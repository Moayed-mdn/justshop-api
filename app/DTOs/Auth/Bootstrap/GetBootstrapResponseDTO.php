<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Bootstrap;

use App\Enums\Auth\ActorContextEnum;

class GetBootstrapResponseDTO
{
    /**
     * @param BootstrapStoreDTO[] $stores
     */
    public function __construct(
        public BootstrapUserDTO $user,
        public array $stores,
        public ?BootstrapStoreDTO $activeStore,
        public BootstrapOnboardingDTO $onboarding,
        public array $permissions,
        public array $capabilities,
        public BootstrapConfigDTO $config,
        public ActorContextEnum $actorContext,
        public array $session,
        public ?array $billing = null,
    ) {}
}
