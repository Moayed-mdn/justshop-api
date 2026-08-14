<?php

namespace App\Actions\Subscription;

use App\Actions\Entitlement\RecomputeEntitlementsAction;
use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\Enums\Subscription\SubscriptionEventTypeEnum;
use App\Events\Subscription\PlanUpgraded;
use App\Models\Store;
use App\Models\Subscription;
use App\Repositories\Subscription\SubscriptionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Move a subscription from a superseded plan to its current version.
 * 
 * This is a tier-check-free action that allows merchants to move from
 * a legacy plan to its direct successor when the plans have the same tier.
 * 
 * Unlike UpgradePlanAction, this does NOT enforce tier hierarchy - it only
 * follows the superseded_by_plan_id pointer to find the current version.
 */
class MoveToCurrentPlanVersionAction
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepo,
        private RecomputeEntitlementsAction $recomputeEntitlements,
    ) {}

    /**
     * Move subscription to current plan version.
     * 
     * @param int $billingAccountId The billing account ID
     * @param int $actorUserId The user performing the action
     * @return Subscription The updated subscription
     * @throws \DomainException If plan is not superseded or current version is not available
     */
    public function execute(int $billingAccountId, int $actorUserId): Subscription
    {
        return DB::transaction(function () use ($billingAccountId, $actorUserId) {
            // Get subscription with lock
            $subscription = $this->subscriptionRepo->findActiveForAccountOrFailWithLock($billingAccountId);
            
            $currentPlan = $subscription->plan;

            // Verify plan has been superseded
            if (!$currentPlan->superseded_by_plan_id) {
                throw new \DomainException(
                    'Cannot move to current version: Plan has not been superseded.'
                );
            }

            // Get the new (current) plan version
            $newPlan = \App\Models\Plan::find($currentPlan->superseded_by_plan_id);
            
            if (!$newPlan) {
                throw new \DomainException(
                    'Cannot move to current version: New plan version not found.'
                );
            }

            // Verify new plan is active and public
            if (!$newPlan->is_active || !$newPlan->is_public) {
                throw new \DomainException(
                    'Cannot move to current version: New plan version is not available.'
                );
            }

            // Find a matching price on the new plan
            // Try to keep the same billing cycle and currency
            $currentPrice = $subscription->planPrice;
            $newPrice = null;

            if ($currentPrice) {
                // Try to find exact match (same cycle + currency)
                $newPrice = $newPlan->prices()
                    ->where('billing_cycle', $currentPrice->billing_cycle)
                    ->where('currency', $currentPrice->currency)
                    ->where('is_active', true)
                    ->first();
            }

            // Fallback: use the first active price for the account's default currency
            if (!$newPrice) {
                $defaultCurrency = $subscription->billingAccount->default_currency ?? 'USD';
                $newPrice = $newPlan->prices()
                    ->where('currency', $defaultCurrency)
                    ->where('is_active', true)
                    ->orderBy('billing_cycle') // Prefer monthly
                    ->first();
            }

            if (!$newPrice) {
                throw new \DomainException(
                    'Cannot move to current version: No active price found on new plan.'
                );
            }

            Log::channel('billing')->info('subscription.move_to_current_version.starting', [
                'subscription_id' => $subscription->id,
                'from_plan_id' => $currentPlan->id,
                'from_plan_code' => $currentPlan->code,
                'to_plan_id' => $newPlan->id,
                'to_plan_code' => $newPlan->code,
                'same_tier' => $currentPlan->tier_rank === $newPlan->tier_rank,
            ]);

            // Update subscription
            $subscription->update([
                'plan_id' => $newPlan->id,
                'plan_price_id' => $newPrice->id,
                'billing_cycle' => $newPrice->billing_cycle,
                'pending_plan_id' => null,
                'pending_plan_effective_at' => null,
            ]);

            // Record event
            $subscription->events()->create([
                'event_type' => SubscriptionEventTypeEnum::UPGRADED->value,
                'from_status' => $subscription->status->value,
                'to_status' => $subscription->status->value,
                'actor_user_id' => $actorUserId,
                'source' => 'merchant',
                'reason' => "Moved from legacy plan {$currentPlan->code} to current version {$newPlan->code}",
                'payload' => [
                    'from_plan' => $currentPlan->code,
                    'to_plan' => $newPlan->code,
                    'from_plan_id' => $currentPlan->id,
                    'to_plan_id' => $newPlan->id,
                    'cycle' => $newPrice->billing_cycle,
                    'action_type' => 'move_to_current_version',
                ],
            ]);

            // Recompute entitlements for all stores
            $stores = Store::where('owner_id', $subscription->billingAccount->owner_user_id)->get();
            
            foreach ($stores as $store) {
                $this->recomputeEntitlements->execute(
                    new RecomputeEntitlementsDTO(
                        billingAccountId: $billingAccountId,
                        storeId: $store->id,
                    )
                );
            }

            // Fire event (reuse PlanUpgraded event)
            event(new PlanUpgraded($subscription, $currentPlan, $newPlan));

            Log::channel('billing')->info('subscription.moved_to_current_version', [
                'subscription_id' => $subscription->id,
                'from_plan' => $currentPlan->code,
                'to_plan' => $newPlan->code,
                'billing_cycle' => $newPrice->billing_cycle,
            ]);

            return $subscription->fresh(['plan', 'planPrice']);
        });
    }
}
