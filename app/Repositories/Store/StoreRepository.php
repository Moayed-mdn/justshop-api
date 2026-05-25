<?php

namespace App\Repositories\Store;

use App\DTOs\Store\CreateStoreDTO;
use App\DTOs\Store\UpdateStoreDTO;
use App\Enums\Store\StoreRoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Exceptions\Store\StoreNotFoundException;
use Illuminate\Database\Eloquent\Collection;

class StoreRepository
{
    /**
     * Get all stores accessible by the user.
     */
    public function getAccessibleStores(User $user): Collection
    {
        // Wave 6: Governed impersonation allows platform actors to access all active stores
        if ($user->hasRole(\App\Enums\RoleEnum::SUPER_ADMIN->value)) {
            $isImpersonating = app(\App\Services\Platform\Impersonation\ImpersonationLifecycleManager::class)
                ->hasActiveImpersonation(request());

            if ($isImpersonating) {
                return Store::where('is_active', true)->get();
            }
        }

        return $user->stores;
    }

    public function create(CreateStoreDTO $dto): Store
    {
        // No transaction here — CreateStoreAction owns the transaction boundary.
        // Adding a nested transaction would create a savepoint and could cause
        // DB::afterCommit callbacks to fire at the wrong commit level.
        $store = Store::create([
            'name'             => $dto->name,
            'slug'             => $dto->slug,
            'owner_id'         => $dto->ownerId,
            // New stores are created in a non-operational state and become active only
            // after async bootstrap finishes successfully.
            'status'           => \App\Enums\Store\StoreStatusEnum::PENDING_SETUP->value,
            'is_active'        => false,
            'status_changed_at' => now(),
            'provisioning_status' => \App\Enums\Store\ProvisioningStatusEnum::PENDING->value,
            'provisioning_progress' => 0,
            'provisioning_retryable' => false,
        ]);

        $store->users()->attach($dto->ownerId, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        return $store;
    }

    public function findById(int $storeId): Store
    {
        // Step 5 Hardening: Structural isolation for Store access
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        
        if ($user) {
            $accessibleStoreIds = $this->getAccessibleStores($user)->pluck('id')->toArray();
            
            if (!in_array($storeId, $accessibleStoreIds, true)) {
                throw new StoreNotFoundException('Access denied to store or store does not exist.');
            }
        }

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

        if ($dto->domain !== null) {
            $data['domain'] = $dto->domain;
        }

        if ($dto->currency !== null) {
            $data['currency'] = $dto->currency;
        }

        if ($dto->timezone !== null) {
            $data['timezone'] = $dto->timezone;
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
