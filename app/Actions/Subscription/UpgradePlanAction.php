<?php

namespace App\Actions\Subscription;

use App\Actions\Entitlement\RecomputeEntitlementsAction;
use App\Contracts\Billing\BillingProviderInterface;
use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\DTOs\Subscription\ChangePlanDTO;
use App\Enums\Subscription\SubscriptionEventTypeEnum;
use App\Events\Subscription\PlanUpgraded;
use App\Models\Store;
use App\Models\Subscription;
use App\Repositories\Subscription\PlanRepository;
use App\Repositories\Subscription\SubscriptionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpgradePlanAction
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepo,
        private PlanRepository $planRepo,
        private BillingProviderInterface $billingProvider,
        private RecomputeEntitlementsAction $recomputeEntitlements,
    ) {}

    /**
     * Upgrade plan immediately with proration.
     * 
     * Upgrades are always immediate and prorated. The customer is charged the
     * prorated difference immediately.
     */
    public function execute(ChangePlanDTO $dto): Subscription
    {
        return DB::transaction(function () use ($dto) {
            $subscription = $this->subscriptionRepo->findActiveForAccountOrFail(
                $dto->billingAccountId
            );

            $newPlan = $this->planRepo->findByCodeOrFail($dto->planCode);
            $newPrice = $newPlan->prices()
                ->where('billing_cycle', $dto->billingCycle->value)
                ->where('currency', $subscription->billingAccount->default_currency)
                ->firstOrFail();

            $oldPlan = $subscription->plan;

            // Prevent "upgrade" to same or lower tier
            if ($newPlan->tier_value() <= $oldPlan->tier_value()) {
                throw new \DomainException(
                    "Cannot upgrade to same or lower tier. Use downgrade endpoint instead."
                );
            }

            // Call Stripe to update the price immediately with proration
            $this->billingProvider->updateSubscription(
                subscription: $subscription,
                newPlanPriceId: $newPrice->provider_price_id,
                prorated: true
            );

            // Update local subscription
            $subscription->update([
                'plan_id' => $newPlan->id,
                'plan_price_id' => $newPrice->id,
                'billing_cycle' => $dto->billingCycle->value,
                // Clear any pending downgrade
                'pending_plan_id' => null,
                'pending_plan_effective_at' => null,
                'provider_synced_at' => now(),
            ]);

            // Record event
            $subscription->events()->create([
                'event_type' => SubscriptionEventTypeEnum::UPGRADED->value,
                'from_status' => $subscription->status->value,
                'to_status' => $subscription->status->value,
                'actor_user_id' => $dto->actorUserId,
                'source' => 'merchant',
                'reason' => "Upgraded from {$oldPlan->code} to {$newPlan->code}",
                'payload' => [
                    'from_plan' => $oldPlan->code,
                    'to_plan' => $newPlan->code,
                    'cycle' => $dto->billingCycle->value,
                ],
            ]);

            // Recompute entitlements for all stores on this account
            $stores = Store::where('owner_id', $subscription->billingAccount->owner_user_id)->get();
            
            foreach ($stores as $store) {
                $this->recomputeEntitlements->execute(
                    new RecomputeEntitlementsDTO(
                        billingAccountId: $dto->billingAccountId,
                        storeId: $store->id,
                    )
                );
            }

            event(new PlanUpgraded($subscription, $oldPlan, $newPlan));

            Log::channel('billing')->info('subscription.upgraded', [
                'subscription_id' => $subscription->id,
                'from_plan' => $oldPlan->code,
                'to_plan' => $newPlan->code,
                'billing_cycle' => $dto->billingCycle->value,
            ]);

            return $subscription->fresh(['plan', 'planPrice']);
        });
    }
}
