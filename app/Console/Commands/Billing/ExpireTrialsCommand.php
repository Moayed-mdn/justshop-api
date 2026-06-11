<?php

declare(strict_types=1);

namespace App\Console\Commands\Billing;

use App\Actions\Subscription\ExpireSubscriptionAction;
use App\DTOs\Subscription\ExpireSubscriptionDTO;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireTrialsCommand extends Command
{
    protected $signature = 'billing:expire-trials
                            {--dry-run : Show which subscriptions would be expired without actually expiring them}
                            {--batch-size=50 : Number of subscriptions to process per batch}';

    protected $description = 'Expire trial subscriptions that have reached their trial_ends_at date';

    public function __construct(
        private readonly ExpireSubscriptionAction $expireSubscription,
    ) {
        parent::__construct();
    }

    /**
     * Sweep for expired trials and mark them as EXPIRED.
     * Should run daily via scheduler (typically at midnight).
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');

        $this->info('🔍 Searching for expired trial subscriptions...');

        // Find all trialing subscriptions where trial_ends_at has passed
        $expiredTrials = Subscription::where('status', SubscriptionStatusEnum::TRIALING->value)
            ->where('trial_ends_at', '<=', now())
            ->limit($batchSize)
            ->get();

        if ($expiredTrials->isEmpty()) {
            $this->info('✅ No expired trials found.');
            return self::SUCCESS;
        }

        $this->warn("Found {$expiredTrials->count()} expired trial(s)");

        if ($isDryRun) {
            $this->table(
                ['ID', 'Billing Account ID', 'Plan', 'Trial Ended At', 'Days Overdue'],
                $expiredTrials->map(fn($sub) => [
                    $sub->id,
                    $sub->billing_account_id,
                    $sub->plan->code,
                    $sub->trial_ends_at->toDateTimeString(),
                    now()->diffInDays($sub->trial_ends_at),
                ])
            );

            $this->info('🔸 Dry run mode - no changes made');
            return self::SUCCESS;
        }

        $expired = 0;
        $failed = 0;

        foreach ($expiredTrials as $subscription) {
            try {
                $this->expireSubscription->execute(
                    ExpireSubscriptionDTO::fromTrialExpiry($subscription->id)
                );

                $this->info("✅ Expired subscription #{$subscription->id}");
                $expired++;

            } catch (\Throwable $e) {
                $this->error("❌ Failed to expire subscription #{$subscription->id}: {$e->getMessage()}");

                Log::channel('billing')->error('billing.expire_trials.failed', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $failed++;
            }
        }

        $this->newLine();
        $this->info("✅ Processed: {$expiredTrials->count()}");
        $this->info("✅ Expired: {$expired}");

        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
        }

        Log::channel('billing')->info('billing.expire_trials.completed', [
            'processed' => $expiredTrials->count(),
            'expired' => $expired,
            'failed' => $failed,
        ]);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
