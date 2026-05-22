<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\Bootstrap\BootstrapConfigDTO;
use App\DTOs\Auth\Bootstrap\BootstrapOnboardingDTO;
use App\DTOs\Auth\Bootstrap\BootstrapStoreDTO;
use App\DTOs\Auth\Bootstrap\BootstrapUserDTO;
use App\DTOs\Auth\Bootstrap\GetBootstrapResponseDTO;
use App\DTOs\Auth\GetBootstrapDTO;
use App\Models\User;
use App\Models\Store;
use App\Services\Auth\PermissionResolver;
use App\Repositories\Store\StoreRepository;

class GetBootstrapAction
{
    public function __construct(
        private PermissionResolver $permissionResolver,
        private StoreRepository $storeRepository,
    ) {}

    public function execute(GetBootstrapDTO $dto): GetBootstrapResponseDTO
    {
        $user = User::findOrFail($dto->userId);

        $stores = $this->storeRepository->getAccessibleStores($user);

        // Active store resolution
        $activeStoreModel = null;
        if ($user->last_active_store_id) {
            $activeStoreModel = $stores->firstWhere('id', $user->last_active_store_id);
        }

        // Fallback to first store if no active store is set but user has stores
        if (!$activeStoreModel && $stores->isNotEmpty()) {
            $activeStoreModel = $stores->first();
        }

        // Map to DTOs
        $storeDTOs = $stores->map(function (Store $store) use ($user) {
            $role = $store->pivot?->role ?? 'member';
            return BootstrapStoreDTO::fromModel($store, $role);
        })->toArray();

        $activeStoreDTO = $activeStoreModel 
            ? BootstrapStoreDTO::fromModel(
                $activeStoreModel, 
                $activeStoreModel->pivot?->role ?? 'member'
            ) 
            : null;

        return new GetBootstrapResponseDTO(
            user: BootstrapUserDTO::fromModel($user),
            stores: $storeDTOs,
            activeStore: $activeStoreDTO,
            onboarding: BootstrapOnboardingDTO::fromData(
                $user->onboarding_step, 
                $user->isOnboardingCompleted()
            ),
            permissions: $this->permissionResolver->resolve($user, $activeStoreModel),
            capabilities: [], // Future feature flags
            config: BootstrapConfigDTO::fromDefaults(),
            actorContext: $user->getActorContext(),
        );
    }
}
