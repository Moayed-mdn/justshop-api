<?php

namespace App\Http\Resources\Billing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the invoice resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'currency' => $this->currency,
            
            // Amounts (structured format)
            'subtotal' => [
                'cents' => $this->subtotal_cents,
                'formatted' => $this->formatAmount($this->subtotal_cents, $this->currency),
            ],
            'tax' => [
                'cents' => $this->tax_cents,
                'formatted' => $this->formatAmount($this->tax_cents, $this->currency),
            ],
            'discount' => [
                'cents' => $this->discount_cents,
                'formatted' => $this->formatAmount($this->discount_cents, $this->currency),
            ],
            'total' => [
                'cents' => $this->total_cents,
                'formatted' => $this->formatAmount($this->total_cents, $this->currency),
            ],
            'amount_paid' => [
                'cents' => $this->amount_paid_cents,
                'formatted' => $this->formatAmount($this->amount_paid_cents, $this->currency),
            ],
            'amount_due' => [
                'cents' => $this->amount_due_cents,
                'formatted' => $this->formatAmount($this->amount_due_cents, $this->currency),
            ],
            
            // Flat amounts (frontend compatibility)
            'subtotal_cents' => $this->subtotal_cents,
            'tax_cents' => $this->tax_cents,
            'discount_cents' => $this->discount_cents,
            'total_cents' => $this->total_cents,
            'amount_paid_cents' => $this->amount_paid_cents,
            'amount_due_cents' => $this->amount_due_cents,
            
            // Dates
            'period_starts_at' => $this->period_starts_at?->toIso8601String(),
            'period_ends_at' => $this->period_ends_at?->toIso8601String(),
            'issued_at' => $this->issued_at?->toIso8601String(),
            'due_at' => $this->due_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            
            // URLs
            'hosted_invoice_url' => $this->hosted_invoice_url,
            'invoice_pdf_url' => $this->invoice_pdf_url,
            
            // Relationships
            'line_items' => InvoiceLineItemResource::collection($this->whenLoaded('lineItems')),
            'payment_transactions' => PaymentTransactionResource::collection($this->whenLoaded('paymentTransactions')),
            
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * Format amount with currency symbol.
     */
    private function formatAmount(int $cents, string $currency): string
    {
        $amount = $cents / 100;
        
        return match (strtoupper($currency)) {
            'USD' => '$' . number_format($amount, 2),
            'EUR' => '€' . number_format($amount, 2),
            'GBP' => '£' . number_format($amount, 2),
            'SAR' => number_format($amount, 2) . ' ر.س',
            default => number_format($amount, 2) . ' ' . strtoupper($currency),
        };
    }
}
