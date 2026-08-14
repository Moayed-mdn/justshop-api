<?php

namespace App\Services\Billing;

use App\Contracts\Billing\BillingProviderInterface;
use App\Models\BillingAccount;
use App\Models\BillingCustomer;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class TestBillingProvider implements BillingProviderInterface
{
    public function createCustomer(BillingAccount $billingAccount): array
    {
        Log::channel('billing')->info('test.provider.customer_created', [
            'billing_account_id' => $billingAccount->id,
        ]);

        return [
            'provider_customer_id' => 'test_customer_' . $billingAccount->id,
            'metadata' => [],
        ];
    }

    public function updateCustomer(BillingCustomer $customer, array $data): void
    {
        Log::channel('billing')->info('test.provider.customer_updated', [
            'billing_account_id' => $customer->billing_account_id,
            'data' => $data,
        ]);
    }

    public function createCheckoutSession(
        BillingCustomer $customer,
        string $planPriceId,
        string $successUrl,
        string $cancelUrl,
        array $metadata = [],
        ?int $trialDays = null
    ): array {
        Log::channel('billing')->info('test.provider.checkout_created', [
            'billing_account_id' => $customer->billing_account_id,
            'plan_price_id' => $planPriceId,
            'trial_days' => $trialDays,
            'metadata' => $metadata,
        ]);

        $sessionId = 'test_session_' . uniqid();

        // Append session ID to success URL (like Stripe does)
        $successUrl .= (str_contains($successUrl, '?') ? '&' : '?') . 'session_id=' . $sessionId;

        return [
            'session_id' => $sessionId,
            'url' => $successUrl,
            'expires_at' => now()->addHour()->timestamp,
        ];
    }

    public function createPortalSession(BillingCustomer $customer, string $returnUrl): array
    {
        Log::channel('billing')->info('test.provider.portal_created', [
            'billing_account_id' => $customer->billing_account_id,
        ]);

        return [
            'session_id' => 'test_portal_' . uniqid(),
            'url' => $returnUrl,
        ];
    }

    public function cancelSubscription(Subscription $subscription, bool $immediately = false): void
    {
        Log::channel('billing')->info('test.provider.subscription_canceled', [
            'subscription_id' => $subscription->id,
            'immediately' => $immediately,
        ]);
    }

    public function resumeSubscription(Subscription $subscription): void
    {
        Log::channel('billing')->info('test.provider.subscription_resumed', [
            'subscription_id' => $subscription->id,
        ]);
    }

    public function updateSubscription(
        Subscription $subscription,
        string $newPlanPriceId,
        bool $prorated = true
    ): void {
        Log::channel('billing')->info('test.provider.subscription_updated', [
            'subscription_id' => $subscription->id,
            'new_plan_price_id' => $newPlanPriceId,
            'prorated' => $prorated,
        ]);
    }

    public function getSubscription(string $providerSubscriptionId): array
    {
        Log::channel('billing')->info('test.provider.subscription_retrieved', [
            'provider_subscription_id' => $providerSubscriptionId,
        ]);

        return [
            'id' => $providerSubscriptionId,
            'status' => 'active',
        ];
    }

    public function getInvoice(string $providerInvoiceId): array
    {
        Log::channel('billing')->info('test.provider.invoice_retrieved', [
            'provider_invoice_id' => $providerInvoiceId,
        ]);

        return [
            'id' => $providerInvoiceId,
            'status' => 'paid',
        ];
    }

    public function verifyWebhookSignature(string $payload, string $signature, string $secret): void
    {
        // No-op for test provider
    }

    public function parseWebhookEvent(string $payload): array
    {
        $data = json_decode($payload, true);
        return [
            'id' => $data['id'] ?? 'test_event_' . uniqid(),
            'type' => $data['type'] ?? 'test.event',
            'data' => $data['data'] ?? [],
        ];
    }

    public function createPrice(
        \App\Models\Plan $plan,
        \App\Models\PlanPrice $planPrice
    ): array {
        Log::channel('billing')->info('test.provider.price_created', [
            'plan_id' => $plan->id,
            'plan_price_id' => $planPrice->id,
            'amount_cents' => $planPrice->amount_cents,
        ]);

        $productId = $plan->provider_product_id ?? 'test_product_' . $plan->id;
        $priceId = 'test_price_' . $planPrice->id . '_' . uniqid();

        return [
            'provider_product_id' => $productId,
            'provider_price_id' => $priceId,
        ];
    }

    public function archivePrice(string $providerPriceId): void
    {
        Log::channel('billing')->info('test.provider.price_archived', [
            'provider_price_id' => $providerPriceId,
        ]);
    }
}
