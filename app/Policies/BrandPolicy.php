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
        return $this->decision($user, 'create', $this->canManage($user, $store, PermissionEnum::BRAND_CREATE), $store, [
            'authorization_domain' => 'brand',
            'fallback_path_used' => false,
        ]);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->decision($user, 'update', $this->canManage($user, $store, PermissionEnum::BRAND_UPDATE), $store, [
            'authorization_domain' => 'brand',
            'fallback_path_used' => false,
        ]);
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->decision($user, 'delete', $this->canManage($user, $store, PermissionEnum::BRAND_DELETE), $store, [
            'authorization_domain' => 'brand',
            'fallback_path_used' => false,
        ]);
    }

    public function restore(User $user, Store $store): bool
    {
        return $this->decision($user, 'restore', $this->canManage($user, $store, PermissionEnum::BRAND_RESTORE), $store, [
            'authorization_domain' => 'brand',
            'fallback_path_used' => false,
        ]);
    }

    private function canView(User $user, Store $store): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        return $this->isMember($user, $store) && $user->can(PermissionEnum::BRAND_VIEW);
    }

    private function canManage(User $user, Store $store, string $permission): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        return $this->isAdmin($user, $store) && $user->can($permission);
    }
}
