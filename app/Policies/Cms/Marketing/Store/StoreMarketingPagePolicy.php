<?php

declare(strict_types=1);

namespace App\Policies\Cms\Marketing\Store;

use App\Enums\PermissionEnum;
use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\HasStoreMembership;
use Illuminate\Auth\Access\HandlesAuthorization;

class StoreMarketingPagePolicy
{
    use HandlesAuthorization, HasStoreMembership;

    public function viewAny(User $user, Store $store): bool
    {
        return $this->isMember($user, $store) 
            && $user->can(PermissionEnum::MARKETING_STORE_VIEW);
    }

    public function view(User $user, Store $store): bool
    {
        return $this->isMember($user, $store) 
            && $user->can(PermissionEnum::MARKETING_STORE_VIEW);
    }

    public function create(User $user, Store $store): bool
    {
        return $this->isMember($user, $store) 
            && $user->can(PermissionEnum::MARKETING_STORE_CREATE);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->isMember($user, $store) 
            && $user->can(PermissionEnum::MARKETING_STORE_UPDATE);
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->isMember($user, $store) 
            && $user->can(PermissionEnum::MARKETING_STORE_DELETE);
    }

    public function publish(User $user, Store $store): bool
    {
        return $this->isMember($user, $store) 
            && $user->can(PermissionEnum::MARKETING_STORE_PUBLISH);
    }
}
