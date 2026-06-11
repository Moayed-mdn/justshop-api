<?php

namespace App\Services\Billing\Webhooks;

use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Services\Subscription\SubscriptionStateMachine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HandleSubscriptionDeleted
{
    public function __construct(
        private SubscriptionStateMachine $stateMachine,
    ) {}

    /**
     * Handle customer.subscription.deleted webhook.
     * 
     * Marks subscription as expired.
     */
    public function handle(array $event): void
    {
        $stripeSubscription = $event['data']['object'];
        $stripeSubscriptionId = $stripeSubscription['id'];

        $subscription = Subscription::where('provider_subscription_id', $stripeSubscriptionId)->first();

        if (!$subscription) {
            Log::channel('billing')->warning('webhook.subscription.deleted.not_found', [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);
            return;
        }

        // Transition to EXPIRED
        $subscription = $this->stateMachine->transition(
            $subscription,
            SubscriptionStatusEnum::EXPIRED,
            source: 'webhook',
            reason: 'stripe_subscription_deleted',
            payload: [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]
        );

        $subscription->update([
            'provider_status' => 'canceled',
            'provider_synced_at' => Carbon::now(),
            'ended_at' => Carbon::now(),
        ]);

        Log::channel('billing')->info('webhook.subscription.deleted', [
            'subscription_id' => $subscription->id,
            'stripe_subscription_id' => $stripeSubscriptionId,
        ]);
    }
}
