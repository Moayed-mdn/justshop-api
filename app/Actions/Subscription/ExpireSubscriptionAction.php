<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\DTOs\Subscription\ExpireSubscriptionDTO;
use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Actions\Entitlement\RecomputeEntitlementsAction;
use App\Services\Subscription\SubscriptionStateMachine;
use App\Events\Subscription\SubscriptionStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class ExpireSubscriptionAction
{
    public function __construct(
        private SubscriptionStateMachine $stateMachine,
        private RecomputeEntitlementsAction $recomputeEntitlements,
    ) {}

    /**
     * Expire subscription (terminal state).
     * Triggers when:
     * - Trial ends without conversion
     * - Grace period expires without payment
     * - Canceled subscription reaches period end
     * 
     * After expiry: entitlement_status = none, new subscription required.
     */
    public function execute(ExpireSubscriptionDTO $dto): Subscription
    {
        return DB::transaction(function () use ($dto) {
            $subscription = Subscription::with('billingAccount')->findOrFail($dto->subscriptionId);

            // Use state machine to enforce valid transition
            $this->stateMachine->transition(
                subscription: $subscription,
                toStatus: SubscriptionStatusEnum::EXPIRED,
                source: $dto->source,
                reason: $dto->reason ?? 'Subscription expired',
                actorUserId: null,
            );

            // Set ended_at timestamp
            $subscription->update([
                'ended_at' => now(),
                'grace_period_ends_at' => null, // Clear grace period
            ]);

            Log::channel('billing')->info('subscription.expired', [
                'subscription_id' => $subscription->id,
                'billing_account_id' => $subscription->billing_account_id,
                'reason' => $dto->reason,
                'ended_at' => now()->toIso8601String(),
            ]);

            // Recompute entitlements for all stores owned by this account
            // Status: expired → entitlement_status: none (full block)
            $stores = $subscription->billingAccount->owner->stores;
            foreach ($stores as $store) {
                $this->recomputeEntitlements->execute(
                    new RecomputeEntitlementsDTO(
                        billingAccountId: $subscription->billing_account_id,
                        storeId: $store->id,
                    )
                );
            }

            event(new SubscriptionStatusChanged($subscription));

            // TODO: Send expiry notice email with reactivation CTA
            // dispatch(new SendSubscriptionExpiredEmail($subscription));

            return $subscription->fresh();
        });
    }
}
