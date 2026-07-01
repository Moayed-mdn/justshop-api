<?php

namespace App\Actions\Address;

use App\Models\Address;
use App\Repositories\Address\AddressRepository;

class DeleteAddressAction
{
    public function __construct(
        private AddressRepository $addressRepository
    ) {}

    public function execute(Address $address, int $storeId): void
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

        $this->addressRepository->delete($address);
    }
}
