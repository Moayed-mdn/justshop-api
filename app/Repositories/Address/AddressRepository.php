<?php

namespace App\Repositories\Address;

use App\Models\Address;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class AddressRepository extends BaseRepository
{
    protected function modelClass(): string
    {
        return Address::class;
    }

    public function getByUser(int $userId, int $storeId, ?string $type = null): Collection
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

    public function setDefault(int $userId, string $type, int $addressId, int $storeId): void
    {
        $this->scopedQuery()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->update(['is_default' => false]);

        $this->scopedQuery()
            ->where('id', $addressId)
            ->update(['is_default' => true]);
    }

    public function unsetDefaultForType(int $userId, string $type, int $excludeId, int $storeId): void
    {
        $this->scopedQuery()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('id', '!=', $excludeId)
            ->update(['is_default' => false]);
    }

    public function getNextDefault(int $userId, string $type, int $excludeId, int $storeId): ?Address
    {
        return $this->scopedQuery()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('id', '!=', $excludeId)
            ->first();
    }
}
