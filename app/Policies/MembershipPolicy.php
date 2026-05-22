<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;

class MembershipPolicy
{
    use HasStoreMembership;

    /**
     * Determine whether the user can view memberships of the store.
     */
    public function viewAny(User $user, Store $store): bool
    {
        return $this->decision($user, 'viewAny', $this->isAdmin($user, $store), $store);
    }

    /**
     * Determine whether the user can view a specific membership.
     */
    public function view(User $user, Store $store): bool
    {
        return $this->decision($user, 'view', $this->isAdmin($user, $store), $store);
    }

    /**
     * Determine whether the user can invite/create memberships.
     */
    public function create(User $user, Store $store): bool
    {
        return $this->decision($user, 'create', $this->isAdmin($user, $store), $store);
    }

    /**
     * Determine whether the user can update a membership.
     */
    public function update(User $user, Store $store): bool
    {
        return $this->decision($user, 'update', $this->isAdmin($user, $store), $store);
    }

    /**
     * Determine whether the user can delete/revoke a membership.
     */
    public function delete(User $user, Store $store): bool
    {
        return $this->decision($user, 'delete', $this->isAdmin($user, $store), $store);
    }
}
