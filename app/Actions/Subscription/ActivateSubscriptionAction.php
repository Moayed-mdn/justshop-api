<?php

namespace App\Actions\Subscription;

use App\Actions\Entitlement\RecomputeEntitlementsAction;
use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\DTOs\Subscription\ActivateSubscriptionDTO;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Events\Subscription\SubscriptionActivated;
use App\Models\Store;
use App\Models\Subscription;
use App\Services\Subscription\SubscriptionStateMachine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivateSubscriptionAction
{
    public function __construct(
        private SubscriptionStateMachine $stateMachine,
        private RecomputeEntitlementsAction $recomputeEntitlementsAction,
    ) {}

    /**
     * Activate a subscription after successful payment.
     * 
     * Called when:
     * - Trial converts to paid
     * - Direct subscription (skip trial)
     * - Reactivation after suspension
     */
    public function execute(ActivateSubscriptionDTO $dto): Subscription
    {
        return DB::transaction(function () use ($dto) {
            $subscription = Subscription::lockForUpdate()->findOrFail($dto->subscriptionId);

            // Transition to ACTIVE status
            $subscription = $this->stateMachine->transition(
                $subscription,
                SubscriptionStatusEnum::ACTIVE,
                source: $dto->source ?? 'system',
                reason: $dto->reason ?? 'payment_successful',
                actorUserId: $dto->actorUserId,
                payload: [
                    'provider_subscription_id' => $dto->providerSubscriptionId,
                    'activated_from' => $subscription->status->value,
                ]
            );

            // Update subscription with provider data
            $subscription->update([
                'provider_subscription_id' => $dto->providerSubscriptionId,
                'provider_status' => $dto->providerStatus ?? 'active',
                'provider_synced_at' => Carbon::now(),
                'current_period_starts_at' => $dto->currentPeriodStartsAt ?? Carbon::now(),
                'current_period_ends_at' => $dto->currentPeriodEndsAt,
                'trial_starts_at' => null, // Clear trial dates
                'trial_ends_at' => null,
            ]);

            // Recompute entitlements for all stores owned by this billing account
            $stores = Store::where('owner_id', $subscription->billingAccount->owner_user_id)->get();
            
            foreach ($stores as $store) {
                $this->recomputeEntitlementsAction->execute(
                    new RecomputeEntitlementsDTO(
                        billingAccountId: $subscription->billing_account_id,
                        storeId: $store->id,
                    )
                );
            }

            // Fire SubscriptionActivated event
            event(new SubscriptionActivated($subscription));

            Log::channel('billing')->info('subscription.activated', [
                'subscription_id' => $subscription->id,
                'billing_account_id' => $subscription->billing_account_id,
                'plan_id' => $subscription->plan_id,
                'provider_subscription_id' => $dto->providerSubscriptionId,
            ]);

            return $subscription->fresh(['plan', 'items']);
        });
    }
}
