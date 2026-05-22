<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\Permission\CapabilityResolutionResult;
use App\Models\Store;
use App\Models\User;
use App\Services\Auth\Permission\LegacyPermissionAuthority;
use App\Services\Auth\Permission\NormalizedPermissionAuthority;
use App\Services\Auth\Permission\PermissionResolutionTelemetry;

class PermissionResolver
{
    public function __construct(
        private readonly LegacyPermissionAuthority $legacyAuthority,
        private readonly NormalizedPermissionAuthority $normalizedAuthority,
        private readonly PermissionResolutionTelemetry $telemetry,
    ) {}

    /**
     * Resolve permissions for a user within a specific store context.
     *
     * @return string[]
     */
    public function resolve(User $user, ?Store $activeStore): array
    {
        return $this->resolveResult($user, $activeStore)->permissions();
    }

    public function resolveResult(User $user, ?Store $activeStore): CapabilityResolutionResult
    {
        $legacy = $this->legacyAuthority->resolve($user, $activeStore);
        $useNormalizedAuthority = (bool) config('migration.rbac.resolver_v2', false);
        $dualResolve = (bool) config('migration.rbac.dual_resolve', false);

        if (!$useNormalizedAuthority) {
            $this->telemetry->recordResolved($user, $legacy, 'legacy');

            if ($dualResolve) {
                $normalizedShadow = $this->normalizedAuthority->resolve($user, $activeStore);
                $this->telemetry->recordParity($user, $legacy, $normalizedShadow);
            }

            return $legacy;
        }

        $normalized = $this->normalizedAuthority->resolve($user, $activeStore);
        $this->telemetry->recordResolved($user, $normalized, 'normalized');

        if ($dualResolve) {
            $this->telemetry->recordParity($user, $normalized, $legacy);
        }

        return $normalized;
    }
}
