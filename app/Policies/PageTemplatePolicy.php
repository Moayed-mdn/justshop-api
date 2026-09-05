<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\PageTemplate;
use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;

class PageTemplatePolicy
{
    use HasStoreMembership;

    public function viewAny(User $user, Store $store): bool
    {
        return $this->decision($user, 'viewAny', $this->canView($user, $store, PermissionEnum::TEMPLATE_VIEW, 'template', 'view'), $store);
    }

    public function view(User $user, PageTemplate $template): bool
    {
        $store = $template->store;
        return $this->decision($user, 'view', $this->canView($user, $store, PermissionEnum::TEMPLATE_VIEW, 'template', 'view'), $store);
    }

    public function create(User $user, Store $store): bool
    {
        return $this->decision($user, 'create', $this->canManage($user, $store, PermissionEnum::TEMPLATE_CREATE, 'template', 'create'), $store);
    }

    public function update(User $user, PageTemplate $template): bool
    {
        $store = $template->store;
        return $this->decision($user, 'update', $this->canManage($user, $store, PermissionEnum::TEMPLATE_UPDATE, 'template', 'update'), $store);
    }

    public function delete(User $user, PageTemplate $template): bool
    {
        $store = $template->store;
        return $this->decision($user, 'delete', $this->canManage($user, $store, PermissionEnum::TEMPLATE_DELETE, 'template', 'delete'), $store);
    }

    private function canView(User $user, Store $store, string $permission, string $resource, string $action): bool
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
