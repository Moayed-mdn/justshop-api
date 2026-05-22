<?php

declare(strict_types=1);

namespace App\Services\Auth\Bootstrap;

use App\DTOs\Auth\Bootstrap\BootstrapPermissionResolution;
use App\Models\Store;
use App\Models\User;
use App\Services\Auth\PermissionResolver;

class BootstrapPermissionResolver
{
    public function __construct(
        private readonly PermissionResolver $permissionResolver,
    ) {}

    public function resolve(User $user, ?Store $activeStore): BootstrapPermissionResolution
    {
        $capabilityResolution = $this->permissionResolver->resolveResult($user, $activeStore);

        return new BootstrapPermissionResolution(
            permissions: $capabilityResolution->permissions(),
            capabilityResolution: $capabilityResolution,
        );
    }
}
