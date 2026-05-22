<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Session;

final readonly class GuardShadowSummary
{
    public function __construct(
        public GuardShadowResolution $merchant,
        public GuardShadowResolution $customer,
        public string $futureGuardHint,
        public bool $ambiguousOwnershipPath,
        public bool $guardMismatchAnomaly,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'merchant_guard' => $this->merchant->toArray(),
            'customer_guard' => $this->customer->toArray(),
            'future_guard_hint' => $this->futureGuardHint,
            'ambiguous_ownership_path' => $this->ambiguousOwnershipPath,
            'guard_mismatch_anomaly' => $this->guardMismatchAnomaly,
        ];
    }
}
