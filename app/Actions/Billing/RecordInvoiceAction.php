<?php

namespace App\Actions\Billing;

use App\DTOs\Billing\RecordInvoiceDTO;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Repositories\Billing\InvoiceRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecordInvoiceAction
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
    ) {}

    /**
     * Record an invoice from provider webhook.
     * 
     * Creates or updates invoice and its line items.
     * Idempotent based on provider_invoice_id.
     */
    public function execute(RecordInvoiceDTO $dto): Invoice
    {
        return DB::transaction(function () use ($dto) {
            // Find or create invoice
            $invoice = $this->invoiceRepository->findByProviderInvoiceId(
                $dto->provider,
                $dto->providerInvoiceId
            );

            if ($invoice) {
                // Update existing invoice
                $invoice->update([
                    'status' => $dto->status,
                    'invoice_number' => $dto->invoiceNumber ?? $invoice->invoice_number,
                    'subtotal_cents' => $dto->subtotalCents,
                    'tax_cents' => $dto->taxCents,
                    'discount_cents' => $dto->discountCents,
                    'total_cents' => $dto->totalCents,
                    'amount_paid_cents' => $dto->amountPaidCents,
                    'amount_due_cents' => $dto->amountDueCents,
                    'paid_at' => $dto->paidAt ?? $invoice->paid_at,
                    'hosted_invoice_url' => $dto->hostedInvoiceUrl ?? $invoice->hosted_invoice_url,
                    'invoice_pdf_url' => $dto->invoicePdfUrl ?? $invoice->invoice_pdf_url,
                    'metadata' => $dto->metadata ?? $invoice->metadata,
                ]);
            } else {
                // Create new invoice
                $invoice = Invoice::create([
                    'billing_account_id' => $dto->billingAccountId,
                    'subscription_id' => $dto->subscriptionId,
                    'provider' => $dto->provider,
                    'provider_invoice_id' => $dto->providerInvoiceId,
                    'invoice_number' => $dto->invoiceNumber,
                    'status' => $dto->status,
                    'currency' => $dto->currency,
                    'subtotal_cents' => $dto->subtotalCents,
                    'tax_cents' => $dto->taxCents,
                    'discount_cents' => $dto->discountCents,
                    'total_cents' => $dto->totalCents,
                    'amount_paid_cents' => $dto->amountPaidCents,
                    'amount_due_cents' => $dto->amountDueCents,
                    'period_starts_at' => $dto->periodStartsAt,
                    'period_ends_at' => $dto->periodEndsAt,
                    'issued_at' => $dto->issuedAt,
                    'due_at' => $dto->dueAt,
                    'paid_at' => $dto->paidAt,
                    'hosted_invoice_url' => $dto->hostedInvoiceUrl,
                    'invoice_pdf_url' => $dto->invoicePdfUrl,
                    'metadata' => $dto->metadata,
                ]);
            }

            // Record line items if provided
            if ($dto->lineItems) {
                // Delete existing line items to avoid duplicates
                $invoice->lineItems()->delete();

                foreach ($dto->lineItems as $lineItem) {
                    InvoiceLineItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $lineItem['description'] ?? '',
                        'quantity' => $lineItem['quantity'] ?? 1,
                        'unit_amount_cents' => $lineItem['amount'] ?? 0,
                        'total_cents' => ($lineItem['amount'] ?? 0) * ($lineItem['quantity'] ?? 1),
                        'currency' => $dto->currency,
                        'period_starts_at' => isset($lineItem['period']['start']) 
                            ? \Carbon\Carbon::createFromTimestamp($lineItem['period']['start']) 
                            : null,
                        'period_ends_at' => isset($lineItem['period']['end']) 
                            ? \Carbon\Carbon::createFromTimestamp($lineItem['period']['end']) 
                            : null,
                        'metadata' => $lineItem['metadata'] ?? null,
                    ]);
                }
            }

            Log::channel('billing')->info('invoice.recorded', [
                'invoice_id' => $invoice->id,
                'provider_invoice_id' => $dto->providerInvoiceId,
                'status' => $dto->status,
                'total_cents' => $dto->totalCents,
            ]);

            return $invoice->fresh(['lineItems']);
        });
    }
}
