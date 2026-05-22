<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Store;
use App\Policies\Concerns\HasStoreMembership;

class MembershipPolicy
{
    use HasStoreMembership;

    /**
     * Determine whether the user can view memberships of the store.
     */
    public function viewAny(User $user, Store $store): bool
    {
        return $this->isAdmin($user, $store);
    }

    /**
     * Determine whether the user can view a specific membership.
     */
    public function view(User $user, Store $store): bool
    {
        return $this->isAdmin($user, $store);
    }

    /**
     * Determine whether the user can invite/create memberships.
     */
    public function create(User $user, Store $store): bool
    {
        return $this->isAdmin($user, $store);
    }

    /**
     * Determine whether the user can update a membership.
     */
    public function update(User $user, Store $store): bool
    {
        return $this->isAdmin($user, $store);
    }

    /**
     * Determine whether the user can delete/revoke a membership.
     */
    public function delete(User $user, Store $store): bool
    {
        return $this->isAdmin($user, $store);
    }
}
