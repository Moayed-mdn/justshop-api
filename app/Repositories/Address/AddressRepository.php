<?php

namespace App\Repositories\Address;

use App\Models\Address;
use App\Enums\Address\AddressTypeEnum;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class AddressRepository extends BaseRepository
{
    protected function modelClass(): string
    {
        return Address::class;
    }

    public function getByUser(int $userId, int $storeId, null|string|AddressTypeEnum $type = null): Collection
    {
        $query = $this->scopedQuery()
            ->where('user_id', $userId);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->get();
    }

    public function find(int $id, int $storeId): Address
    {
        return $this->scopedQuery()->findOrFail($id);
    }

    public function create(array $data, int $storeId): Address
    {
        $data['store_id'] = $this->getCurrentStoreId() ?? $storeId;
        return Address::create($data);
    }

    public function update(Address $address, array $data): Address
    {
        $address->update($data);
        return $address->fresh();
    }

    public function delete(Address $address): bool
    {
        return (bool) $address->delete();
    }

    public function setDefaultForAddressType(
        int $userId,
        string|AddressTypeEnum $type,
        int $addressId,
        int $storeId
    ): void
    {
        $resolvedType = $type instanceof AddressTypeEnum ? $type : AddressTypeEnum::from($type);

        if (in_array($resolvedType, [AddressTypeEnum::SHIPPING, AddressTypeEnum::BOTH], true)) {
            $this->setDefaultShipping($userId, $addressId, $storeId);
        }

        if (in_array($resolvedType, [AddressTypeEnum::BILLING, AddressTypeEnum::BOTH], true)) {
            $this->setDefaultBilling($userId, $addressId, $storeId);
        }
    }

    public function clearDefaultsForAddressType(
        int $userId,
        string|AddressTypeEnum $type,
        ?int $excludeId,
        int $storeId
    ): void
    {
        $resolvedType = $type instanceof AddressTypeEnum ? $type : AddressTypeEnum::from($type);

        if (in_array($resolvedType, [AddressTypeEnum::SHIPPING, AddressTypeEnum::BOTH], true)) {
            $this->clearDefaultShipping($userId, $storeId, $excludeId);
        }

        if (in_array($resolvedType, [AddressTypeEnum::BILLING, AddressTypeEnum::BOTH], true)) {
            $this->clearDefaultBilling($userId, $storeId, $excludeId);
        }
    }

    public function setDefaultShipping(int $userId, int $addressId, int $storeId): void
    {
        $this->clearDefaultShipping($userId, $storeId);

        $this->scopedQuery()
            ->where('id', $addressId)
            ->update(['is_default_shipping' => true]);
    }

    public function setDefaultBilling(int $userId, int $addressId, int $storeId): void
    {
        $this->clearDefaultBilling($userId, $storeId);

        $this->scopedQuery()
            ->where('id', $addressId)
            ->update(['is_default_billing' => true]);
    }

    public function clearDefaultShipping(int $userId, int $storeId, ?int $excludeId = null): void
    {
        $query = $this->scopedQuery()
            ->where('user_id', $userId)
            ->whereIn('type', [AddressTypeEnum::SHIPPING->value, AddressTypeEnum::BOTH->value]);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $query->update(['is_default_shipping' => false]);
    }

    public function clearDefaultBilling(int $userId, int $storeId, ?int $excludeId = null): void
    {
        $query = $this->scopedQuery()
            ->where('user_id', $userId)
            ->whereIn('type', [AddressTypeEnum::BILLING->value, AddressTypeEnum::BOTH->value]);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $query->update(['is_default_billing' => false]);
    }

    public function getNextShippingDefaultCandidate(int $userId, int $excludeId, int $storeId): ?Address
    {
        return $this->scopedQuery()
            ->where('user_id', $userId)
            ->whereIn('type', [AddressTypeEnum::SHIPPING->value, AddressTypeEnum::BOTH->value])
            ->where('id', '!=', $excludeId)
            ->first();
    }

    public function getNextBillingDefaultCandidate(int $userId, int $excludeId, int $storeId): ?Address
    {
        return $this->scopedQuery()
            ->where('user_id', $userId)
            ->whereIn('type', [AddressTypeEnum::BILLING->value, AddressTypeEnum::BOTH->value])
            ->where('id', '!=', $excludeId)
            ->first();
    }
}
