<?php

namespace App\DTOs\Billing;

class UpdatePlanDTO
{
    public function __construct(
        public readonly int $planId,
        public readonly ?string $code = null,
        public readonly ?array $name = null,
        public readonly ?array $description = null,
        public readonly ?string $tier = null,
        public readonly ?int $tierRank = null,
        public readonly ?bool $isPublic = null,
        public readonly ?bool $isActive = null,
        public readonly ?int $trialDays = null,
        public readonly ?int $sortOrder = null,
        public readonly ?array $metadata = null,
        public readonly ?array $features = null, // [{featureKey, valueType, limitValue?, booleanValue?}]
        public readonly ?array $prices = null,   // [{billingCycle, currency, amountCents, provider?}]
    ) {}

    /**
     * Check if this update contains any breaking changes.
     */
    public function hasBreakingChanges(): bool
    {
        return $this->code !== null
            || $this->tier !== null
            || $this->tierRank !== null
            || $this->features !== null
            || $this->prices !== null;
    }
}
