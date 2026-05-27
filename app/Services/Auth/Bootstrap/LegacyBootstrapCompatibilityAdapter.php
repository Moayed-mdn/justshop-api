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
use App\Services\Auth\OnboardingApplicabilityResolver;
use App\Services\Auth\Permission\LegacyPermissionAuthority;

class LegacyBootstrapCompatibilityAdapter
{
    public function __construct(
        private readonly LegacyPermissionAuthority $legacyPermissionAuthority,
        private readonly StoreRepository $storeRepository,
        private readonly OnboardingApplicabilityResolver $onboardingApplicabilityResolver,
    ) {}

    public function adapt(User $user, array $session = []): GetBootstrapResponseDTO
    {
        $stores = $this->storeRepository->getAccessibleStores($user);

        $activeStoreModel = null;
        if ($user->last_active_store_id) {
            $activeStoreModel = $stores->firstWhere('id', $user->last_active_store_id);
        }

        if (!$activeStoreModel && $stores->isNotEmpty()) {
            $activeStoreModel = $stores->first();
        }

        $storeDTOs = $stores->map(function (Store $store) use ($user): BootstrapStoreDTO {
            $role = $store->pivot?->role ?? 'member';
            $permissions = $this->legacyPermissionAuthority->resolve($user, $store)->permissions();

            return BootstrapStoreDTO::fromModel($store, $role, $permissions);
        })->toArray();

        $activeStoreDTO = null;
        if ($activeStoreModel) {
            $activeRole = $activeStoreModel->pivot?->role ?? 'member';
            $activePermissions = $this->legacyPermissionAuthority->resolve($user, $activeStoreModel)->permissions();
            $activeStoreDTO = BootstrapStoreDTO::fromModel($activeStoreModel, $activeRole, $activePermissions);
        }

        $applicability = $this->onboardingApplicabilityResolver->resolve($user);
        $step = $user->onboarding_step ?? \App\Enums\Auth\OnboardingStepEnum::COMPLETED;
        $steps = \App\Enums\Auth\OnboardingStepEnum::values();
        $currentStepIndex = array_search($step->value, $steps, true);
        
        if (!$applicability->applies) {
            $completedSteps = [];
            $step = \App\Enums\Auth\OnboardingStepEnum::COMPLETED;
        } else {
            $completedSteps = $currentStepIndex !== false ? array_slice($steps, 0, $currentStepIndex) : [];
        }
        
        $storeId = $user->stores()->first()?->id;

        $activePermissions = $activeStoreModel ? $this->legacyPermissionAuthority->resolve($user, $activeStoreModel)->permissions() : [];
        $activeCapabilities = \App\Services\Auth\Permission\PermissionTransformer::toFrontendFlags($activePermissions);

        return new GetBootstrapResponseDTO(
            user: BootstrapUserDTO::fromModel($user),
            stores: $storeDTOs,
            activeStore: $activeStoreDTO,
            onboarding: BootstrapOnboardingDTO::fromData(
                $step,
                $completedSteps,
                !$user->isOnboardingCompleted(),
                $storeId !== null ? (string) $storeId : null,
                $user->isOnboardingCompleted(),
            ),
            permissions: $activePermissions,
            capabilities: $activeCapabilities,
            config: BootstrapConfigDTO::fromDefaults(),
            actorContext: $user->getActorContext(),
            session: $session,
        );
    }
}
