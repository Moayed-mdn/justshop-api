<?php

namespace App\Services\Billing\Webhooks;

use App\Actions\Subscription\ActivateSubscriptionAction;
use App\Contracts\Billing\BillingProviderInterface;
use App\DTOs\Subscription\ActivateSubscriptionDTO;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class HandleCheckoutSessionCompleted
{
    public function __construct(
        private ActivateSubscriptionAction $activateSubscriptionAction,
        private BillingProviderInterface $billingProvider,
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
        $localSubscriptionId = $session['metadata']['local_subscription_id'] ?? null;

        if (!$billingAccountId || !$stripeSubscriptionId) {
            Log::channel('billing')->warning('webhook.checkout.missing_data', [
                'session_id' => $session['id'],
                'billing_account_id' => $billingAccountId,
                'subscription_id' => $stripeSubscriptionId,
            ]);
            return;
        }

        $subscription = null;
        $matchingStrategy = null;

        // Strategy 1: Precise matching via local_subscription_id (preferred)
        if ($localSubscriptionId) {
            $subscription = Subscription::find($localSubscriptionId);

            // Verify it belongs to the expected billing account
            if ($subscription && $subscription->billing_account_id !== (int) $billingAccountId) {
                Log::channel('billing')->warning('webhook.checkout.subscription_mismatch', [
                    'session_id' => $session['id'],
                    'local_subscription_id' => $localSubscriptionId,
                    'expected_billing_account_id' => $billingAccountId,
                    'actual_billing_account_id' => $subscription->billing_account_id,
                ]);
                $subscription = null; // Fall through to fallback
            } else {
                $matchingStrategy = 'local_subscription_id';
            }
        }

        // Strategy 2: Fallback to billing_account_id + status (backward compatibility)
        if (!$subscription) {
            $subscription = Subscription::where('billing_account_id', $billingAccountId)
                ->whereIn('status', ['trialing', 'incomplete'])
                ->orderByDesc('id')
                ->first();

            $matchingStrategy = 'billing_account_fallback';
        }

        if (!$subscription) {
            Log::channel('billing')->warning('webhook.checkout.subscription_not_found', [
                'session_id' => $session['id'],
                'billing_account_id' => $billingAccountId,
                'local_subscription_id' => $localSubscriptionId,
            ]);
            return;
        }

        Log::channel('billing')->info('webhook.checkout.subscription_matched', [
            'session_id' => $session['id'],
            'subscription_id' => $subscription->id,
            'strategy' => $matchingStrategy,
        ]);

        // Activate the subscription
        $activatedSubscription = $this->activateSubscriptionAction->execute(
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

        // Check if this subscription replaces a previous one
        if ($activatedSubscription->replaces_subscription_id) {
            try {
                $oldSubscription = Subscription::find($activatedSubscription->replaces_subscription_id);
                
                if ($oldSubscription) {
                    // Cancel in Stripe first (if linked)
                    if ($oldSubscription->provider_subscription_id) {
                        try {
                            $this->billingProvider->cancelSubscription(
                                $oldSubscription,
                                immediately: true
                            );
                        } catch (\Exception $e) {
                            Log::channel('billing')->warning('webhook.checkout.stripe_cancel_old_failed', [
                                'old_subscription_id' => $oldSubscription->id,
                                'provider_subscription_id' => $oldSubscription->provider_subscription_id,
                                'error' => $e->getMessage(),
                            ]);
                            // Continue with local cancellation even if Stripe fails
                        }
                    }

                    // Cancel locally
                    $oldSubscription->update([
                        'status' => 'canceled',
                        'canceled_at' => now(),
                    ]);

                    Log::channel('billing')->info('webhook.checkout.previous_subscription_canceled', [
                        'new_subscription_id' => $activatedSubscription->id,
                        'old_subscription_id' => $oldSubscription->id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::channel('billing')->error('webhook.checkout.previous_subscription_cancel_failed', [
                    'new_subscription_id' => $activatedSubscription->id,
                    'replaces_subscription_id' => $activatedSubscription->replaces_subscription_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Don't throw - new subscription is already active
            }
        }
    }
}
