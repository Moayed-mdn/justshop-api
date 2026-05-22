<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Models\Store;
use Spatie\Permission\Models\Role;

class PermissionResolver
{
    /**
     * Resolve permissions for a user within a specific store context.
     */
    public function resolve(User $user, ?Store $activeStore): array
    {
        if ($user->isSuperAdmin()) {
            return $user->getAllPermissions()->pluck('name')->toArray();
        }

        if (!$activeStore) {
            return [];
        }

        // Get the role from the pivot table for the active store
        $storeMembership = $user->stores()
            ->where('store_id', $activeStore->id)
            ->first();
        
        if (!$storeMembership || !$storeMembership->pivot->role) {
            return [];
        }

        $role = Role::findByName($storeMembership->pivot->role, 'web');
        
        return $role ? $role->permissions->pluck('name')->toArray() : [];
    }
}
