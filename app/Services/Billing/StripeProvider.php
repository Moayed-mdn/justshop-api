<?php

namespace App\Services\Billing;

use App\Contracts\Billing\BillingProviderInterface;
use App\Models\BillingAccount;
use App\Models\BillingCustomer;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\StripeClient;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeProvider implements BillingProviderInterface
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe customer.
     */
    public function createCustomer(BillingAccount $billingAccount): array
    {
        $user = $billingAccount->owner;

        $customer = $this->stripe->customers->create([
            'email' => $billingAccount->billing_email,
            'name' => $billingAccount->legal_name ?? $user->name,
            'metadata' => [
                'billing_account_id' => $billingAccount->id,
                'owner_user_id' => $billingAccount->owner_user_id,
                'environment' => config('app.env'),
            ],
        ]);

        Log::channel('billing')->info('stripe.customer.created', [
            'billing_account_id' => $billingAccount->id,
            'stripe_customer_id' => $customer->id,
        ]);

        return [
            'provider_customer_id' => $customer->id,
            'metadata' => $customer->metadata->toArray(),
        ];
    }

    /**
     * Update Stripe customer.
     */
    public function updateCustomer(BillingCustomer $customer, array $data): void
    {
        $this->stripe->customers->update($customer->provider_customer_id, $data);

        Log::channel('billing')->info('stripe.customer.updated', [
            'billing_account_id' => $customer->billing_account_id,
            'stripe_customer_id' => $customer->provider_customer_id,
        ]);
    }

    /**
     * Create Stripe Checkout Session.
     */
    public function createCheckoutSession(
        BillingCustomer $customer,
        string $planPriceId,
        string $successUrl,
        string $cancelUrl,
        array $metadata = [],
        ?int $trialDays = null
    ): array {
        // Ensure success_url includes the session_id parameter
        // Stripe will replace {CHECKOUT_SESSION_ID} with the actual session ID
        if (!str_contains($successUrl, '{CHECKOUT_SESSION_ID}')) {
            $successUrl .= (str_contains($successUrl, '?') ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}';
        }

        $sessionParams = [
            'customer' => $customer->provider_customer_id,
            'mode' => 'subscription',
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price' => $planPriceId,
                    'quantity' => 1,
                ],
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => array_merge([
                'billing_account_id' => $customer->billing_account_id,
                'environment' => config('app.env'),
            ], $metadata),
            'subscription_data' => [
                'metadata' => array_merge([
                    'billing_account_id' => $customer->billing_account_id,
                ], $metadata),
            ],
        ];

        // Add trial period if specified and > 0
        if ($trialDays !== null && $trialDays > 0) {
            $sessionParams['subscription_data']['trial_period_days'] = $trialDays;
        }

        $session = $this->stripe->checkout->sessions->create($sessionParams);

        Log::channel('billing')->info('stripe.checkout_session.created', [
            'billing_account_id' => $customer->billing_account_id,
            'session_id' => $session->id,
            'plan_price_id' => $planPriceId,
            'trial_days' => $trialDays,
        ]);

        return [
            'session_id' => $session->id,
            'url' => $session->url,
            'expires_at' => $session->expires_at,
        ];
    }

    /**
     * Create Stripe Billing Portal Session.
     */
    public function createPortalSession(BillingCustomer $customer, string $returnUrl): array
    {
        $session = $this->stripe->billingPortal->sessions->create([
            'customer' => $customer->provider_customer_id,
            'return_url' => $returnUrl,
        ]);

        Log::channel('billing')->info('stripe.portal_session.created', [
            'billing_account_id' => $customer->billing_account_id,
            'session_id' => $session->id,
        ]);

        return [
            'session_id' => $session->id,
            'url' => $session->url,
        ];
    }

    /**
     * Cancel Stripe subscription.
     */
    public function cancelSubscription(Subscription $subscription, bool $immediately = false): void
    {
        if ($immediately) {
            $this->stripe->subscriptions->cancel($subscription->provider_subscription_id);
        } else {
            $this->stripe->subscriptions->update($subscription->provider_subscription_id, [
                'cancel_at_period_end' => true,
            ]);
        }

        Log::channel('billing')->info('stripe.subscription.canceled', [
            'subscription_id' => $subscription->id,
            'stripe_subscription_id' => $subscription->provider_subscription_id,
            'immediately' => $immediately,
        ]);
    }

    /**
     * Resume Stripe subscription.
     */
    public function resumeSubscription(Subscription $subscription): void
    {
        $this->stripe->subscriptions->update($subscription->provider_subscription_id, [
            'cancel_at_period_end' => false,
        ]);

        Log::channel('billing')->info('stripe.subscription.resumed', [
            'subscription_id' => $subscription->id,
            'stripe_subscription_id' => $subscription->provider_subscription_id,
        ]);
    }

    /**
     * Update Stripe subscription (change plan).
     */
    public function updateSubscription(
        Subscription $subscription,
        string $newPlanPriceId,
        bool $prorated = true
    ): void {
        // If no provider subscription ID, this is a trial that never went through Stripe
        // We can't update a subscription that doesn't exist in Stripe
        if (empty($subscription->provider_subscription_id)) {
            throw new \DomainException(
                'Cannot update subscription: No Stripe subscription found. Please complete checkout first.'
            );
        }

        $stripeSubscription = $this->stripe->subscriptions->retrieve(
            $subscription->provider_subscription_id
        );

        $this->stripe->subscriptions->update($subscription->provider_subscription_id, [
            'items' => [
                [
                    'id' => $stripeSubscription->items->data[0]->id,
                    'price' => $newPlanPriceId,
                ],
            ],
            'proration_behavior' => $prorated ? 'always_invoice' : 'none',
        ]);

        Log::channel('billing')->info('stripe.subscription.updated', [
            'subscription_id' => $subscription->id,
            'stripe_subscription_id' => $subscription->provider_subscription_id,
            'new_plan_price_id' => $newPlanPriceId,
            'prorated' => $prorated,
        ]);
    }

    /**
     * Retrieve Stripe subscription.
     */
    public function getSubscription(string $providerSubscriptionId): array
    {
        $subscription = $this->stripe->subscriptions->retrieve($providerSubscriptionId);

        return $subscription->toArray();
    }

    /**
     * Retrieve Stripe invoice.
     */
    public function getInvoice(string $providerInvoiceId): array
    {
        $invoice = $this->stripe->invoices->retrieve($providerInvoiceId);

        return $invoice->toArray();
    }

    /**
     * Verify Stripe webhook signature.
     * 
     * @throws SignatureVerificationException
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $secret): void
    {
        Webhook::constructEvent($payload, $signature, $secret);
    }

    /**
     * Parse Stripe webhook event.
     */
    public function parseWebhookEvent(string $payload): array
    {
        $event = json_decode($payload, true);

        return [
            'id' => $event['id'],
            'type' => $event['type'],
            'data' => $event['data']['object'] ?? [],
        ];
    }

    /**
     * Create a Stripe Product (if needed) and a Price for a plan.
     */
    public function createPrice(
        \App\Models\Plan $plan,
        \App\Models\PlanPrice $planPrice
    ): array {
        // Get or create Stripe Product
        if ($plan->provider_product_id) {
            $productId = $plan->provider_product_id;
        } else {
            $product = $this->stripe->products->create([
                'name' => $plan->name['en'] ?? $plan->code,
                'description' => $plan->description['en'] ?? null,
                'metadata' => [
                    'plan_id' => $plan->id,
                    'plan_code' => $plan->code,
                    'environment' => config('app.env'),
                ],
            ]);
            $productId = $product->id;

            // Update plan with product ID
            $plan->update(['provider_product_id' => $productId]);

            Log::channel('billing')->info('stripe.product.created', [
                'plan_id' => $plan->id,
                'stripe_product_id' => $productId,
            ]);
        }

        // Create Stripe Price
        $price = $this->stripe->prices->create([
            'product' => $productId,
            'unit_amount' => $planPrice->amount_cents,
            'currency' => strtolower($planPrice->currency),
            'recurring' => [
                'interval' => $planPrice->billing_cycle === 'annual' ? 'year' : 'month',
            ],
            'metadata' => [
                'plan_id' => $plan->id,
                'plan_price_id' => $planPrice->id,
                'plan_code' => $plan->code,
                'environment' => config('app.env'),
            ],
        ]);

        Log::channel('billing')->info('stripe.price.created', [
            'plan_id' => $plan->id,
            'plan_price_id' => $planPrice->id,
            'stripe_price_id' => $price->id,
            'amount_cents' => $planPrice->amount_cents,
            'billing_cycle' => $planPrice->billing_cycle,
        ]);

        return [
            'provider_product_id' => $productId,
            'provider_price_id' => $price->id,
        ];
    }

    /**
     * Archive a Stripe Price so it can no longer be used for new subscriptions.
     */
    public function archivePrice(string $providerPriceId): void
    {
        $this->stripe->prices->update($providerPriceId, [
            'active' => false,
        ]);

        Log::channel('billing')->info('stripe.price.archived', [
            'stripe_price_id' => $providerPriceId,
        ]);
    }
}
