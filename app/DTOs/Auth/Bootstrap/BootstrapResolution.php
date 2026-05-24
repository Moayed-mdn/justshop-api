<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Bootstrap;

use App\Enums\Auth\ActorContextEnum;

final readonly class BootstrapResolution
{
    /**
     * @param BootstrapStoreDTO[] $stores
     * @param mixed[] $capabilities
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
        public BootstrapResolutionMetadata $metadata,
    ) {}
}
