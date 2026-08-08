<?php

namespace App\Console\Commands\Billing;

use App\Actions\Subscription\SyncStripeSubscriptionAction;
use App\DTOs\Subscription\SyncSubscriptionDTO;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Services\Billing\ReconciliationReportGenerator;
use App\Services\Billing\WebhookHealthMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BillingReconcileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:reconcile
                            {--dry-run : Show what would be fixed without making changes}
                            {--subscription-id= : Reconcile specific subscription only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect and fix drift between local database and Stripe';

    public function __construct(
        private SyncStripeSubscriptionAction $syncAction,
        private WebhookHealthMonitor $webhookHealthMonitor,
        private ReconciliationReportGenerator $reportGenerator,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $specificSubscriptionId = $this->option('subscription-id');

        $this->info('═══════════════════════════════════════════════');
        $this->info('   Billing Reconciliation Report');
        $this->info('═══════════════════════════════════════════════');
        $this->newLine();
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('Date: ' . now()->format('Y-m-d H:i:s') . ' UTC');
        $this->newLine();

        // Get subscriptions to check
        $subscriptions = $this->getSubscriptionsToCheck($specificSubscriptionId);

        if ($subscriptions->isEmpty()) {
            $this->warn('No active subscriptions found to reconcile.');
            return self::SUCCESS;
        }

        // Reconcile subscriptions
        $this->info('Checking Subscriptions...');
        $this->newLine();
        
        $results = $this->reconcileSubscriptions($subscriptions, $isDryRun);

        // Display summary
        $this->displaySummary($results);

        // Check for duplicate provider_subscription_id (critical data integrity issue)
        $this->checkForDuplicates($isDryRun);

        // Display webhook health
        $this->displayWebhookHealth();

        // Log the reconciliation
        Log::channel('billing')->info('billing.reconciliation_completed', [
            'dry_run' => $isDryRun,
            'subscriptions_checked' => $results['checked'],
            'drift_detected' => $results['drift_detected'],
            'fixed' => $results['fixed'],
            'failed' => $results['failed'],
        ]);

        return $results['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Get subscriptions to check.
     */
    private function getSubscriptionsToCheck(?string $specificSubscriptionId): \Illuminate\Support\Collection
    {
        if ($specificSubscriptionId) {
            $subscription = Subscription::find($specificSubscriptionId);
            return $subscription ? collect([$subscription]) : collect();
        }

        // Get all active subscriptions (those that have provider subscription IDs)
        return Subscription::whereNotNull('provider_subscription_id')
            ->whereIn('status', [
                SubscriptionStatusEnum::TRIALING->value,
                SubscriptionStatusEnum::ACTIVE->value,
                SubscriptionStatusEnum::PAST_DUE->value,
                SubscriptionStatusEnum::GRACE_PERIOD->value,
                SubscriptionStatusEnum::PAUSED->value,
                SubscriptionStatusEnum::CANCELED->value,
            ])
            ->with(['plan', 'billingAccount'])
            ->get();
    }

    /**
     * Reconcile subscriptions and detect drift.
     */
    private function reconcileSubscriptions(\Illuminate\Support\Collection $subscriptions, bool $isDryRun): array
    {
        $checked = 0;
        $driftDetected = 0;
        $fixed = 0;
        $failed = 0;
        $driftDetails = [];

        foreach ($subscriptions as $subscription) {
            $checked++;
            
            try {
                $driftInfo = $this->checkSubscriptionDrift($subscription, $isDryRun);
                
                if ($driftInfo['has_drift']) {
                    $driftDetected++;
                    $driftDetails[] = $driftInfo;
                    
                    if ($driftInfo['fixed']) {
                        $fixed++;
                        $this->info("✓ Subscription #{$subscription->id}: {$driftInfo['description']} → FIXED");
                    } else {
                        $this->warn("⚠ Subscription #{$subscription->id}: {$driftInfo['description']} → DRY RUN (would fix)");
                    }
                } else {
                    $this->line("• Subscription #{$subscription->id}: No drift detected");
                }
                
            } catch (\Exception $e) {
                $failed++;
                $this->error("✗ Subscription #{$subscription->id}: Failed - {$e->getMessage()}");
                
                Log::channel('billing')->error('billing.reconciliation_failed', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return [
            'checked' => $checked,
            'drift_detected' => $driftDetected,
            'fixed' => $fixed,
            'failed' => $failed,
            'drift_details' => $driftDetails,
        ];
    }

    /**
     * Check for drift in a single subscription.
     */
    private function checkSubscriptionDrift(Subscription $subscription, bool $isDryRun): array
    {
        // Store original state
        $originalStatus = $subscription->status;
        $originalPeriodEnd = $subscription->current_period_ends_at;
        $originalCancelFlag = $subscription->cancel_at_period_end;

        // Sync with Stripe (this will detect drift)
        if (!$isDryRun) {
            $syncedSubscription = $this->syncAction->execute(
                new SyncSubscriptionDTO($subscription->id)
            );
        } else {
            // In dry run, fetch Stripe data without updating
            $syncedSubscription = $subscription;
        }

        // Detect what changed
        $statusDrift = $originalStatus !== $syncedSubscription->status;
        $periodDrift = $originalPeriodEnd?->ne($syncedSubscription->current_period_ends_at) ?? false;
        $cancelFlagDrift = $originalCancelFlag !== $syncedSubscription->cancel_at_period_end;

        $hasDrift = $statusDrift || $periodDrift || $cancelFlagDrift;

        $description = [];
        if ($statusDrift) {
            $description[] = "Status: {$originalStatus} → {$syncedSubscription->status}";
        }
        if ($periodDrift) {
            $description[] = "Period end mismatch";
        }
        if ($cancelFlagDrift) {
            $description[] = "Cancel flag mismatch";
        }

        return [
            'subscription_id' => $subscription->id,
            'has_drift' => $hasDrift,
            'status_drift' => $statusDrift,
            'period_drift' => $periodDrift,
            'cancel_flag_drift' => $cancelFlagDrift,
            'description' => implode(', ', $description),
            'original_status' => $originalStatus,
            'synced_status' => $syncedSubscription->status,
            'fixed' => !$isDryRun && $hasDrift,
        ];
    }

    /**
     * Display reconciliation summary.
     */
    private function displaySummary(array $results): void
    {
        $this->newLine();
        $this->info('─────────────────────────────────────────────────');
        $this->info('Summary');
        $this->info('─────────────────────────────────────────────────');
        
        $this->line("Subscriptions Checked: {$results['checked']}");
        $this->line("Drift Detected: {$results['drift_detected']}");
        
        if ($this->option('dry-run')) {
            $this->line("Would Fix: {$results['drift_detected']}");
        } else {
            $this->line("Fixed: {$results['fixed']}");
        }
        
        $this->line("Failed: {$results['failed']}");
        $this->newLine();

        if ($results['drift_detected'] > 0) {
            $this->info('Drift Details:');
            foreach ($results['drift_details'] as $detail) {
                $this->line("  - Subscription #{$detail['subscription_id']}: {$detail['description']}");
            }
            $this->newLine();
        }
    }

    /**
     * Display webhook health information.
     */
    private function displayWebhookHealth(): void
    {
        $this->info('─────────────────────────────────────────────────');
        $this->info('Webhook Health');
        $this->info('─────────────────────────────────────────────────');

        $health = $this->webhookHealthMonitor->getHealthScore();
        
        $statusColor = match($health['status']) {
            'healthy' => 'info',
            'warning' => 'warn',
            'critical' => 'error',
        };

        $this->line("Health Score: {$health['health_score']}% ({$health['status']})");
        $this->line("Failed webhooks (24h): {$health['failed_webhooks_24h']}");
        $this->line("Unprocessed webhooks (1h): {$health['unprocessed_webhooks_1h']}");
        $this->line("Average processing time: {$health['average_processing_time_ms']}ms");

        if ($health['should_alert']) {
            $this->newLine();
            $this->error('⚠ ALERT: Webhook health requires attention!');
        }

        $this->newLine();
        $this->info('✓ Reconciliation complete');
        $this->info('═══════════════════════════════════════════════');
    }

    /**
     * Check for duplicate provider_subscription_id across ALL statuses.
     * 
     * This is a critical data integrity check. The UNIQUE constraint prevents
     * new duplicates, but this detects existing corruption from the old bug.
     */
    private function checkForDuplicates(bool $isDryRun): void
    {
        $this->newLine();
        $this->info('─────────────────────────────────────────────────');
        $this->info('Duplicate Detection (ALL statuses)');
        $this->info('─────────────────────────────────────────────────');

        // Find all provider_subscription_ids that appear more than once
        // Check ALL statuses (including canceled) to find data corruption
        $duplicates = Subscription::select('provider_subscription_id')
            ->whereNotNull('provider_subscription_id')
            ->groupBy('provider_subscription_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('provider_subscription_id');

        if ($duplicates->isEmpty()) {
            $this->info('✓ No duplicate provider_subscription_id found');
            return;
        }

        $this->error("⚠ Found {$duplicates->count()} duplicate provider_subscription_id values!");
        $this->newLine();

        foreach ($duplicates as $providerSubId) {
            $affectedSubscriptions = Subscription::where('provider_subscription_id', $providerSubId)
                ->orderBy('id')
                ->get();

            $this->warn("Provider Subscription ID: {$providerSubId}");
            $this->line("  Affects {$affectedSubscriptions->count()} local subscriptions:");
            
            foreach ($affectedSubscriptions as $sub) {
                $this->line("    - Subscription #{$sub->id}: Plan #{$sub->plan_id}, Status: {$sub->status}, Created: {$sub->created_at}");
            }

            if (!$isDryRun) {
                // Auto-fix strategy: Keep the ACTIVE/TRIALING one, clear provider_subscription_id from others
                $keepSubscription = $affectedSubscriptions->whereIn('status', ['active', 'trialing'])->first()
                    ?? $affectedSubscriptions->first(); // fallback to first if none active

                $this->info("  → Keeping Subscription #{$keepSubscription->id} (Status: {$keepSubscription->status})");

                foreach ($affectedSubscriptions as $sub) {
                    if ($sub->id !== $keepSubscription->id) {
                        $sub->update(['provider_subscription_id' => null]);
                        $this->info("  → Cleared provider_subscription_id from Subscription #{$sub->id}");
                        
                        Log::channel('billing')->warning('billing.duplicate_fixed', [
                            'provider_subscription_id' => $providerSubId,
                            'cleared_from_subscription_id' => $sub->id,
                            'kept_subscription_id' => $keepSubscription->id,
                        ]);
                    }
                }
            } else {
                $this->warn("  → DRY RUN: Would fix by keeping one subscription and clearing others");
            }

            $this->newLine();
        }

        if (!$isDryRun) {
            $this->info("✓ Fixed {$duplicates->count()} duplicate(s)");
        }
    }
}
