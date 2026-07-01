<?php

namespace App\DTOs\Shipping;

/**
 * DTO for assigning a shipping method to a zone with optional price override.
 */
class AssignMethodToZoneDTO
{
    public function __construct(
        public readonly int $storeId,
        public readonly int $zoneId,
        public readonly int $methodId,
        public readonly ?float $priceOverride,
    ) {}

    /**
     * Create from array (typically from a validated request).
     */
    public static function fromArray(array $data, int $storeId, int $zoneId): self
    {
        return new self(
            storeId: $storeId,
            zoneId: $zoneId,
            methodId: (int) $data['method_id'],
            priceOverride: isset($data['price_override']) ? (float) $data['price_override'] : null,
        );
    }
}
