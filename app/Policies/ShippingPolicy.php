<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;

class ShippingPolicy
{
    use HasStoreMembership;

    public function view(User $user, Store $store): bool
    {
        return $this->decision($user, 'view', $this->canView($user, $store), $store);
    }

    public function create(User $user, Store $store): bool
    {
        return $this->decision($user, 'create', $this->canManage($user, $store, PermissionEnum::SHIPPING_CREATE, 'shipping', 'create'), $store);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->decision($user, 'update', $this->canManage($user, $store, PermissionEnum::SHIPPING_UPDATE, 'shipping', 'update'), $store);
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->decision($user, 'delete', $this->canManage($user, $store, PermissionEnum::SHIPPING_DELETE, 'shipping', 'delete'), $store);
    }

    private function canView(User $user, Store $store): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        $isAdmin = $this->isAdmin($user, $store);
        $hasPermission = $user->can(PermissionEnum::SHIPPING_VIEW);

        if ($isAdmin) {
            return $hasPermission;
        }

        if ($this->isMember($user, $store)) {
            if ($hasPermission) {
                return true;
            }
            $this->denyWithContext('shipping', 'view', PermissionEnum::SHIPPING_VIEW);
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
