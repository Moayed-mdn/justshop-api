<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Stripe\StripeClient;

class SyncSubscriptionPeriods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:sync-periods {--dry-run : Show what would be synced without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync subscription period dates from Stripe for subscriptions missing current_period_ends_at';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('Running in DRY-RUN mode - no changes will be made');
        }
        
        $this->info('Syncing subscription periods from Stripe...');
        $this->newLine();
        
        // Find subscriptions with provider_subscription_id but missing current_period_ends_at
        $subscriptions = Subscription::whereNotNull('provider_subscription_id')
            ->whereNull('current_period_ends_at')
            ->get();
            
        if ($subscriptions->isEmpty()) {
            $this->info('✓ All subscriptions already have period end dates');
            return self::SUCCESS;
        }
        
        $this->info("Found {$subscriptions->count()} subscription(s) to sync");
        $this->newLine();
        
        $stripe = new StripeClient(config('services.stripe.secret'));
        $synced = 0;
        $failed = 0;
        
        foreach ($subscriptions as $subscription) {
            $this->line("Subscription #{$subscription->id} ({$subscription->plan->code})");
            $this->line("  Provider ID: {$subscription->provider_subscription_id}");
            
            try {
                $stripeSubscription = $stripe->subscriptions->retrieve(
                    $subscription->provider_subscription_id
                );
                
                // Get period end from subscription items
                $items = $stripeSubscription->items->data ?? [];
                if (empty($items)) {
                    $this->warn('  ⚠ No subscription items found in Stripe');
                    $failed++;
                    continue;
                }
                
                $periodEnd = $items[0]->current_period_end ?? null;
                $periodStart = $items[0]->current_period_start ?? null;
                
                if (!$periodEnd) {
                    $this->warn('  ⚠ No period end found in Stripe subscription items');
                    $failed++;
                    continue;
                }
                
                $periodEndDate = Carbon::createFromTimestamp($periodEnd);
                $periodStartDate = Carbon::createFromTimestamp($periodStart);
                
                $this->line("  Period: {$periodStartDate->toDateTimeString()} → {$periodEndDate->toDateTimeString()}");
                
                if (!$isDryRun) {
                    $subscription->update([
                        'current_period_ends_at' => $periodEndDate,
                        'current_period_starts_at' => $subscription->current_period_starts_at ?? $periodStartDate,
                    ]);
                    $this->info('  ✓ Synced');
                } else {
                    $this->info('  ✓ Would sync (dry-run)');
                }
                
                $synced++;
                
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}");
                $failed++;
            }
            
            $this->newLine();
        }
        
        $this->newLine();
        $this->info("Results:");
        $this->line("  Synced: {$synced}");
        if ($failed > 0) {
            $this->line("  Failed: {$failed}");
        }
        
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
