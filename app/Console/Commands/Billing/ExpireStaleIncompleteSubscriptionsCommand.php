<?php

declare(strict_types=1);

namespace App\Console\Commands\Billing;

use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Services\Subscription\SubscriptionStateMachine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireStaleIncompleteSubscriptionsCommand extends Command
{
    protected $signature = 'billing:expire-stale-incomplete-subscriptions
                            {--dry-run : Show which subscriptions would be expired without actually expiring them}
                            {--batch-size=50 : Number of subscriptions to process per batch}';

    protected $description = 'Expire incomplete subscriptions that are older than 24 hours (abandoned checkouts)';

    public function __construct(
        private readonly SubscriptionStateMachine $stateMachine,
    ) {
        parent::__construct();
    }

    /**
     * Sweep for stale incomplete subscriptions and mark them as EXPIRED.
     * Should run hourly via scheduler.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');

        $this->info('🔍 Searching for stale incomplete subscriptions...');

        // Find all incomplete subscriptions created more than 24 hours ago
        // (Stripe Checkout Sessions expire after 24h by default)
        $staleIncomplete = Subscription::where('status', SubscriptionStatusEnum::INCOMPLETE->value)
            ->where('created_at', '<=', now()->subHours(24))
            ->limit($batchSize)
            ->get();

        if ($staleIncomplete->isEmpty()) {
            $this->info('✅ No stale incomplete subscriptions found.');
            return self::SUCCESS;
        }

        $this->warn("Found {$staleIncomplete->count()} stale incomplete subscription(s)");

        if ($isDryRun) {
            $this->table(
                ['ID', 'Billing Account ID', 'Plan', 'Created At', 'Hours Stale'],
                $staleIncomplete->map(fn($sub) => [
                    $sub->id,
                    $sub->billing_account_id,
                    $sub->plan->code,
                    $sub->created_at->toDateTimeString(),
                    now()->diffInHours($sub->created_at),
                ])
            );

            $this->info('🔸 Dry run mode - no changes made');
            return self::SUCCESS;
        }

        $expired = 0;
        $failed = 0;

        foreach ($staleIncomplete as $subscription) {
            try {
                // Use state machine to transition to EXPIRED
                $this->stateMachine->transition(
                    $subscription,
                    SubscriptionStatusEnum::EXPIRED,
                    source: 'system',
                    reason: 'stale_incomplete_cleanup'
                );

                // Update ended_at timestamp
                $subscription->update(['ended_at' => now()]);

                $this->info("✅ Expired subscription #{$subscription->id}");
                $expired++;

            } catch (\Throwable $e) {
                $this->error("❌ Failed to expire subscription #{$subscription->id}: {$e->getMessage()}");

                Log::channel('billing')->error('billing.expire_stale_incomplete.failed', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $failed++;
            }
        }

        $this->newLine();
        $this->info("✅ Processed: {$staleIncomplete->count()}");
        $this->info("✅ Expired: {$expired}");

        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
        }

        Log::channel('billing')->info('billing.expire_stale_incomplete.completed', [
            'processed' => $staleIncomplete->count(),
            'expired' => $expired,
            'failed' => $failed,
        ]);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
