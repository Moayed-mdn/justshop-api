<?php

namespace App\Console\Commands\Billing;

use App\Actions\Billing\CreateBillingAccountAction;
use App\Actions\Entitlement\RecomputeEntitlementsAction;
use App\Actions\Subscription\StartTrialAction;
use App\DTOs\Billing\CreateBillingAccountDTO;
use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\DTOs\Subscription\StartTrialDTO;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Plan;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use App\Repositories\Subscription\PlanRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingMigrateLegacyAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:migrate-legacy-accounts
                            {--grace-days=90 : Number of days of grace period}
                            {--plan=starter : Default plan code for legacy accounts}
                            {--dry-run : Show what would be migrated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing stores to grandfathered subscriptions with grace period';

    public function __construct(
        private CreateBillingAccountAction $createBillingAccount,
        private RecomputeEntitlementsAction $recomputeEntitlements,
        private PlanRepository $planRepository,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $graceDays = (int) $this->option('grace-days');
        $planCode = $this->option('plan');

        $this->info('═══════════════════════════════════════════════');
        $this->info('   Legacy Accounts Migration');
        $this->info('═══════════════════════════════════════════════');
        $this->newLine();

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('Configuration:');
        $this->line("  Grace Period: {$graceDays} days");
        $this->line("  Default Plan: {$planCode}");
        $this->line("  Grandfathered Until: " . now()->addDays($graceDays)->format('Y-m-d H:i:s'));
        $this->newLine();

        // Get default plan - use current (non-superseded) version
        $plan = $this->planRepository->findCurrentByCode($planCode);
        if (!$plan) {
            $this->error("Plan '{$planCode}' not found. Please create it first or specify a different plan.");
            return self::FAILURE;
        }

        // Get all stores that need migration
        $stores = $this->getStoresToMigrate();

        if ($stores->isEmpty()) {
            $this->info('No stores found that need migration.');
            return self::SUCCESS;
        }

        $this->info("Found {$stores->count()} store(s) to migrate.");
        $this->newLine();

        // Migrate stores
        $results = $this->migrateStores($stores, $plan, $graceDays, $isDryRun);

        // Display summary
        $this->displaySummary($results, $isDryRun);

        // Log migration
        Log::channel('billing')->info('billing.legacy_migration_completed', [
            'dry_run' => $isDryRun,
            'stores_processed' => $results['processed'],
            'stores_migrated' => $results['migrated'],
            'stores_skipped' => $results['skipped'],
            'stores_failed' => $results['failed'],
            'grace_days' => $graceDays,
            'plan_code' => $planCode,
        ]);

        return $results['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Get stores that need migration.
     */
    private function getStoresToMigrate(): \Illuminate\Support\Collection
    {
        return Store::whereNull('billing_account_id')
            ->where('is_grandfathered', false)
            ->with('owner')
            ->get();
    }

    /**
     * Migrate stores to grandfathered subscriptions.
     */
    private function migrateStores(
        \Illuminate\Support\Collection $stores,
        Plan $plan,
        int $graceDays,
        bool $isDryRun
    ): array {
        $processed = 0;
        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($stores as $store) {
            $processed++;

            try {
                if ($this->shouldSkipStore($store)) {
                    $skipped++;
                    $this->warn("⚠ Store #{$store->id} ({$store->name}): Skipped - no owner");
                    continue;
                }

                if (!$isDryRun) {
                    $this->migrateStore($store, $plan, $graceDays);
                    $migrated++;
                    $this->info("✓ Store #{$store->id} ({$store->name}): Migrated successfully");
                } else {
                    $migrated++; // Count what would be migrated
                    $this->line("• Store #{$store->id} ({$store->name}): Would migrate");
                }

            } catch (\Exception $e) {
                $failed++;
                $this->error("✗ Store #{$store->id}: Failed - {$e->getMessage()}");

                Log::channel('billing')->error('billing.legacy_migration_failed', [
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'owner_id' => $store->owner_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return [
            'processed' => $processed,
            'migrated' => $migrated,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    /**
     * Check if store should be skipped.
     */
    private function shouldSkipStore(Store $store): bool
    {
        return !$store->owner;
    }

    /**
     * Migrate a single store.
     */
    private function migrateStore(Store $store, Plan $plan, int $graceDays): void
    {
        DB::transaction(function () use ($store, $plan, $graceDays) {
            $owner = $store->owner;

            // 1. Create or get billing account
            $billingAccount = $owner->billingAccount;
            
            if (!$billingAccount) {
                $billingAccount = $this->createBillingAccount->execute(
                    new CreateBillingAccountDTO(
                        ownerUserId: $owner->id,
                        billingEmail: $owner->email,
                        legalName: $owner->name,
                        countryCode: null,
                        defaultCurrency: $store->currency ?? 'USD',
                    )
                );

                // Update user with billing account
                $owner->update(['billing_account_id' => $billingAccount->id]);
            }

            // 2. Create grandfathered subscription (no Stripe linkage)
            $grandfatheredUntil = now()->addDays($graceDays);

            $subscription = Subscription::create([
                'billing_account_id' => $billingAccount->id,
                'plan_id' => $plan->id,
                'plan_price_id' => null, // No price - this is grandfathered
                'status' => SubscriptionStatusEnum::ACTIVE->value,
                'billing_cycle' => 'monthly',
                'provider' => 'stripe',
                'provider_subscription_id' => null, // No Stripe subscription
                'provider_status' => null,
                'provider_synced_at' => null,
                'trial_starts_at' => null,
                'trial_ends_at' => null,
                'current_period_starts_at' => now(),
                'current_period_ends_at' => $grandfatheredUntil,
                'grace_period_ends_at' => null,
                'canceled_at' => null,
                'cancel_at_period_end' => false,
                'collection_paused' => false,
                'ended_at' => null,
                'pending_plan_id' => null,
                'pending_plan_effective_at' => null,
                'metadata' => [
                    'migrated_at' => now()->toIso8601String(),
                    'migration_source' => 'legacy',
                    'original_store_id' => $store->id,
                ],
            ]);

            // 3. Mark store as grandfathered
            $store->update([
                'is_grandfathered' => true,
                'grandfathered_until' => $grandfatheredUntil,
            ]);

            // 4. Materialize entitlement snapshot
            $this->recomputeEntitlements->execute(
                new RecomputeEntitlementsDTO(
                    storeId: $store->id,
                    billingAccountId: $billingAccount->id,
                    isGrandfathered: true,
                )
            );

            Log::channel('billing')->info('billing.store_migrated', [
                'store_id' => $store->id,
                'store_name' => $store->name,
                'owner_id' => $owner->id,
                'billing_account_id' => $billingAccount->id,
                'subscription_id' => $subscription->id,
                'plan_code' => $plan->code,
                'grandfathered_until' => $grandfatheredUntil->toIso8601String(),
            ]);
        });
    }

    /**
     * Display migration summary.
     */
    private function displaySummary(array $results, bool $isDryRun): void
    {
        $this->newLine();
        $this->info('─────────────────────────────────────────────────');
        $this->info('Migration Summary');
        $this->info('─────────────────────────────────────────────────');

        $this->line("Stores Processed: {$results['processed']}");

        if ($isDryRun) {
            $this->line("Would Migrate: {$results['migrated']}");
        } else {
            $this->line("Migrated Successfully: {$results['migrated']}");
        }

        $this->line("Skipped: {$results['skipped']}");
        $this->line("Failed: {$results['failed']}");
        $this->newLine();

        if ($results['migrated'] > 0) {
            if ($isDryRun) {
                $this->info("✓ Dry run complete - {$results['migrated']} store(s) ready for migration");
            } else {
                $this->info("✓ Migration complete - {$results['migrated']} store(s) now grandfathered");
            }
        }

        $this->info('═══════════════════════════════════════════════');
    }
}
