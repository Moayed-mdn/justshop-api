<?php

namespace App\DTOs\Shipping;

/**
 * DTO for creating a new shipping zone.
 */
class CreateShippingZoneDTO
{
    public function __construct(
        public readonly int $storeId,
        public readonly string $name,
        public readonly array $countries,
        public readonly ?array $regions,
        public readonly ?array $postalCodePatterns,
        public readonly bool $isActive,
    ) {}

    /**
     * Create from array (typically from a validated request).
     */
    public static function fromArray(array $data, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            name: $data['name'],
            countries: $data['countries'] ?? [],
            regions: $data['regions'] ?? null,
            postalCodePatterns: $data['postal_code_patterns'] ?? null,
            isActive: $data['is_active'] ?? true,
        );
    }

    /**
     * Convert to array for model creation.
     */
    public function toArray(): array
    {
        return [
            'store_id' => $this->storeId,
            'name' => $this->name,
            'countries' => $this->countries,
            'regions' => $this->regions,
            'postal_code_patterns' => $this->postalCodePatterns,
            'is_active' => $this->isActive,
        ];
    }
}
