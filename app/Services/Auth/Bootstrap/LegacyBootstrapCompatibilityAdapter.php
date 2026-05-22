<?php

declare(strict_types=1);

namespace App\Services\Auth\Bootstrap;

use App\DTOs\Auth\Bootstrap\BootstrapConfigDTO;
use App\DTOs\Auth\Bootstrap\BootstrapOnboardingDTO;
use App\DTOs\Auth\Bootstrap\BootstrapStoreDTO;
use App\DTOs\Auth\Bootstrap\BootstrapUserDTO;
use App\DTOs\Auth\Bootstrap\GetBootstrapResponseDTO;
use App\Models\Store;
use App\Models\User;
use App\Repositories\Store\StoreRepository;
use App\Services\Auth\Permission\LegacyPermissionAuthority;

class LegacyBootstrapCompatibilityAdapter
{
    public function __construct(
        private readonly LegacyPermissionAuthority $legacyPermissionAuthority,
        private readonly StoreRepository $storeRepository,
    ) {}

    public function adapt(User $user): GetBootstrapResponseDTO
    {
        $stores = $this->storeRepository->getAccessibleStores($user);

        $activeStoreModel = null;
        if ($user->last_active_store_id) {
            $activeStoreModel = $stores->firstWhere('id', $user->last_active_store_id);
        }

        if (!$activeStoreModel && $stores->isNotEmpty()) {
            $activeStoreModel = $stores->first();
        }

        $storeDTOs = $stores->map(function (Store $store): BootstrapStoreDTO {
            $role = $store->pivot?->role ?? 'member';

            return BootstrapStoreDTO::fromModel($store, $role);
        })->toArray();

        $activeStoreDTO = $activeStoreModel
            ? BootstrapStoreDTO::fromModel(
                $activeStoreModel,
                $activeStoreModel->pivot?->role ?? 'member',
            )
            : null;

        return new GetBootstrapResponseDTO(
            user: BootstrapUserDTO::fromModel($user),
            stores: $storeDTOs,
            activeStore: $activeStoreDTO,
            onboarding: BootstrapOnboardingDTO::fromData(
                $user->onboarding_step ?? \App\Enums\Auth\OnboardingStepEnum::COMPLETED,
                $user->isOnboardingCompleted(),
            ),
            permissions: $this->legacyPermissionAuthority->resolve($user, $activeStoreModel)->permissions(),
            capabilities: [],
            config: BootstrapConfigDTO::fromDefaults(),
            actorContext: $user->getActorContext(),
        );
    }
}
