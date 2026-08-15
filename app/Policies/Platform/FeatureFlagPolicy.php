<?php

declare(strict_types=1);

namespace App\Policies\Platform;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

/**
 * FeatureFlagPolicy - Authorization for platform feature flag management
 * 
 * Feature flags are platform-level resources.
 * Only super_admin users can manage feature flags.
 */
class FeatureFlagPolicy
{
    use InteractsWithPolicyTelemetry;

    /**
     * Determine whether the user can view any feature flags.
     */
    public function viewAny(User $user): bool
    {
        return $this->decision($user, 'viewAny', $user->hasRole(RoleEnum::SUPER_ADMIN->value));
    }

    /**
     * Determine whether the user can update feature flags.
     */
    public function update(User $user): bool
    {
        return $this->decision($user, 'update', $user->hasRole(RoleEnum::SUPER_ADMIN->value));
    }
}
