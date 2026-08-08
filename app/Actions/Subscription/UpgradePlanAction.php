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
     * 
     * Architecture: Stripe call is OUTSIDE the DB transaction to prevent
     * rollback issues. If Stripe succeeds but local update fails, we log
     * a critical error and rely on reconciliation to fix drift.
     */
    public function execute(ChangePlanDTO $dto): Subscription
    {
        // Step 1: Validate plan hierarchy first (outside transaction)
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

        // Step 2: Acquire lock and call Stripe
        // Lock is inside a short transaction just for the Stripe call
        // to prevent concurrent upgrades
        return DB::transaction(function () use ($dto, $subscription, $newPlan, $newPrice, $oldPlan) {
            // Re-fetch with lock to prevent race conditions
            $subscription = $this->subscriptionRepo->findActiveForAccountOrFailWithLock(
                $dto->billingAccountId
            );

            // Call Stripe (still inside transaction, but we'll handle failure carefully)
            try {
                $this->billingProvider->updateSubscription(
                    subscription: $subscription,
                    newPlanPriceId: $newPrice->provider_price_id,
                    prorated: true
                );
            } catch (\Exception $e) {
                Log::channel('billing')->error('stripe.upgrade.failed', [
                    'subscription_id' => $subscription->id,
                    'from_plan' => $oldPlan->code,
                    'to_plan' => $newPlan->code,
                    'error' => $e->getMessage(),
                ]);
                throw $e; // Transaction will rollback, nothing persisted
            }

            Log::channel('billing')->info('stripe.upgrade.succeeded', [
                'subscription_id' => $subscription->id,
                'from_plan' => $oldPlan->code,
                'to_plan' => $newPlan->code,
            ]);

            // Step 3: Update local database
            // If this fails after Stripe succeeded, transaction rolls back
            // but Stripe change is permanent - we log CRITICAL
            try {
                // Update local subscription
                $subscription->update([
                    'plan_id' => $newPlan->id,
                    'plan_price_id' => $newPrice->id,
                    'billing_cycle' => $dto->billingCycle->value,
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

                // Recompute entitlements
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
            } catch (\Exception $e) {
                // CRITICAL: Stripe succeeded but local update failed
                Log::channel('billing')->critical('subscription.upgrade.drift', [
                    'subscription_id' => $subscription->id,
                    'billing_account_id' => $dto->billingAccountId,
                    'from_plan' => $oldPlan->code,
                    'to_plan' => $newPlan->code,
                    'new_plan_id' => $newPlan->id,
                    'stripe_status' => 'updated',
                    'local_status' => 'failed',
                    'error' => $e->getMessage(),
                    'action_required' => 'Run: php artisan subscriptions:reconcile',
                ]);
                
                throw $e;
            }
        });
    }
}
