<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\DTOs\Subscription\SuspendSubscriptionDTO;
use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Actions\Entitlement\RecomputeEntitlementsAction;
use App\Services\Subscription\SubscriptionStateMachine;
use App\Events\Subscription\SubscriptionStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class SuspendSubscriptionAction
{
    public function __construct(
        private SubscriptionStateMachine $stateMachine,
        private RecomputeEntitlementsAction $recomputeEntitlements,
    ) {}

    /**
     * Suspend subscription (PAUSED status).
     * Triggers when grace period expires without payment.
     * During suspension: no write access, no read access, storefront offline.
     */
    public function execute(SuspendSubscriptionDTO $dto): Subscription
    {
        return DB::transaction(function () use ($dto) {
            $subscription = Subscription::with('billingAccount')->findOrFail($dto->subscriptionId);

            // Use state machine to enforce valid transition
            $this->stateMachine->transition(
                subscription: $subscription,
                toStatus: SubscriptionStatusEnum::PAUSED,
                source: $dto->source,
                reason: $dto->reason ?? 'Subscription suspended',
                actorUserId: $dto->actorUserId,
            );

            Log::channel('billing')->error('subscription.suspended', [
                'subscription_id' => $subscription->id,
                'billing_account_id' => $subscription->billing_account_id,
                'reason' => $dto->reason,
                'source' => $dto->source,
                'actor_user_id' => $dto->actorUserId,
            ]);

            // Recompute entitlements for all stores owned by this account
            // Status: paused → entitlement_status: restricted (full block)
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

            // TODO: Send suspension notice email
            // dispatch(new SendSubscriptionSuspendedEmail($subscription));

            return $subscription->fresh();
        });
    }
}
