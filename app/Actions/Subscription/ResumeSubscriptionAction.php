<?php

namespace App\Actions\Subscription;

use App\Contracts\Billing\BillingProviderInterface;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Events\Subscription\SubscriptionResumed;
use App\Models\Subscription;
use App\Repositories\Subscription\SubscriptionRepository;
use App\Services\Subscription\SubscriptionStateMachine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResumeSubscriptionAction
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepo,
        private BillingProviderInterface $billingProvider,
        private SubscriptionStateMachine $stateMachine,
    ) {}

    /**
     * Resume a canceled or paused subscription.
     * 
     * Can only resume subscriptions that are:
     * - CANCELED (but still within current period)
     * - PAUSED
     */
    public function execute(int $billingAccountId, ?int $actorUserId = null): Subscription
    {
        return DB::transaction(function () use ($billingAccountId, $actorUserId) {
            $subscription = $this->subscriptionRepo->findActiveForAccountOrFail($billingAccountId);

            // Validate current status allows resumption
            if (! in_array(
                $subscription->status,
                [SubscriptionStatusEnum::CANCELED->value, SubscriptionStatusEnum::PAUSED->value]
            )) {
                throw new \DomainException(
                    "Cannot resume subscription in {$subscription->status} status"
                );
            }

            // Resume in Stripe
            $this->billingProvider->resumeSubscription($subscription);

            // Transition back to ACTIVE
            $subscription = $this->stateMachine->transition(
                $subscription,
                SubscriptionStatusEnum::ACTIVE,
                source: 'merchant',
                reason: 'resumed_by_merchant',
                actorUserId: $actorUserId,
            );

            // Clear cancellation flags
            $subscription->update([
                'cancel_at_period_end' => false,
                'canceled_at' => null,
                'collection_paused' => false,
                'provider_synced_at' => now(),
            ]);

            event(new SubscriptionResumed($subscription));

            Log::channel('billing')->info('subscription.resumed', [
                'subscription_id' => $subscription->id,
                'billing_account_id' => $billingAccountId,
            ]);

            return $subscription->fresh();
        });
    }
}
