<?php

namespace App\DTOs\Billing;

class CreatePlanPriceDTO
{
    public function __construct(
        public readonly int $planId,
        public readonly string $billingCycle,
        public readonly string $currency,
        public readonly int $amountCents,
        public readonly string $provider = 'stripe',
    ) {}
}
