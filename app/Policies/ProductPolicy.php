<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;

class ProductPolicy
{
    use HasStoreMembership;

    public function viewAny(User $user, Store $store): bool
    {
        return $this->decision($user, 'viewAny', $this->isMember($user, $store), $store);
    }

    public function view(User $user, Store $store): bool
    {
        return $this->decision($user, 'view', $this->isMember($user, $store), $store);
    }

    public function create(User $user, Store $store): bool
    {
        return $this->decision($user, 'create', $this->isMember($user, $store), $store);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->decision($user, 'update', $this->isAdmin($user, $store), $store);
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->decision($user, 'delete', $this->isAdmin($user, $store), $store);
    }
}
