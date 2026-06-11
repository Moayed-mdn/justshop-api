<?php

namespace App\Contracts\Billing;

use App\Models\BillingAccount;
use App\Models\BillingCustomer;
use App\Models\Subscription;

/**
 * Provider-agnostic billing interface.
 * 
 * Abstracts Stripe-specific logic to enable future multi-provider support
 * (Paddle, PayPal, regional processors) without touching domain logic.
 */
interface BillingProviderInterface
{
    /**
     * Create a customer in the billing provider.
     * 
     * @return array ['provider_customer_id' => string, 'metadata' => array]
     */
    public function createCustomer(BillingAccount $billingAccount): array;

    /**
     * Update customer information.
     */
    public function updateCustomer(BillingCustomer $customer, array $data): void;

    /**
     * Create a checkout session for subscription signup.
     * 
     * @param string $planPriceId The provider price ID (e.g., price_xxx)
     * @param string $successUrl Redirect URL after successful checkout
     * @param string $cancelUrl Redirect URL if checkout is abandoned
     * @return array ['session_id' => string, 'url' => string, 'expires_at' => int]
     */
    public function createCheckoutSession(
        BillingCustomer $customer,
        string $planPriceId,
        string $successUrl,
        string $cancelUrl,
        array $metadata = []
    ): array;

    /**
     * Create a billing portal session for customer self-service.
     * 
     * @return array ['session_id' => string, 'url' => string]
     */
    public function createPortalSession(BillingCustomer $customer, string $returnUrl): array;

    /**
     * Cancel a subscription (at period end or immediately).
     * 
     * @param bool $immediately If true, cancel now. If false, cancel at period end.
     */
    public function cancelSubscription(Subscription $subscription, bool $immediately = false): void;

    /**
     * Resume a canceled subscription.
     */
    public function resumeSubscription(Subscription $subscription): void;

    /**
     * Update subscription (e.g., change plan).
     * 
     * @param string $newPlanPriceId The new provider price ID
     * @param bool $prorated Whether to prorate the change
     */
    public function updateSubscription(
        Subscription $subscription,
        string $newPlanPriceId,
        bool $prorated = true
    ): void;

    /**
     * Retrieve subscription from provider.
     * 
     * @return array Provider subscription data
     */
    public function getSubscription(string $providerSubscriptionId): array;

    /**
     * Retrieve invoice from provider.
     * 
     * @return array Provider invoice data
     */
    public function getInvoice(string $providerInvoiceId): array;

    /**
     * Verify webhook signature.
     * 
     * @throws \Exception If signature is invalid
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $secret): void;

    /**
     * Parse webhook event payload.
     * 
     * @return array ['type' => string, 'data' => array, 'id' => string]
     */
    public function parseWebhookEvent(string $payload): array;
}
