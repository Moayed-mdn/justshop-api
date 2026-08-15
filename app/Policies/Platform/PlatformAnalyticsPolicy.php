<?php

declare(strict_types=1);

namespace App\Policies\Platform;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

/**
 * PlatformAnalyticsPolicy - Authorization for platform analytics access
 * 
 * Platform analytics are platform-level resources.
 * Only super_admin users can view platform analytics.
 */
class PlatformAnalyticsPolicy
{
    use InteractsWithPolicyTelemetry;

    /**
     * Determine whether the user can view platform analytics.
     */
    public function viewAny(User $user): bool
    {
        return $this->decision($user, 'viewAny', $user->hasRole(RoleEnum::SUPER_ADMIN->value));
    }
}
