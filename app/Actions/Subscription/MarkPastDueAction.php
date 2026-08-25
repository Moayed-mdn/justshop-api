<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\DTOs\Subscription\MarkPastDueDTO;
use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Enums\Subscription\SubscriptionEventTypeEnum;
use App\Models\Subscription;
use App\Actions\Entitlement\RecomputeEntitlementsAction;
use App\Services\Subscription\SubscriptionStateMachine;
use App\Events\Subscription\SubscriptionStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class MarkPastDueAction
{
    public function __construct(
        private SubscriptionStateMachine $stateMachine,
        private RecomputeEntitlementsAction $recomputeEntitlements,
    ) {}

    /**
     * Mark subscription as past_due after payment failure.
     * Stripe will retry payments automatically (smart retries).
     * During past_due: merchant has read-only access, storefront stays live.
     */
    public function execute(MarkPastDueDTO $dto): Subscription
    {
        return DB::transaction(function () use ($dto) {
            $subscription = Subscription::with('billingAccount')->findOrFail($dto->subscriptionId);

            // Use state machine to enforce valid transition
            $this->stateMachine->transition(
                subscription: $subscription,
                toStatus: SubscriptionStatusEnum::PAST_DUE,
                source: 'webhook',
                reason: $dto->reason ?? 'Payment failed, retrying',
                actorUserId: null,
            );

            // Update provider status if provided
            if ($dto->providerStatus) {
                $subscription->update(['provider_status' => $dto->providerStatus]);
            }

            Log::channel('billing')->warning('subscription.marked_past_due', [
                'subscription_id' => $subscription->id,
                'billing_account_id' => $subscription->billing_account_id,
                'reason' => $dto->reason,
                'provider_status' => $dto->providerStatus,
            ]);

            // Recompute entitlements for all stores owned by this account
            // Status: past_due → entitlement_status: read_only
            $stores = \App\Models\Store::where('owner_id', $subscription->billingAccount->owner_user_id)->get();
            foreach ($stores as $store) {
                $this->recomputeEntitlements->execute(
                    new RecomputeEntitlementsDTO(
                        billingAccountId: $subscription->billing_account_id,
                        storeId: $store->id,
                    )
                );
            }

            event(new SubscriptionStatusChanged($subscription));

            return $subscription->fresh();
        });
    }
}
