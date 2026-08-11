<?php

namespace App\DTOs\Billing;

use Carbon\Carbon;

final readonly class RecordInvoiceDTO
{
    public function __construct(
        public int $billingAccountId,
        public ?int $subscriptionId,
        public string $provider,
        public string $providerInvoiceId,
        public ?string $invoiceNumber,
        public string $status,
        public string $currency,
        public int $subtotalCents,
        public int $taxCents,
        public int $discountCents,
        public int $totalCents,
        public int $amountPaidCents,
        public int $amountDueCents,
        public ?Carbon $periodStartsAt,
        public ?Carbon $periodEndsAt,
        public ?Carbon $issuedAt,
        public ?Carbon $dueAt,
        public ?Carbon $paidAt,
        public ?string $hostedInvoiceUrl,
        public ?string $invoicePdfUrl,
        public ?array $metadata = null,
        public ?array $lineItems = null,
    ) {}

    /**
     * Create DTO from Stripe invoice data.
     */
    public static function fromStripeInvoice(array $stripeInvoice, int $billingAccountId, ?int $subscriptionId = null): self
    {
        // Extract period dates from line items (actual subscription period)
        // instead of invoice-level period (which is just the billing moment).
        // For subscription invoices, the first line item period represents the
        // actual service period being billed.
        $periodStart = $stripeInvoice['period_start'] ?? null;
        $periodEnd = $stripeInvoice['period_end'] ?? null;
        
        if (isset($stripeInvoice['lines']['data'][0]['period'])) {
            $lineItemPeriod = $stripeInvoice['lines']['data'][0]['period'];
            $periodStart = $lineItemPeriod['start'] ?? $periodStart;
            $periodEnd = $lineItemPeriod['end'] ?? $periodEnd;
        }
        
        return new self(
            billingAccountId: $billingAccountId,
            subscriptionId: $subscriptionId,
            provider: 'stripe',
            providerInvoiceId: $stripeInvoice['id'],
            invoiceNumber: $stripeInvoice['number'] ?? null,
            status: $stripeInvoice['status'],
            currency: strtoupper($stripeInvoice['currency']),
            subtotalCents: $stripeInvoice['subtotal'] ?? 0,
            taxCents: $stripeInvoice['tax'] ?? 0,
            discountCents: abs($stripeInvoice['total_discount_amounts'][0]['amount'] ?? 0),
            totalCents: $stripeInvoice['total'],
            amountPaidCents: $stripeInvoice['amount_paid'],
            amountDueCents: $stripeInvoice['amount_due'],
            periodStartsAt: $periodStart ? Carbon::createFromTimestamp($periodStart) : null,
            periodEndsAt: $periodEnd ? Carbon::createFromTimestamp($periodEnd) : null,
            issuedAt: isset($stripeInvoice['created']) ? Carbon::createFromTimestamp($stripeInvoice['created']) : null,
            dueAt: isset($stripeInvoice['due_date']) ? Carbon::createFromTimestamp($stripeInvoice['due_date']) : null,
            paidAt: isset($stripeInvoice['status_transitions']['paid_at']) 
                ? Carbon::createFromTimestamp($stripeInvoice['status_transitions']['paid_at']) 
                : null,
            hostedInvoiceUrl: $stripeInvoice['hosted_invoice_url'] ?? null,
            invoicePdfUrl: $stripeInvoice['invoice_pdf'] ?? null,
            metadata: $stripeInvoice['metadata'] ?? null,
            lineItems: $stripeInvoice['lines']['data'] ?? null,
        );
    }
}
