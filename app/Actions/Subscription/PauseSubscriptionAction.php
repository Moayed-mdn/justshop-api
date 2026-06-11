<?php

namespace App\Actions\Subscription;

use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Events\Subscription\SubscriptionPaused;
use App\Models\Subscription;
use App\Repositories\Subscription\SubscriptionRepository;
use App\Services\Subscription\SubscriptionStateMachine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PauseSubscriptionAction
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepo,
        private SubscriptionStateMachine $stateMachine,
    ) {}

    /**
     * Pause subscription (Stripe pause collection feature).
     * 
     * This is a Stripe-specific feature that stops billing without canceling.
     * Access is immediately restricted.
     */
    public function execute(int $billingAccountId, ?int $actorUserId = null): Subscription
    {
        return DB::transaction(function () use ($billingAccountId, $actorUserId) {
            $subscription = $this->subscriptionRepo->findActiveForAccountOrFail($billingAccountId);

            // Transition to PAUSED
            $subscription = $this->stateMachine->transition(
                $subscription,
                SubscriptionStatusEnum::PAUSED,
                source: 'merchant',
                reason: 'paused_by_merchant',
                actorUserId: $actorUserId,
            );

            $subscription->update([
                'collection_paused' => true,
                'provider_synced_at' => now(),
            ]);

            event(new SubscriptionPaused($subscription));

            Log::channel('billing')->info('subscription.paused', [
                'subscription_id' => $subscription->id,
                'billing_account_id' => $billingAccountId,
            ]);

            return $subscription->fresh();
        });
    }
}
