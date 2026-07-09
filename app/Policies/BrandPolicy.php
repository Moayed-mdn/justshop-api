<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;

class BrandPolicy
{
    use HasStoreMembership;

    public function viewAny(User $user, Store $store): bool
    {
        return $this->decision($user, 'viewAny', $this->canView($user, $store), $store, [
            'authorization_domain' => 'brand',
            'fallback_path_used' => false,
        ]);
    }

    public function view(User $user, Store $store): bool
    {
        return $this->decision($user, 'view', $this->canView($user, $store), $store, [
            'authorization_domain' => 'brand',
            'fallback_path_used' => false,
        ]);
    }

    public function create(User $user, Store $store): bool
    {
        return $this->decision($user, 'create', $this->canManage($user, $store, PermissionEnum::BRAND_CREATE, 'brand', 'create'), $store, [
            'authorization_domain' => 'brand',
            'fallback_path_used' => false,
        ]);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->decision($user, 'update', $this->canManage($user, $store, PermissionEnum::BRAND_UPDATE, 'brand', 'update'), $store, [
            'authorization_domain' => 'brand',
            'fallback_path_used' => false,
        ]);
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->decision($user, 'delete', $this->canManage($user, $store, PermissionEnum::BRAND_DELETE, 'brand', 'delete'), $store, [
            'authorization_domain' => 'brand',
            'fallback_path_used' => false,
        ]);
    }

    public function restore(User $user, Store $store): bool
    {
        return $this->decision($user, 'restore', $this->canManage($user, $store, PermissionEnum::BRAND_RESTORE, 'brand', 'restore'), $store, [
            'authorization_domain' => 'brand',
            'fallback_path_used' => false,
        ]);
    }

    private function canView(User $user, Store $store): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        $isAdmin = $this->isAdmin($user, $store);
        $hasPermission = $user->can(PermissionEnum::BRAND_VIEW);

        if ($isAdmin) {
            return $hasPermission;
        }

        if ($this->isMember($user, $store)) {
            if ($hasPermission) {
                return true;
            }
            $this->denyWithContext('brand', 'view', PermissionEnum::BRAND_VIEW);
        }

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
            $this->denyWithContext($resource, $action, $permission);
        }

        return false;
    }
}
