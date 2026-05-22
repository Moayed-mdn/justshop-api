<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Enums\Auth\ActorContextEnum;
use App\Enums\RoleEnum;
use App\Models\User;

class ActorResolver
{
    /**
     * Resolve the actor context for a given user.
     */
    public function resolve(User $user): ActorContextEnum
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return ActorContextEnum::SUPER_ADMIN;
        }

        // Merchants are users who have at least one store membership
        // or are in the process of creating one (onboarding).
        // This logic will be hardened as we introduce dedicated Customer tables/guards.
        if ($user->stores()->exists() || $user->onboarding_step !== null) {
            return ActorContextEnum::MERCHANT;
        }

        return ActorContextEnum::CUSTOMER;
    }
}
