<?php

namespace App\Console\Commands\Billing;

use App\Actions\Subscription\ExpireSubscriptionAction;
use App\DTOs\Subscription\ExpireSubscriptionDTO;
use App\Models\Store;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BillingLegacyGraceSweepCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:legacy-grace-sweep
                            {--dry-run : Show what would be expired without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire grandfathered stores that have passed their grace period';

    public function __construct(
        private ExpireSubscriptionAction $expireSubscription,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('═══════════════════════════════════════════════');
        $this->info('   Legacy Grace Period Sweep');
        $this->info('═══════════════════════════════════════════════');
        $this->newLine();

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('Date: ' . now()->format('Y-m-d H:i:s') . ' UTC');
        $this->newLine();

        // Get stores with expired grandfathering
        $stores = $this->getExpiredGrandfatheredStores();

        if ($stores->isEmpty()) {
            $this->info('No grandfathered stores with expired grace period found.');
            return self::SUCCESS;
        }

        $this->info("Found {$stores->count()} store(s) with expired grace period.");
        $this->newLine();

        // Expire stores
        $results = $this->expireStores($stores, $isDryRun);

        // Display summary
        $this->displaySummary($results, $isDryRun);

        // Log sweep
        Log::channel('billing')->info('billing.legacy_grace_sweep_completed', [
            'dry_run' => $isDryRun,
            'stores_processed' => $results['processed'],
            'stores_expired' => $results['expired'],
            'stores_failed' => $results['failed'],
        ]);

        return $results['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Get stores with expired grandfathering period.
     */
    private function getExpiredGrandfatheredStores(): \Illuminate\Support\Collection
    {
        return Store::where('is_grandfathered', true)
            ->whereNotNull('grandfathered_until')
            ->where('grandfathered_until', '<=', now())
            ->with(['owner.billingAccount.subscriptions' => function ($query) {
                $query->whereIn('status', ['active', 'trialing', 'past_due']);
            }])
            ->get();
    }

    /**
     * Expire stores that have passed grace period.
     */
    private function expireStores(\Illuminate\Support\Collection $stores, bool $isDryRun): array
    {
        $processed = 0;
        $expired = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($stores as $store) {
            $processed++;

            try {
                // Check if merchant has subscribed to a paid plan
                $hasPaidSubscription = $this->hasActivePaidSubscription($store);

                if ($hasPaidSubscription) {
                    $skipped++;
                    $this->info("✓ Store #{$store->id} ({$store->name}): Skipped - has active paid subscription");
                    
                    // Clear grandfathering flags since they've subscribed
                    if (!$isDryRun) {
                        $store->update([
                            'is_grandfathered' => false,
                            'grandfathered_until' => null,
                        ]);
                    }
                    continue;
                }

                // Expire the grandfathered subscription
                if (!$isDryRun) {
                    $this->expireGrandfatheredSubscription($store);
                    $expired++;
                    $this->warn("⚠ Store #{$store->id} ({$store->name}): Grace period expired - subscription ended");
                } else {
                    $expired++; // Count what would be expired
                    $this->line("• Store #{$store->id} ({$store->name}): Would expire");
                }

            } catch (\Exception $e) {
                $failed++;
                $this->error("✗ Store #{$store->id}: Failed - {$e->getMessage()}");

                Log::channel('billing')->error('billing.legacy_grace_sweep_failed', [
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return [
            'processed' => $processed,
            'expired' => $expired,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    /**
     * Check if store owner has an active paid subscription.
     */
    private function hasActivePaidSubscription(Store $store): bool
    {
        if (!$store->owner || !$store->owner->billingAccount) {
            return false;
        }

        $subscriptions = $store->owner->billingAccount->subscriptions;

        return $subscriptions->filter(function ($subscription) {
            return in_array($subscription->status, ['active', 'trialing', 'past_due'])
                && $subscription->provider_subscription_id !== null; // Has real Stripe subscription
        })->isNotEmpty();
    }

    /**
     * Expire the grandfathered subscription.
     */
    private function expireGrandfatheredSubscription(Store $store): void
    {
        $billingAccount = $store->owner->billingAccount;

        if (!$billingAccount) {
            throw new \RuntimeException("Store #{$store->id} has no billing account");
        }

        // Find the grandfathered subscription
        $subscription = Subscription::where('billing_account_id', $billingAccount->id)
            ->whereNull('provider_subscription_id') // Grandfathered ones have no Stripe ID
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            throw new \RuntimeException("Store #{$store->id} has no grandfathered subscription to expire");
        }

        // Expire the subscription
        $this->expireSubscription->execute(
            new ExpireSubscriptionDTO(
                subscriptionId: $subscription->id,
                reason: 'grace_period_expired',
            )
        );

        // Update store grandfathering status
        $store->update([
            'is_grandfathered' => false,
            'grandfathered_until' => null,
        ]);

        Log::channel('billing')->warning('billing.grandfathered_subscription_expired', [
            'store_id' => $store->id,
            'store_name' => $store->name,
            'subscription_id' => $subscription->id,
            'billing_account_id' => $billingAccount->id,
            'grandfathered_until' => $store->grandfathered_until?->toIso8601String(),
        ]);
    }

    /**
     * Display sweep summary.
     */
    private function displaySummary(array $results, bool $isDryRun): void
    {
        $this->newLine();
        $this->info('─────────────────────────────────────────────────');
        $this->info('Sweep Summary');
        $this->info('─────────────────────────────────────────────────');

        $this->line("Stores Processed: {$results['processed']}");

        if ($isDryRun) {
            $this->line("Would Expire: {$results['expired']}");
        } else {
            $this->line("Expired: {$results['expired']}");
        }

        $this->line("Skipped (Subscribed): {$results['skipped']}");
        $this->line("Failed: {$results['failed']}");
        $this->newLine();

        if ($results['expired'] > 0) {
            if ($isDryRun) {
                $this->warn("⚠ {$results['expired']} store(s) ready for expiration");
            } else {
                $this->warn("⚠ {$results['expired']} store(s) expired - owners need to subscribe");
            }
        }

        if ($results['skipped'] > 0) {
            $this->info("✓ {$results['skipped']} store(s) already subscribed to paid plans");
        }

        $this->info('═══════════════════════════════════════════════');
    }
}
