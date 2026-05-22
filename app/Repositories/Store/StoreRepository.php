<?php

namespace App\Repositories\Store;

use App\DTOs\Store\CreateStoreDTO;
use App\DTOs\Store\UpdateStoreDTO;
use App\Enums\Store\StoreRoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Exceptions\Store\StoreNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StoreRepository
{
    /**
     * Get all stores accessible by the user.
     */
    public function getAccessibleStores(User $user): Collection
    {
        if ($user->hasRole(\App\Enums\RoleEnum::SUPER_ADMIN->value)) {
            return Store::where('is_active', true)->get();
        }

        return $user->stores;
    }

    public function create(CreateStoreDTO $dto): Store
    {
        return DB::transaction(function () use ($dto) {
            $store = Store::create([
                'name' => $dto->name,
                'slug' => $dto->slug,
                'owner_id' => $dto->ownerId,
            ]);

            $store->users()->attach($dto->ownerId, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

            return $store;
        });
    }

    public function findById(int $storeId): Store
    {
        $store = Store::find($storeId);

        if (!$store) {
            throw new StoreNotFoundException();
        }

        return $store;
    }

    public function update(UpdateStoreDTO $dto): Store
    {
        $store = $this->findById($dto->storeId);

        $data = [];

        if ($dto->name !== null) {
            $data['name'] = $dto->name;
        }

        if ($dto->slug !== null) {
            $data['slug'] = $dto->slug;
        }

        if ($dto->isActive !== null) {
            $data['is_active'] = $dto->isActive;
        }

        if (!empty($data)) {
            $store->update($data);
        }

        return $store->fresh();
    }
}
