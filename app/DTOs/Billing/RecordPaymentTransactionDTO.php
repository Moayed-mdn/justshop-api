<?php

namespace App\DTOs\Billing;

use Carbon\Carbon;

final readonly class RecordPaymentTransactionDTO
{
    public function __construct(
        public int $billingAccountId,
        public ?int $invoiceId,
        public ?int $subscriptionId,
        public string $provider,
        public string $providerTransactionId,
        public ?string $providerPaymentMethodId,
        public string $type,
        public string $status,
        public string $currency,
        public int $amountCents,
        public ?string $failureCode = null,
        public ?string $failureMessage = null,
        public ?Carbon $processedAt = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Create DTO from Stripe payment intent data.
     */
    public static function fromStripePaymentIntent(
        array $paymentIntent,
        int $billingAccountId,
        ?int $invoiceId = null,
        ?int $subscriptionId = null
    ): self {
        return new self(
            billingAccountId: $billingAccountId,
            invoiceId: $invoiceId,
            subscriptionId: $subscriptionId,
            provider: 'stripe',
            providerTransactionId: $paymentIntent['id'],
            providerPaymentMethodId: $paymentIntent['payment_method'] ?? null,
            type: 'charge',
            status: $paymentIntent['status'],
            currency: strtoupper($paymentIntent['currency']),
            amountCents: $paymentIntent['amount'],
            failureCode: $paymentIntent['last_payment_error']['code'] ?? null,
            failureMessage: $paymentIntent['last_payment_error']['message'] ?? null,
            processedAt: isset($paymentIntent['created']) ? Carbon::createFromTimestamp($paymentIntent['created']) : null,
            metadata: $paymentIntent['metadata'] ?? null,
        );
    }

    /**
     * Create DTO from Stripe charge data.
     */
    public static function fromStripeCharge(
        array $charge,
        int $billingAccountId,
        ?int $invoiceId = null,
        ?int $subscriptionId = null
    ): self {
        return new self(
            billingAccountId: $billingAccountId,
            invoiceId: $invoiceId,
            subscriptionId: $subscriptionId,
            provider: 'stripe',
            providerTransactionId: $charge['id'],
            providerPaymentMethodId: $charge['payment_method'] ?? null,
            type: $charge['refunded'] ? 'refund' : 'charge',
            status: $charge['status'],
            currency: strtoupper($charge['currency']),
            amountCents: $charge['amount'],
            failureCode: $charge['failure_code'] ?? null,
            failureMessage: $charge['failure_message'] ?? null,
            processedAt: isset($charge['created']) ? Carbon::createFromTimestamp($charge['created']) : null,
            metadata: $charge['metadata'] ?? null,
        );
    }
}
