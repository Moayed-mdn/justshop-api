<?php

declare(strict_types=1);

namespace App\Policies\Cms\Marketing\Store;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;

class StoreMarketingPagePolicy
{
    use HasStoreMembership;

    public function viewAny(User $user, Store $store): bool
    {
        return $this->decision($user, 'viewAny', $this->canView($user, $store, PermissionEnum::MARKETING_STORE_VIEW), $store);
    }

    public function view(User $user, Store $store): bool
    {
        return $this->decision($user, 'view', $this->canView($user, $store, PermissionEnum::MARKETING_STORE_VIEW), $store);
    }

    public function create(User $user, Store $store): bool
    {
        return $this->decision($user, 'create', $this->canManage($user, $store, PermissionEnum::MARKETING_STORE_CREATE, 'page', 'create'), $store);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->decision($user, 'update', $this->canManage($user, $store, PermissionEnum::MARKETING_STORE_UPDATE, 'page', 'update'), $store);
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->decision($user, 'delete', $this->canManage($user, $store, PermissionEnum::MARKETING_STORE_DELETE, 'page', 'delete'), $store);
    }

    public function publish(User $user, Store $store): bool
    {
        return $this->decision($user, 'publish', $this->canManage($user, $store, PermissionEnum::MARKETING_STORE_PUBLISH, 'page', 'publish'), $store);
    }

    private function canView(User $user, Store $store, string $permission): bool
    {
        $isAdmin = $this->isAdmin($user, $store);
        $hasPermission = $user->can($permission);

        if ($isAdmin && $hasPermission) {
            return true;
        }

        if ($this->isMember($user, $store)) {
            if ($hasPermission) {
                return true;
            }
            // Member without permission - throw detailed error
            $this->denyWithContext('page', 'view', $permission);
        }

        // Not a member of this store at all
        $this->denyWithContext('page', 'view', $permission);
    }

    private function canManage(User $user, Store $store, string $permission, string $resource, string $action): bool
    {
        $isAdmin = $this->isAdmin($user, $store);
        $hasPermission = $user->can($permission);

        if ($isAdmin && $hasPermission) {
            return true;
        }

        if ($this->isMember($user, $store)) {
            if ($hasPermission) {
                return true;
            }
            // Member without permission - throw detailed error
            $this->denyWithContext($resource, $action, $permission);
        }

        // Not a member of this store at all
        $this->denyWithContext($resource, $action, $permission);
    }
}
