<?php

namespace App\DTOs\Billing;

class CreatePlanDTO
{
    public function __construct(
        public readonly string $code,
        public readonly array $name,
        public readonly ?array $description,
        public readonly string $tier,
        public readonly int $tierRank,
        public readonly bool $isPublic,
        public readonly bool $isActive,
        public readonly int $trialDays,
        public readonly int $sortOrder,
        public readonly ?array $metadata,
        public readonly array $features, // [{featureKey, valueType, limitValue?, booleanValue?}]
        public readonly array $prices,   // [{billingCycle, currency, amountCents, provider?}]
    ) {}
}
