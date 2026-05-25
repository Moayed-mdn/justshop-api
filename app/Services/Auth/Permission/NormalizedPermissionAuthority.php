<?php

declare(strict_types=1);

namespace App\Services\Auth\Permission;

use App\DTOs\Auth\Permission\CapabilityResolutionResult;
use App\Models\Store;
use App\Models\User;
use App\Services\Auth\Membership\MembershipResolver;
use Spatie\Permission\Models\Role;

class NormalizedPermissionAuthority
{
    public function __construct(
        private readonly MembershipResolver $membershipResolver,
    ) {}

    public function resolve(User $user, ?Store $activeStore): CapabilityResolutionResult
    {
        if (!$activeStore) {
            return new CapabilityResolutionResult(
                capabilities: [],
                authority: 'normalized.store_scope',
                resolutionPath: 'no_active_store',
                storeId: null,
                membershipId: null,
                membershipRole: null,
                storeScoped: false,
                superAdminBypass: false,
            );
        }

        $membership = $this->membershipResolver->resolveForStore($user, $activeStore);

        if (!$membership) {
            return new CapabilityResolutionResult(
                capabilities: [],
                authority: 'normalized.store_scope',
                resolutionPath: 'missing_membership',
                storeId: (int) $activeStore->id,
                membershipId: null,
                membershipRole: null,
                storeScoped: true,
                superAdminBypass: false,
            );
        }

        $role = Role::findByName($membership->role, 'web');
        $capabilities = $role ? $role->permissions->pluck('name')->sort()->values()->toArray() : [];

        return new CapabilityResolutionResult(
            capabilities: $capabilities,
            authority: 'normalized.store_scope',
            resolutionPath: $role ? 'store.role.permissions' : 'role_not_found',
            storeId: (int) $activeStore->id,
            membershipId: $membership->membershipId,
            membershipRole: $membership->role,
            storeScoped: true,
            superAdminBypass: false,
        );
    }
}
