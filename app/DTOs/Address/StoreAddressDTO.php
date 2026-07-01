<?php

namespace App\DTOs\Address;

use App\Http\Requests\Address\StoreAddressRequest;
use App\Enums\Address\AddressTypeEnum;

class StoreAddressDTO
{
    public function __construct(
        public int $storeId,
        public AddressTypeEnum $type,
        public string $firstName,
        public string $lastName,
        public ?string $company,
        public string $addressLine1,
        public ?string $addressLine2,
        public string $city,
        public string $state,
        public string $postalCode,
        public string $country,
        public ?string $phone,
        public bool $isDefault,
        public int $userId,
    ) {}

    public static function fromRequest(StoreAddressRequest $request, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            type: AddressTypeEnum::from($request->input('type')),
            firstName: (string) $request->input('first_name', ''),
            lastName: (string) $request->input('last_name', ''),
            company: $request->input('company'),
            addressLine1: (string) $request->input('address_line_1', ''),
            addressLine2: $request->input('address_line_2'),
            city: (string) $request->input('city', ''),
            state: (string) $request->input('state', ''),
            postalCode: (string) $request->input('postal_code', ''),
            country: (string) $request->input('country', ''),
            phone: $request->input('phone'),
            isDefault: $request->boolean('is_default', false),
            userId: $request->user()->id,
        );
    }
}
