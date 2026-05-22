<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Bootstrap;

use App\DTOs\Auth\Permission\CapabilityResolutionResult;

final readonly class BootstrapPermissionResolution
{
    /**
     * @param string[] $permissions
     */
    public function __construct(
        public array $permissions,
        public CapabilityResolutionResult $capabilityResolution,
    ) {}
}
