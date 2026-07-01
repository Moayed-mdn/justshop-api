<?php

namespace App\Actions\Address;

use App\DTOs\Address\ValidateAddressDTO;
use App\Enums\Address\AddressValidationStatusEnum;
use App\Models\Store;
use App\Services\StoreAddressSettingsService;

class ValidateAddressAction
{
    public function __construct(
        private StoreAddressSettingsService $storeAddressSettingsService,
    ) {}

    public function execute(ValidateAddressDTO $dto): array
    {
        $store = Store::findOrFail($dto->storeId);
        $normalizedAddress = $this->storeAddressSettingsService->normalizeAddressData([
            'first_name' => $dto->firstName,
            'last_name' => $dto->lastName,
            'company' => $dto->company,
            'address_line_1' => $dto->addressLine1,
            'address_line_2' => $dto->addressLine2,
            'city' => $dto->city,
            'state' => $dto->state,
            'postal_code' => $dto->postalCode,
            'country' => $dto->country,
            'phone' => $dto->phone,
        ]);
        $messages = $this->storeAddressSettingsService->validateAddressForStore($store, $normalizedAddress);
        $errors = $this->storeAddressSettingsService->formatValidationIssues($messages);
        $warnings = [];
        $suggestions = [];
        $status = empty($errors)
            ? AddressValidationStatusEnum::VALID
            : AddressValidationStatusEnum::ERROR;

        return [
            'status' => $status->value,
            'errors' => $errors,
            'warnings' => $warnings,
            'suggestions' => $suggestions,
        ];
    }
}
