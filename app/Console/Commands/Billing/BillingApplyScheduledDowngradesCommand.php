<?php

namespace App\Console\Commands\Billing;

use App\Actions\Subscription\ApplyScheduledDowngradeAction;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BillingApplyScheduledDowngradesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:apply-scheduled-downgrades';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply scheduled plan downgrades that have reached their effective date';

    public function __construct(
        private ApplyScheduledDowngradeAction $applyScheduledDowngrade,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for scheduled downgrades to apply...');

        // Find subscriptions with pending downgrades that are due
        $subscriptions = Subscription::whereNotNull('pending_plan_id')
            ->whereNotNull('pending_plan_effective_at')
            ->where('pending_plan_effective_at', '<=', now())
            ->with(['plan', 'pendingPlan', 'billingAccount'])
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No scheduled downgrades to apply.');
            return self::SUCCESS;
        }

        $this->info("Found {$subscriptions->count()} downgrade(s) to apply.");

        $applied = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $this->line("Applying downgrade for subscription #{$subscription->id}...");
                
                $this->applyScheduledDowngrade->execute($subscription);
                
                $this->info("✓ Applied downgrade from {$subscription->plan->code} to {$subscription->pendingPlan->code}");
                $applied++;
            } catch (\Exception $e) {
                $this->error("✗ Failed to apply downgrade for subscription #{$subscription->id}: {$e->getMessage()}");
                
                Log::channel('billing')->error('scheduled_downgrade.failed', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Summary: {$applied} applied, {$failed} failed");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
