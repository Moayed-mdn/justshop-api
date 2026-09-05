<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;

class MembershipPolicy
{
    use HasStoreMembership;

    public function viewAny(User $user, Store $store): bool
    {
        return $this->decision($user, 'viewAny', $this->isMerchant($user) && $this->isAdmin($user, $store), $store);
    }

    public function view(User $user, Store $store): bool
    {
        return $this->decision($user, 'view', $this->isMerchant($user) && $this->isAdmin($user, $store), $store);
    }

    public function create(User $user, Store $store): bool
    {
        return $this->decision($user, 'create', $this->canManage($user, $store, PermissionEnum::USER_CREATE, 'user', 'create'), $store);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->decision($user, 'update', $this->canManage($user, $store, PermissionEnum::USER_BLOCK, 'user', 'block'), $store);
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->decision($user, 'delete', $this->canManage($user, $store, PermissionEnum::USER_DELETE, 'user', 'delete'), $store);
    }

    private function canManage(User $user, Store $store, string $permission, string $resource, string $action): bool
    {
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
