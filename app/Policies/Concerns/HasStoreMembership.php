<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\User;
use App\Models\Store;
use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Enums\Auth\ActorContextEnum;

trait HasStoreMembership
{
    use InteractsWithPolicyTelemetry;

    /**
     * Pre-authorization check for Super Admins.
     */
    public function before(User $user, string $ability, mixed $store = null): ?bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return $this->decision($user, $ability, true, $store);
        }

        return null;
    }

    /**
     * Check if the user is a merchant actor.
     */
    protected function isMerchant(User $user): bool
    {
        return $user->getActorContext() === ActorContextEnum::MERCHANT;
    }

    /**
     * Check if the user is a customer actor.
     */
    protected function isCustomer(User $user): bool
    {
        return $user->getActorContext() === ActorContextEnum::CUSTOMER;
    }

    /**
     * Check if the user is a member of the store.
     */
    protected function isMember(User $user, Store $store): bool
    {
        return $user->stores()->where('store_id', $store->id)->exists();
    }

    /**
     * Check if the user has an administrative role in the store.
     */
    protected function isAdmin(User $user, Store $store): bool
    {
        return $user->stores()
            ->where('store_id', $store->id)
            ->wherePivotIn('role', [
                StoreRoleEnum::STORE_ADMIN->value,
            ])
            ->exists();
    }
}
