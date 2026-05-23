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

    /**
     * Determine whether the user can switch to (access) the store.
     * 
     * Wave 2 Remediation: Authorization moved from UpdateActiveStoreAction to explicit policy.
     * 
     * Authorization Rules:
     * - Super Admin: always allowed (handled in before())
     * - Merchant actors: must be a member of the store
     * - Customer actors: explicitly denied (merchant-only operation)
     * - Store must be active
     */
    public function switchStore(User $user, Store $store): bool
    {
        // Rule 1: Merchant-only operation (customer actors denied)
        if ($user->getActorContext() === \App\Enums\Auth\ActorContextEnum::CUSTOMER) {
            return $this->decision($user, 'switchStore', false, $store);
        }

        // Rule 2: Store must be active
        if (!$store->is_active) {
            return $this->decision($user, 'switchStore', false, $store);
        }

        // Rule 3: User must be a member of the store (super_admin bypassed in before())
        $isMember = $user->stores()
            ->where('store_id', $store->id)
            ->exists();

        return $this->decision($user, 'switchStore', $isMember, $store);
    }
}
