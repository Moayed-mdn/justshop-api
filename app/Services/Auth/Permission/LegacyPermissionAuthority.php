<?php

declare(strict_types=1);

namespace App\Services\Auth\Permission;

use App\DTOs\Auth\Permission\CapabilityResolutionResult;
use App\Models\Store;
use App\Models\User;
use Spatie\Permission\Models\Role;

class LegacyPermissionAuthority
{
    public function resolve(User $user, ?Store $activeStore): CapabilityResolutionResult
    {
        if ($user->isSuperAdmin()) {
            return new CapabilityResolutionResult(
                capabilities: $user->getAllPermissions()->pluck('name')->sort()->values()->toArray(),
                authority: 'legacy.role_permissions',
                resolutionPath: 'super_admin.all_permissions',
                storeId: $activeStore?->id,
                membershipId: null,
                membershipRole: null,
                storeScoped: $activeStore !== null,
                superAdminBypass: true,
            );
        }

        if (!$activeStore) {
            return new CapabilityResolutionResult(
                capabilities: [],
                authority: 'legacy.role_permissions',
                resolutionPath: 'no_active_store',
                storeId: null,
                membershipId: null,
                membershipRole: null,
                storeScoped: false,
                superAdminBypass: false,
            );
        }

        $storeMembership = $user->stores()
            ->where('store_id', $activeStore->id)
            ->first();

        if (!$storeMembership || !$storeMembership->pivot?->role) {
            return new CapabilityResolutionResult(
                capabilities: [],
                authority: 'legacy.role_permissions',
                resolutionPath: 'missing_membership',
                storeId: (int) $activeStore->id,
                membershipId: $storeMembership?->pivot?->id ? (int) $storeMembership->pivot->id : null,
                membershipRole: null,
                storeScoped: true,
                superAdminBypass: false,
            );
        }

        try {
            $role = Role::findByName((string) $storeMembership->pivot->role, 'web');
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist) {
            $role = null;
        }

        $capabilities = $role ? $role->permissions->pluck('name')->sort()->values()->toArray() : [];

        // If the store is not active, restrict permissions to view-only or empty
        if (!$activeStore->is_active || $activeStore->status !== \App\Enums\Store\StoreStatusEnum::ACTIVE) {
            $capabilities = array_values(array_filter($capabilities, function ($permission) {
                return str_ends_with($permission, '.view') || $permission === 'dashboard.view';
            }));
        }

        return new CapabilityResolutionResult(
            capabilities: $capabilities,
            authority: 'legacy.role_permissions',
            resolutionPath: $role ? 'role.permissions' : 'role_not_found',
            storeId: (int) $activeStore->id,
            membershipId: $storeMembership->pivot?->id ? (int) $storeMembership->pivot->id : null,
            membershipRole: (string) $storeMembership->pivot->role,
            storeScoped: true,
            superAdminBypass: false,
        );
    }
}
