<?php

declare(strict_types=1);

namespace App\Services\Auth\Permission;

use App\DTOs\Auth\Permission\CapabilityResolutionResult;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PermissionResolutionTelemetry
{
    public function recordResolved(User $user, CapabilityResolutionResult $result, string $mode): void
    {
        Log::info('authorization.permission.resolved', [
            'actor_id' => (int) $user->id,
            'resolution_mode' => $mode,
            'flag_state' => [
                'rbac.resolver.v2' => (bool) config('migration.rbac.resolver_v2', false),
                'rbac.dual_resolve' => (bool) config('migration.rbac.dual_resolve', false),
            ],
            ...$result->toLogContext(),
        ]);
    }

    public function recordParity(
        User $user,
        CapabilityResolutionResult $authoritative,
        CapabilityResolutionResult $shadow,
    ): void {
        $authoritativeCapabilities = $authoritative->permissions();
        $shadowCapabilities = $shadow->permissions();

        $missingFromShadow = array_values(array_diff($authoritativeCapabilities, $shadowCapabilities));
        $extraInShadow = array_values(array_diff($shadowCapabilities, $authoritativeCapabilities));
        $driftCount = count($missingFromShadow) + count($extraInShadow);

        Log::info('authorization.permission.parity_checked', [
            'actor_id' => (int) $user->id,
            'authoritative_authority' => $authoritative->authority,
            'shadow_authority' => $shadow->authority,
            'drift_count' => $driftCount,
            'missing_from_shadow' => $missingFromShadow,
            'extra_in_shadow' => $extraInShadow,
            'flag_state' => [
                'rbac.resolver.v2' => (bool) config('migration.rbac.resolver_v2', false),
                'rbac.dual_resolve' => (bool) config('migration.rbac.dual_resolve', false),
            ],
        ]);

        if ($driftCount > 0) {
            Log::warning('authorization.permission.drift_detected', [
                'actor_id' => (int) $user->id,
                'authoritative_authority' => $authoritative->authority,
                'shadow_authority' => $shadow->authority,
                'drift_count' => $driftCount,
                'missing_from_shadow' => $missingFromShadow,
                'extra_in_shadow' => $extraInShadow,
                'flag_state' => [
                    'rbac.resolver.v2' => (bool) config('migration.rbac.resolver_v2', false),
                    'rbac.dual_resolve' => (bool) config('migration.rbac.dual_resolve', false),
                ],
            ]);
        }
    }
}
