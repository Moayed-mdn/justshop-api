<?php

namespace App\DTOs\Shipping;

/**
 * DTO for updating an existing shipping zone.
 */
class UpdateShippingZoneDTO
{
    public function __construct(
        public readonly int $storeId,
        public readonly ?string $name,
        public readonly ?array $countries,
        public readonly ?array $regions,
        public readonly ?array $postalCodePatterns,
        public readonly ?bool $isActive,
    ) {}

    /**
     * Create from array (typically from a validated request).
     */
    public static function fromArray(array $data, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            name: $data['name'] ?? null,
            countries: $data['countries'] ?? null,
            regions: $data['regions'] ?? null,
            postalCodePatterns: $data['postal_code_patterns'] ?? null,
            isActive: $data['is_active'] ?? null,
        );
    }

    /**
     * Convert to array for model update (only non-null values).
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'countries' => $this->countries,
            'regions' => $this->regions,
            'postal_code_patterns' => $this->postalCodePatterns,
            'is_active' => $this->isActive,
        ], fn($value) => $value !== null);
    }
}
