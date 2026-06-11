<?php

namespace App\Actions\Subscription;

use App\Actions\Entitlement\RecomputeEntitlementsAction;
use App\Contracts\Billing\BillingProviderInterface;
use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\Enums\Subscription\BillingCycleEnum;
use App\Enums\Subscription\SubscriptionEventTypeEnum;
use App\Events\Subscription\PlanDowngraded;
use App\Models\Store;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplyScheduledDowngradeAction
{
    public function __construct(
        private BillingProviderInterface $billingProvider,
        private RecomputeEntitlementsAction $recomputeEntitlements,
    ) {}

    /**
     * Apply a scheduled downgrade.
     * 
     * Called by billing:apply-scheduled-downgrades command at the period end date.
     */
    public function execute(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            if (! $subscription->pending_plan_id) {
                throw new \InvalidArgumentException(
                    "Subscription #{$subscription->id} has no pending plan"
                );
            }

            if (now()->lt($subscription->pending_plan_effective_at)) {
                throw new \InvalidArgumentException(
                    "Scheduled downgrade not yet effective for subscription #{$subscription->id}"
                );
            }

            $oldPlan = $subscription->plan;
            $newPlan = $subscription->pendingPlan;
            
            // Get the new plan price for the current billing cycle
            $newPrice = $newPlan->prices()
                ->where('billing_cycle', $subscription->billing_cycle)
                ->where('currency', $subscription->billingAccount->default_currency)
                ->firstOrFail();

            // Update Stripe subscription (no proration on downgrade)
            $this->billingProvider->updateSubscription(
                subscription: $subscription,
                newPlanPriceId: $newPrice->provider_price_id,
                prorated: false
            );

            // Update local subscription
            $subscription->update([
                'plan_id' => $newPlan->id,
                'plan_price_id' => $newPrice->id,
                'pending_plan_id' => null,
                'pending_plan_effective_at' => null,
                'provider_synced_at' => now(),
            ]);

            // Record event
            $subscription->events()->create([
                'event_type' => SubscriptionEventTypeEnum::DOWNGRADED->value,
                'from_status' => $subscription->status->value,
                'to_status' => $subscription->status->value,
                'source' => 'system',
                'reason' => "Scheduled downgrade applied from {$oldPlan->code} to {$newPlan->code}",
                'payload' => [
                    'from_plan' => $oldPlan->code,
                    'to_plan' => $newPlan->code,
                ],
            ]);

            // Recompute entitlements for all stores on this account
            $stores = Store::where('owner_id', $subscription->billingAccount->owner_user_id)->get();
            
            foreach ($stores as $store) {
                $this->recomputeEntitlements->execute(
                    new RecomputeEntitlementsDTO(
                        billingAccountId: $subscription->billing_account_id,
                        storeId: $store->id,
                    )
                );
            }

            event(new PlanDowngraded($subscription, $oldPlan, $newPlan));

            Log::channel('billing')->info('subscription.downgrade_applied', [
                'subscription_id' => $subscription->id,
                'from_plan' => $oldPlan->code,
                'to_plan' => $newPlan->code,
            ]);

            return $subscription->fresh(['plan', 'planPrice']);
        });
    }
}
