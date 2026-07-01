<?php

namespace App\DTOs\Address;

class ValidateAddressDTO
{
    public function __construct(
        public int $storeId,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $company = null,
        public ?string $addressLine1 = null,
        public ?string $addressLine2 = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $postalCode = null,
        public ?string $country = null,
        public ?string $phone = null,
    ) {}

    public static function fromArray(array $data, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            company: $data['company'] ?? null,
            addressLine1: $data['address_line_1'] ?? null,
            addressLine2: $data['address_line_2'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            country: $data['country'] ?? null,
            phone: $data['phone'] ?? null,
        );
    }
}
