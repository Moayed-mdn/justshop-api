<?php

namespace App\Actions\Address;

use App\DTOs\Address\UpdateAddressDTO;
use App\Models\Address;
use App\Repositories\Address\AddressRepository;

class UpdateAddressAction
{
    public function __construct(
        private AddressRepository $addressRepository
    ) {}

    public function execute(Address $address, UpdateAddressDTO $dto): Address
    {
        if ($dto->isDefault) {
            $this->addressRepository->clearDefaultsForAddressType(
                $address->user_id,
                $address->type,
                $address->id,
                $dto->storeId
            );
        }

        $updated = $this->addressRepository->update($address, [
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
}
