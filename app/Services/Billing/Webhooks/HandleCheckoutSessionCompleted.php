<?php

namespace App\Services\Billing\Webhooks;

use App\Actions\Subscription\ActivateSubscriptionAction;
use App\DTOs\Subscription\ActivateSubscriptionDTO;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class HandleCheckoutSessionCompleted
{
    public function __construct(
        private ActivateSubscriptionAction $activateSubscriptionAction,
    ) {}

    /**
     * Handle checkout.session.completed webhook.
     * 
     * Fired when customer successfully completes Stripe Checkout.
     */
    public function handle(array $event): void
    {
        $session = $event['data']['object'];

        // Only handle subscription mode checkouts
        if ($session['mode'] !== 'subscription') {
            Log::channel('billing')->info('webhook.checkout.non_subscription', [
                'session_id' => $session['id'],
                'mode' => $session['mode'],
            ]);
            return;
        }

        $billingAccountId = $session['metadata']['billing_account_id'] ?? null;
        $stripeSubscriptionId = $session['subscription'] ?? null;

        if (!$billingAccountId || !$stripeSubscriptionId) {
            Log::channel('billing')->warning('webhook.checkout.missing_data', [
                'session_id' => $session['id'],
                'billing_account_id' => $billingAccountId,
                'subscription_id' => $stripeSubscriptionId,
            ]);
            return;
        }

        // Find the subscription by billing_account_id
        // (Trial subscription already exists from Phase 2)
        $subscription = Subscription::where('billing_account_id', $billingAccountId)
            ->whereIn('status', ['trialing', 'incomplete'])
            ->first();

        if (!$subscription) {
            Log::channel('billing')->warning('webhook.checkout.subscription_not_found', [
                'session_id' => $session['id'],
                'billing_account_id' => $billingAccountId,
            ]);
            return;
        }

        // Activate the subscription
        $this->activateSubscriptionAction->execute(
            new ActivateSubscriptionDTO(
                subscriptionId: $subscription->id,
                providerSubscriptionId: $stripeSubscriptionId,
                providerStatus: 'active',
                source: 'webhook',
                reason: 'checkout_completed',
            )
        );

        Log::channel('billing')->info('webhook.checkout.activated', [
            'session_id' => $session['id'],
            'subscription_id' => $subscription->id,
            'stripe_subscription_id' => $stripeSubscriptionId,
        ]);
    }
}
