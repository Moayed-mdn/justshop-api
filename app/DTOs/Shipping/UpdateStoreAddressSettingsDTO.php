<?php

namespace App\DTOs\Shipping;

/**
 * DTO for updating store address settings.
 */
class UpdateStoreAddressSettingsDTO
{
    public function __construct(
        public readonly int $storeId,
        public readonly ?array $allowedCountries,
        public readonly ?array $requiredFields,
        public readonly ?array $validationRules,
        public readonly ?bool $requirePhone,
        public readonly ?bool $requireCompany,
        public readonly ?bool $allowPoBoxes,
    ) {}

    /**
     * Create from array (typically from a validated request).
     */
    public static function fromArray(array $data, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            allowedCountries: $data['allowed_countries'] ?? null,
            requiredFields: $data['required_fields'] ?? null,
            validationRules: $data['validation_rules'] ?? null,
            requirePhone: $data['require_phone'] ?? null,
            requireCompany: $data['require_company'] ?? null,
            allowPoBoxes: $data['allow_po_boxes'] ?? null,
        );
    }

    /**
     * Convert to array for model update (only non-null values).
     */
    public function toArray(): array
    {
        return array_filter([
            'allowed_countries' => $this->allowedCountries,
            'required_fields' => $this->requiredFields,
            'validation_rules' => $this->validationRules,
            'require_phone' => $this->requirePhone,
            'require_company' => $this->requireCompany,
            'allow_po_boxes' => $this->allowPoBoxes,
        ], fn($value) => $value !== null);
    }
}
