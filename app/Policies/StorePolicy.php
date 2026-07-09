<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;
use App\Policies\Concerns\InteractsWithPolicyTelemetry;

class StorePolicy
{
    use HasStoreMembership;

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
        // Rule: Merchant-only operation (customer actors denied)
        if ($this->isCustomer($user)) {
            return $this->decision($user, 'view', false, $store);
        }

        $isAccessibleStore = $store->owner_id === $user->id || $this->isMember($user, $store);

        return $this->decision(
            $user,
            'view',
            $isAccessibleStore,
            $store,
        );
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Rule: Merchant-only operation (customer actors denied)
        if ($this->isCustomer($user)) {
            return $this->decision($user, 'create', false);
        }

        return $this->decision($user, 'create', true);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Store $store): bool
    {
        if ($this->isCustomer($user)) {
            return $this->decision($user, 'update', false, $store);
        }

        if ($user->id === $store->owner_id || $this->isAdmin($user, $store)) {
            return $this->decision($user, 'update', true, $store);
        }

        if ($this->isMember($user, $store)) {
            $this->denyWithContext('store', 'update', PermissionEnum::STORE_UPDATE);
        }

        return $this->decision($user, 'update', false, $store);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Store $store): bool
    {
        if ($this->isCustomer($user)) {
            return $this->decision($user, 'delete', false, $store);
        }

        if ($user->id === $store->owner_id || $this->isGovernedImpersonationActive($user)) {
            return $this->decision($user, 'delete', true, $store);
        }

        if ($this->isMember($user, $store)) {
            $this->denyWithContext('store', 'delete', PermissionEnum::STORE_DELETE);
        }

        return $this->decision($user, 'delete', false, $store);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Store $store): bool
    {
        if ($this->isMember($user, $store)) {
            $this->denyWithContext('store', 'restore', PermissionEnum::STORE_DELETE);
        }

        return $this->decision($user, 'restore', false, $store);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Store $store): bool
    {
        if ($this->isMember($user, $store)) {
            $this->denyWithContext('store', 'forceDelete', PermissionEnum::STORE_DELETE);
        }

        return $this->decision($user, 'forceDelete', false, $store);
    }

    /**
     * Determine whether the user can switch to (access) the store.
     * 
     * Wave 2 Remediation: Authorization moved from UpdateActiveStoreAction to explicit policy.
     * 
     * Authorization Rules:
     * - Super Admin: allowed via governed impersonation
     * - Merchant actors: must be a member of the store
     * - Customer actors: explicitly denied (merchant-only operation)
     * - Store must be active
     */
    public function switchStore(User $user, Store $store): bool
    {
        // Rule 1: Merchant-only operation (customer actors denied)
        if ($this->isCustomer($user)) {
            return $this->decision($user, 'switchStore', false, $store);
        }

        // Rule 2: Store must be active
        if (!$store->is_active) {
            return $this->decision($user, 'switchStore', false, $store);
        }

        // Rule 3: User must be a member of the store or platform admin (via impersonation)
        $isMember = $store->owner_id === $user->id || $this->isMember($user, $store);

        return $this->decision($user, 'switchStore', $isMember, $store);
    }
}
