<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Address\StoreAddressDTO;
use App\DTOs\Address\UpdateAddressDTO;
use App\Enums\Address\AddressTypeEnum;
use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;
use App\Models\Address;
use App\Models\Store;
use App\Repositories\Address\AddressRepository;
use Illuminate\Database\Eloquent\Collection;

class AddressService
{
    public function __construct(
        private AddressRepository $addressRepository,
        private StoreAddressSettingsService $storeAddressSettingsService,
    ) {}

    public function getUserAddresses(int $storeId, int $userId, null|string|AddressTypeEnum $type = null): Collection
    {
        return $this->addressRepository->getByUser($userId, $storeId, $type);
    }

    public function storeAddress(int $storeId, StoreAddressDTO $dto): Address
    {
        $store = Store::findOrFail($storeId);
        $normalizedAddress = $this->validateAndNormalizeAddress($store, [
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

        $data = [
            'user_id' => $dto->userId,
            'first_name' => $normalizedAddress['first_name'],
            'last_name' => $normalizedAddress['last_name'],
            'company' => $normalizedAddress['company'],
            'phone' => $normalizedAddress['phone'],
            'country' => $normalizedAddress['country'],
            'state' => $normalizedAddress['state'],
            'city' => $normalizedAddress['city'],
            'address_line_1' => $normalizedAddress['address_line_1'],
            'address_line_2' => $normalizedAddress['address_line_2'],
            'postal_code' => $normalizedAddress['postal_code'],
            'type' => $dto->type,
        ];

        if ($dto->isDefault) {
            $this->addressRepository->clearDefaultsForAddressType($dto->userId, $dto->type, null, $storeId);
        }

        $address = $this->addressRepository->create($data, $storeId);

        if ($dto->isDefault) {
            $this->addressRepository->setDefaultForAddressType($dto->userId, $dto->type, $address->id, $storeId);
            return $address->fresh();
        }

        return $address;
    }

    public function updateAddress(Address $address, UpdateAddressDTO $dto): Address
    {
        $store = Store::findOrFail($dto->storeId);
        $normalizedAddress = $this->validateAndNormalizeAddress($store, [
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

        $data = [
            'first_name' => $normalizedAddress['first_name'],
            'last_name' => $normalizedAddress['last_name'],
            'company' => $normalizedAddress['company'],
            'phone' => $normalizedAddress['phone'],
            'country' => $normalizedAddress['country'],
            'state' => $normalizedAddress['state'],
            'city' => $normalizedAddress['city'],
            'address_line_1' => $normalizedAddress['address_line_1'],
            'address_line_2' => $normalizedAddress['address_line_2'],
            'postal_code' => $normalizedAddress['postal_code'],
        ];

        if ($dto->isDefault) {
            $this->addressRepository->clearDefaultsForAddressType(
                $address->user_id,
                $address->type,
                $address->id,
                $dto->storeId
            );
        }

        $updated = $this->addressRepository->update($address, $data);

        if ($dto->isDefault) {
            $this->addressRepository->setDefaultForAddressType(
                $address->user_id,
                $address->type,
                $address->id,
                $dto->storeId
            );
            return $updated->fresh();
        }

        return $updated;
    }

    public function deleteAddress(Address $address, int $storeId): bool
    {
        if ($address->is_default_shipping) {
            $newShippingDefault = $this->addressRepository->getNextShippingDefaultCandidate(
                $address->user_id,
                $address->id,
                $storeId
            );

            if ($newShippingDefault) {
                $this->addressRepository->setDefaultShipping($address->user_id, $newShippingDefault->id, $storeId);
            }
        }

        if ($address->is_default_billing) {
            $newBillingDefault = $this->addressRepository->getNextBillingDefaultCandidate(
                $address->user_id,
                $address->id,
                $storeId
            );

            if ($newBillingDefault) {
                $this->addressRepository->setDefaultBilling($address->user_id, $newBillingDefault->id, $storeId);
            }
        }

        return $this->addressRepository->delete($address);
    }

    public function setAsDefault(Address $address, int $storeId): void
    {
        $this->addressRepository->setDefaultForAddressType(
            $address->user_id,
            $address->type,
            $address->id,
            $storeId
        );
    }

    private function validateAndNormalizeAddress(Store $store, array $addressData): array
    {
        $normalizedAddress = $this->storeAddressSettingsService->normalizeAddressData($addressData);
        $validationMessages = $this->storeAddressSettingsService->validateAddressForStore(
            $store,
            $normalizedAddress
        );

        if (!empty($validationMessages)) {
            throw new BaseApiException(
                message: 'Address validation failed',
                statusCode: 422,
                errorCode: ErrorCode::VAL_001->value,
                errors: $this->storeAddressSettingsService->formatValidationErrors($validationMessages)
            );
        }

        return $normalizedAddress;
    }
}
