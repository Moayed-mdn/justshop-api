<?php

namespace App\Http\Resources\Billing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTransactionResource extends JsonResource
{
    /**
     * Transform the payment transaction resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'amount' => [
                'cents' => $this->amount_cents,
                'formatted' => $this->formatAmount($this->amount_cents, $this->currency),
            ],
            'currency' => $this->currency,
            'failure_code' => $this->failure_code,
            'failure_message' => $this->failure_message,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
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
