<?php

declare(strict_types=1);

namespace App\Policies\Platform;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

/**
 * FeatureFlagPolicy - Authorization for platform feature flag management
 * 
 * Feature flags are platform-level resources.
 * Only super_admin users with explicit permissions can manage feature flags.
 */
class FeatureFlagPolicy
{
    use InteractsWithPolicyTelemetry;

    /**
     * Determine whether the user can view any feature flags.
     */
    public function viewAny(User $user): bool
    {
        $hasRole = $user->hasRole(RoleEnum::SUPER_ADMIN->value);
        $hasPermission = $user->can(PermissionEnum::FEATURE_FLAG_VIEW);

        return $this->decision($user, 'viewAny', $hasRole && $hasPermission);
    }

    /**
     * Determine whether the user can update feature flags.
     */
    public function update(User $user): bool
    {
        $hasRole = $user->hasRole(RoleEnum::SUPER_ADMIN->value);
        $hasPermission = $user->can(PermissionEnum::FEATURE_FLAG_UPDATE);

        return $this->decision($user, 'update', $hasRole && $hasPermission);
    }
}
