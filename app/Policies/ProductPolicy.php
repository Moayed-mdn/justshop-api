<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;

class ProductPolicy
{
    use HasStoreMembership;

    public function viewAny(User $user, Store $store): bool
    {
        return $this->decision($user, 'viewAny', $this->canView($user, $store), $store);
    }

    public function view(User $user, Store $store): bool
    {
        return $this->decision($user, 'view', $this->canView($user, $store), $store);
    }

    public function create(User $user, Store $store): bool
    {
        return $this->decision($user, 'create', $this->canManage($user, $store, PermissionEnum::PRODUCT_CREATE, 'product', 'create'), $store);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->decision($user, 'update', $this->canManage($user, $store, PermissionEnum::PRODUCT_UPDATE, 'product', 'update'), $store);
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->decision($user, 'delete', $this->canManage($user, $store, PermissionEnum::PRODUCT_DELETE, 'product', 'delete'), $store);
    }

    public function restore(User $user, Store $store): bool
    {
        return $this->decision($user, 'restore', $this->canManage($user, $store, PermissionEnum::PRODUCT_RESTORE, 'product', 'restore'), $store);
    }

    private function canView(User $user, Store $store): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        $isAdmin = $this->isAdmin($user, $store);
        $hasPermission = $user->can(PermissionEnum::PRODUCT_VIEW);

        if ($isAdmin) {
            return $hasPermission;
        }

        if ($this->isMember($user, $store)) {
            if ($hasPermission) {
                return true;
            }
            // Member without permission - throw detailed error
            $this->denyWithContext('product', 'view', PermissionEnum::PRODUCT_VIEW);
        }

        // Not a member of this store at all
        return false;
    }

    private function canManage(User $user, Store $store, string $permission, string $resource, string $action): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        $isAdmin = $this->isAdmin($user, $store);
        $hasPermission = $user->can($permission);

        if ($isAdmin) {
            return $hasPermission;
        }

        if ($this->isMember($user, $store)) {
            if ($hasPermission) {
                return true;
            }
            // Member without permission - throw detailed error
            $this->denyWithContext($resource, $action, $permission);
        }

        // Not a member of this store at all
        return false;
    }
}
