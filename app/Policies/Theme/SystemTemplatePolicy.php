<?php

declare(strict_types=1);

namespace App\Policies\Theme;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Store;
use App\Models\Theme\ThemeTemplate;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;

class SystemTemplatePolicy
{
    use HasStoreMembership;

    public function viewAny(User $user, Store $store): bool
    {
        return $this->decision($user, 'viewAny', $this->canView($user, $store), $store);
    }

    public function view(User $user, ThemeTemplate $template): bool
    {
        $store = $template->theme?->store;
        if (!$store) {
            return false;
        }
        return $this->decision($user, 'view', $this->canView($user, $store), $store);
    }

    public function create(User $user, Store $store): bool
    {
        return $this->decision($user, 'create', $this->canManage($user, $store, PermissionEnum::THEME_CREATE, 'system_template', 'create'), $store);
    }

    public function update(User $user, ThemeTemplate $template): bool
    {
        $store = $template->theme?->store;
        if (!$store) {
            return false;
        }
        return $this->decision($user, 'update', $this->canManage($user, $store, PermissionEnum::THEME_UPDATE, 'system_template', 'update'), $store);
    }

    public function delete(User $user, ThemeTemplate $template): bool
    {
        $store = $template->theme?->store;
        if (!$store) {
            return false;
        }
        return $this->decision($user, 'delete', $this->canManage($user, $store, PermissionEnum::THEME_DELETE, 'system_template', 'delete'), $store);
    }

    private function canView(User $user, Store $store): bool
    {
        $isAdmin = $this->isAdmin($user, $store);
        $hasPermission = $user->can(PermissionEnum::THEME_VIEW);

        if ($isAdmin) {
            return $hasPermission;
        }

        if ($this->isMember($user, $store)) {
            if ($hasPermission) {
                return true;
            }
            $this->denyWithContext('system_template', 'view', PermissionEnum::THEME_VIEW);
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
