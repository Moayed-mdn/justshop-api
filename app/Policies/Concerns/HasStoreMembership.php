<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\User;
use App\Models\Store;
use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Enums\Auth\ActorContextEnum;
use App\Services\Platform\Impersonation\ImpersonationLifecycleManager;

trait HasStoreMembership
{
    use InteractsWithPolicyTelemetry;

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
        // Rule 1: Explicit membership check
        if ($user->stores()->where('store_id', $store->id)->exists()) {
            return true;
        }

        // Rule 2: Governed impersonation bypass for platform actors
        return $this->isGovernedImpersonationActive($user);
    }

    /**
     * Check if the user has an administrative role in the store.
     */
    protected function isAdmin(User $user, Store $store): bool
    {
        // Rule 1: Explicit admin role check
        if ($user->stores()
            ->where('store_id', $store->id)
            ->wherePivotIn('role', [
                StoreRoleEnum::STORE_ADMIN->value,
            ])
            ->exists()) {
            return true;
        }

        // Rule 2: Governed impersonation bypass for platform actors
        return $this->isGovernedImpersonationActive($user);
    }

    /**
     * Determine if a governed impersonation session is active for a platform actor.
     */
    private function isGovernedImpersonationActive(User $user): bool
    {
        if (!$user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return false;
        }

        return app(ImpersonationLifecycleManager::class)->hasActiveImpersonation(request());
    }
}
