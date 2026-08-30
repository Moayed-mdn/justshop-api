<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\DTOs\Subscription\ReactivateSubscriptionDTO;
use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Actions\Entitlement\RecomputeEntitlementsAction;
use App\Services\Subscription\SubscriptionStateMachine;
use App\Events\Subscription\SubscriptionStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class ReactivateSubscriptionAction
{
    public function __construct(
        private SubscriptionStateMachine $stateMachine,
        private RecomputeEntitlementsAction $recomputeEntitlements,
    ) {}

    /**
     * Reactivate subscription from past_due or grace_period status.
     * Triggered by successful payment after failure.
     * Restores full write access and storefront operation.
     */
    public function execute(ReactivateSubscriptionDTO $dto): Subscription
    {
        return DB::transaction(function () use ($dto) {
            $subscription = Subscription::with('billingAccount')->findOrFail($dto->subscriptionId);

            // Use state machine to enforce valid transition
            $this->stateMachine->transition(
                subscription: $subscription,
                toStatus: SubscriptionStatusEnum::ACTIVE,
                source: $dto->source,
                reason: $dto->reason ?? 'Subscription reactivated',
                actorUserId: $dto->actorUserId,
            );

            // Clear grace period if set
            $subscription->update([
                'grace_period_ends_at' => null,
            ]);

            Log::channel('billing')->info('subscription.reactivated', [
                'subscription_id' => $subscription->id,
                'billing_account_id' => $subscription->billing_account_id,
                'reason' => $dto->reason,
                'source' => $dto->source,
                'actor_user_id' => $dto->actorUserId,
            ]);

            // Recompute entitlements for all stores owned by this account
            // Status: active → entitlement_status: entitled (full access restored)
            $stores = $subscription->billingAccount->user->stores;
            foreach ($stores as $store) {
                $this->recomputeEntitlements->execute(
                    new RecomputeEntitlementsDTO(
                        billingAccountId: $subscription->billing_account_id,
                        storeId: $store->id,
                    )
                );
            }

            event(new SubscriptionStatusChanged($subscription));

            // TODO: Send reactivation success email
            // dispatch(new SendSubscriptionReactivatedEmail($subscription));

            return $subscription->fresh();
        });
    }
}
