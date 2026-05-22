<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

class TagPolicy
{
    use InteractsWithPolicyTelemetry;

    public function before(User $user, string $ability, mixed $store = null): ?bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return $this->decision($user, $ability, true, $store, [
                'authorization_domain' => 'tag',
                'fallback_path_used' => false,
            ]);
        }

        return null;
    }

    public function viewAny(User $user, Store $store): bool
    {
        return $this->decision($user, 'viewAny', $this->canView($user, $store), $store, [
            'authorization_domain' => 'tag',
            'fallback_path_used' => false,
        ]);
    }

    public function view(User $user, Store $store): bool
    {
        return $this->decision($user, 'view', $this->canView($user, $store), $store, [
            'authorization_domain' => 'tag',
            'fallback_path_used' => false,
        ]);
    }

    public function create(User $user, Store $store): bool
    {
        return $this->decision($user, 'create', $this->canManage($user, $store, PermissionEnum::TAG_CREATE), $store, [
            'authorization_domain' => 'tag',
            'fallback_path_used' => false,
        ]);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->decision($user, 'update', $this->canManage($user, $store, PermissionEnum::TAG_UPDATE), $store, [
            'authorization_domain' => 'tag',
            'fallback_path_used' => false,
        ]);
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->decision($user, 'delete', $this->canManage($user, $store, PermissionEnum::TAG_DELETE), $store, [
            'authorization_domain' => 'tag',
            'fallback_path_used' => false,
        ]);
    }

    private function canView(User $user, Store $store): bool
    {
        return $this->isStoreMember($user, $store) && $user->can(PermissionEnum::TAG_VIEW);
    }

    private function canManage(User $user, Store $store, string $permission): bool
    {
        return $this->isStoreAdmin($user, $store) && $user->can($permission);
    }

    private function isStoreMember(User $user, Store $store): bool
    {
        return $user->stores()->where('store_id', $store->id)->exists();
    }

    private function isStoreAdmin(User $user, Store $store): bool
    {
        return $user->stores()
            ->where('store_id', $store->id)
            ->wherePivotIn('role', [StoreRoleEnum::STORE_ADMIN->value])
            ->exists();
    }
}
