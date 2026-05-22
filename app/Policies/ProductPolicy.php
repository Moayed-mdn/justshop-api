<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Store;
use App\Policies\Concerns\HasStoreMembership;

class ProductPolicy
{
    use HasStoreMembership;

    public function viewAny(User $user, Store $store): bool
    {
        return $this->isMember($user, $store);
    }

    public function view(User $user, Store $store): bool
    {
        return $this->isMember($user, $store);
    }

    public function create(User $user, Store $store): bool
    {
        return $this->isMember($user, $store);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->isAdmin($user, $store);
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->isAdmin($user, $store);
    }
}
