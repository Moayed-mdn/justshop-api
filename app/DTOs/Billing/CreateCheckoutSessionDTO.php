<?php

namespace App\DTOs\Billing;

use Illuminate\Http\Request;

class CreateCheckoutSessionDTO
{
    public function __construct(
        public int $billingAccountId,
        public int $planPriceId,
        public string $successUrl,
        public string $cancelUrl,
        public ?int $storeId = null,
    ) {}

    public static function fromRequest(Request $request, int $billingAccountId): self
    {
        return new self(
            billingAccountId: $billingAccountId,
            planPriceId: $request->integer('plan_price_id'),
            successUrl: $request->string('success_url')->toString(),
            cancelUrl: $request->string('cancel_url')->toString(),
            storeId: $request->integer('store_id') ?: null,
        );
    }
}
