<?php

namespace App\Http\Resources\Billing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceLineItemResource extends JsonResource
{
    /**
     * Transform the invoice line item resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_amount' => [
                'cents' => $this->unit_amount_cents,
                'formatted' => $this->formatAmount($this->unit_amount_cents, $this->currency),
            ],
            'total' => [
                'cents' => $this->total_cents,
                'formatted' => $this->formatAmount($this->total_cents, $this->currency),
            ],
            'unit_amount_cents' => $this->unit_amount_cents,
            'total_cents' => $this->total_cents,
            'currency' => $this->currency,
            'period_starts_at' => $this->period_starts_at?->toIso8601String(),
            'period_ends_at' => $this->period_ends_at?->toIso8601String(),
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
