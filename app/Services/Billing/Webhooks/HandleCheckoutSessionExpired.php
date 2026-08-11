<?php

namespace App\Services\Billing\Webhooks;

use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Services\Subscription\SubscriptionStateMachine;
use Illuminate\Support\Facades\Log;

class HandleCheckoutSessionExpired
{
    public function __construct(
        private SubscriptionStateMachine $stateMachine,
    ) {}

    /**
     * Handle checkout.session.expired webhook.
     * 
     * Fired when a Stripe Checkout Session expires (24h after creation) without completion.
     */
    public function handle(array $event): void
    {
        $session = $event['data']['object'];

        // Only handle subscription mode checkouts
        if ($session['mode'] !== 'subscription') {
            Log::channel('billing')->info('webhook.checkout_expired.non_subscription', [
                'session_id' => $session['id'],
                'mode' => $session['mode'],
            ]);
            return;
        }

        $localSubscriptionId = $session['metadata']['local_subscription_id'] ?? null;

        if (!$localSubscriptionId) {
            Log::channel('billing')->warning('webhook.checkout_expired.missing_local_subscription_id', [
                'session_id' => $session['id'],
            ]);
            return;
        }

        $subscription = Subscription::find($localSubscriptionId);

        if (!$subscription) {
            Log::channel('billing')->warning('webhook.checkout_expired.subscription_not_found', [
                'session_id' => $session['id'],
                'local_subscription_id' => $localSubscriptionId,
            ]);
            return;
        }

        // Only expire if still in incomplete status
        if ($subscription->status->value !== 'incomplete') {
            Log::channel('billing')->info('webhook.checkout_expired.subscription_already_moved', [
                'session_id' => $session['id'],
                'subscription_id' => $subscription->id,
                'current_status' => $subscription->status->value,
            ]);
            return;
        }

        // Use state machine to transition to EXPIRED
        $this->stateMachine->transition(
            $subscription,
            SubscriptionStatusEnum::EXPIRED,
            source: 'webhook',
            reason: 'checkout_session_expired',
            payload: [
                'session_id' => $session['id'],
            ]
        );

        // Update ended_at timestamp
        $subscription->update(['ended_at' => now()]);

        Log::channel('billing')->info('webhook.checkout_expired.subscription_expired', [
            'session_id' => $session['id'],
            'subscription_id' => $subscription->id,
        ]);
    }
}
