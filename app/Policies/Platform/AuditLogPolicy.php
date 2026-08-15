<?php

declare(strict_types=1);

namespace App\Policies\Platform;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

/**
 * AuditLogPolicy - Authorization for platform audit log access
 * 
 * Audit logs are platform-level resources.
 * Only super_admin users can view audit logs.
 */
class AuditLogPolicy
{
    use InteractsWithPolicyTelemetry;

    /**
     * Determine whether the user can view any audit logs.
     */
    public function viewAny(User $user): bool
    {
        return $this->decision($user, 'viewAny', $user->hasRole(RoleEnum::SUPER_ADMIN->value));
    }
}
