<?php

declare(strict_types=1);

namespace App\Console\Commands\Billing;

use App\Actions\Subscription\SuspendSubscriptionAction;
use App\DTOs\Subscription\SuspendSubscriptionDTO;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireGracePeriodsCommand extends Command
{
    protected $signature = 'billing:expire-grace-periods
                            {--dry-run : Show which subscriptions would be suspended without actually suspending them}
                            {--batch-size=50 : Number of subscriptions to process per batch}';

    protected $description = 'Suspend subscriptions where grace_period_ends_at has passed without payment';

    public function __construct(
        private readonly SuspendSubscriptionAction $suspendSubscription,
    ) {
        parent::__construct();
    }

    /**
     * Sweep for expired grace periods and suspend them (PAUSED status).
     * Should run hourly via scheduler to ensure timely suspension.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');

        $this->info('🔍 Searching for expired grace periods...');

        // Find all grace_period subscriptions where grace_period_ends_at has passed
        $expiredGracePeriods = Subscription::where('status', SubscriptionStatusEnum::GRACE_PERIOD->value)
            ->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<=', now())
            ->limit($batchSize)
            ->get();

        if ($expiredGracePeriods->isEmpty()) {
            $this->info('✅ No expired grace periods found.');
            return self::SUCCESS;
        }

        $this->warn("Found {$expiredGracePeriods->count()} expired grace period(s)");

        if ($isDryRun) {
            $this->table(
                ['ID', 'Billing Account ID', 'Plan', 'Grace Period Ended At', 'Hours Overdue'],
                $expiredGracePeriods->map(fn($sub) => [
                    $sub->id,
                    $sub->billing_account_id,
                    $sub->plan->code,
                    $sub->grace_period_ends_at->toDateTimeString(),
                    now()->diffInHours($sub->grace_period_ends_at),
                ])
            );

            $this->info('🔸 Dry run mode - no changes made');
            return self::SUCCESS;
        }

        $suspended = 0;
        $failed = 0;

        foreach ($expiredGracePeriods as $subscription) {
            try {
                $this->suspendSubscription->execute(
                    SuspendSubscriptionDTO::fromSystem(
                        subscriptionId: $subscription->id,
                        reason: 'Grace period expired without payment',
                    )
                );

                $this->info("✅ Suspended subscription #{$subscription->id}");
                $suspended++;

            } catch (\Throwable $e) {
                $this->error("❌ Failed to suspend subscription #{$subscription->id}: {$e->getMessage()}");

                Log::channel('billing')->error('billing.expire_grace_periods.failed', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $failed++;
            }
        }

        $this->newLine();
        $this->info("✅ Processed: {$expiredGracePeriods->count()}");
        $this->info("✅ Suspended: {$suspended}");

        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
        }

        Log::channel('billing')->info('billing.expire_grace_periods.completed', [
            'processed' => $expiredGracePeriods->count(),
            'suspended' => $suspended,
            'failed' => $failed,
        ]);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
