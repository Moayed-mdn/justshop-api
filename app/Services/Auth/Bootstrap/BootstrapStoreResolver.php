<?php

declare(strict_types=1);

namespace App\Services\Auth\Bootstrap;

use App\DTOs\Auth\Bootstrap\BootstrapStoreDTO;
use App\DTOs\Auth\Bootstrap\BootstrapStoreResolution;
use App\Models\Store;
use App\Models\User;
use App\Repositories\Store\StoreRepository;
use App\Services\Auth\Membership\MembershipResolver;

class BootstrapStoreResolver
{
    public function __construct(
        private readonly StoreRepository $storeRepository,
        private readonly MembershipResolver $membershipResolver,
    ) {}

    public function resolve(User $user): BootstrapStoreResolution
    {
        $stores = $this->storeRepository->getAccessibleStores($user);
        $membershipsByStoreId = $this->membershipResolver->resolveForStores($user, $stores);

        $activeStoreModel = null;
        if ($user->last_active_store_id) {
            $activeStoreModel = $stores->firstWhere('id', $user->last_active_store_id);
        }

        if (!$activeStoreModel && $stores->isNotEmpty()) {
            $activeStoreModel = $stores->first();
        }

        $storeDTOs = $stores->map(function (Store $store) use ($membershipsByStoreId): BootstrapStoreDTO {
            $membership = $membershipsByStoreId[(int) $store->id] ?? null;
            $role = $membership?->role ?? (string) ($store->pivot?->role ?? 'member');

            return BootstrapStoreDTO::fromModel($store, $role);
        })->values()->all();

        $activeStoreDTO = null;
        if ($activeStoreModel) {
            $activeMembership = $membershipsByStoreId[(int) $activeStoreModel->id] ?? null;
            $activeRole = $activeMembership?->role ?? (string) ($activeStoreModel->pivot?->role ?? 'member');
            $activeStoreDTO = BootstrapStoreDTO::fromModel($activeStoreModel, $activeRole);
        }

        return new BootstrapStoreResolution(
            stores: $storeDTOs,
            activeStore: $activeStoreDTO,
            activeStoreModel: $activeStoreModel,
            membershipsByStoreId: $membershipsByStoreId,
        );
    }
}
