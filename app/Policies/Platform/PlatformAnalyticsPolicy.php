<?php

declare(strict_types=1);

namespace App\Policies\Platform;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

/**
 * PlatformAnalyticsPolicy - Authorization for platform analytics access
 * 
 * Platform analytics are platform-level resources.
 * Only super_admin users with explicit permissions can view platform analytics.
 */
class PlatformAnalyticsPolicy
{
    use InteractsWithPolicyTelemetry;

    /**
     * Determine whether the user can view platform analytics.
     */
    public function viewAny(User $user): bool
    {
        $hasRole = $user->hasRole(RoleEnum::SUPER_ADMIN->value);
        $hasPermission = $user->can(PermissionEnum::PLATFORM_ANALYTICS_VIEW);

        return $this->decision($user, 'viewAny', $hasRole && $hasPermission);
    }
}
