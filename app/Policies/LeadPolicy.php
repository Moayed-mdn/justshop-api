<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Lead;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

/**
 * Wave 2 Remediation: Created LeadPolicy for platform-level admin authorization
 * 
 * Leads are platform-level resources (not store-scoped).
 * Only super_admin users can manage leads.
 */
class LeadPolicy
{
    use InteractsWithPolicyTelemetry;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return $this->decision($user, $ability, true);
        }

        return null;
    }

    /**
     * Determine whether the user can view any leads.
     */
    public function viewAny(User $user): bool
    {
        return $this->decision($user, 'viewAny', false);
    }

    /**
     * Determine whether the user can view the lead.
     */
    public function view(User $user, Lead $lead): bool
    {
        return $this->decision($user, 'view', false, $lead);
    }

    /**
     * Determine whether the user can update the lead.
     */
    public function update(User $user, Lead $lead): bool
    {
        return $this->decision($user, 'update', false, $lead);
    }

    /**
     * Determine whether the user can delete the lead.
     */
    public function delete(User $user, Lead $lead): bool
    {
        return $this->decision($user, 'delete', false, $lead);
    }
}
