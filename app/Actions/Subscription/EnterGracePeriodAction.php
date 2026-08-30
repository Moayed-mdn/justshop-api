<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\DTOs\Subscription\EnterGracePeriodDTO;
use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Actions\Entitlement\RecomputeEntitlementsAction;
use App\Services\Subscription\SubscriptionStateMachine;
use App\Events\Subscription\SubscriptionStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class EnterGracePeriodAction
{
    public function __construct(
        private SubscriptionStateMachine $stateMachine,
        private RecomputeEntitlementsAction $recomputeEntitlements,
    ) {}

    /**
     * Move subscription to grace_period after Stripe retries exhausted.
     * Grace period = 72 hours to resolve payment before suspension.
     * During grace: merchant has read-only access, storefront stays live.
     */
    public function execute(EnterGracePeriodDTO $dto): Subscription
    {
        return DB::transaction(function () use ($dto) {
            $subscription = Subscription::with('billingAccount')->findOrFail($dto->subscriptionId);

            // Use state machine to enforce valid transition
            $this->stateMachine->transition(
                subscription: $subscription,
                toStatus: SubscriptionStatusEnum::GRACE_PERIOD,
                source: 'system',
                reason: $dto->reason ?? 'Payment retries exhausted, entering grace period',
                actorUserId: null,
            );

            // Set grace period expiry
            $subscription->update([
                'grace_period_ends_at' => $dto->gracePeriodEndsAt,
            ]);

            Log::channel('billing')->warning('subscription.grace_period_entered', [
                'subscription_id' => $subscription->id,
                'billing_account_id' => $subscription->billing_account_id,
                'grace_period_ends_at' => $dto->gracePeriodEndsAt->toIso8601String(),
                'reason' => $dto->reason,
            ]);

            // Recompute entitlements for all stores owned by this account
            // Status: grace_period → entitlement_status: read_only
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

            // TODO: Send grace period warning email to merchant
            // dispatch(new SendGracePeriodWarningEmail($subscription));

            return $subscription->fresh();
        });
    }
}
