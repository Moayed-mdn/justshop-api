<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

class StorePolicy
{
    use InteractsWithPolicyTelemetry;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability, mixed $store = null): ?bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return $this->decision($user, $ability, true, $store);
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->decision($user, 'viewAny', true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Store $store): bool
    {
        return $this->decision(
            $user,
            'view',
            $user->stores()->where('store_id', $store->id)->exists(),
            $store,
        );
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->decision($user, 'create', true);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Store $store): bool
    {
        return $this->decision(
            $user,
            'update',
            $user->stores()
                ->where('store_id', $store->id)
                ->wherePivotIn('role', [
                    StoreRoleEnum::STORE_ADMIN->value,
                ])
                ->exists(),
            $store,
        );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Store $store): bool
    {
        return $this->decision($user, 'delete', $user->id === $store->owner_id, $store);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Store $store): bool
    {
        return $this->decision($user, 'restore', false, $store);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Store $store): bool
    {
        return $this->decision($user, 'forceDelete', false, $store);
    }
}
