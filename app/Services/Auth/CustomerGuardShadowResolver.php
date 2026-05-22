<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\Session\GuardShadowResolution;
use App\DTOs\Auth\Session\SessionOwnershipContext;

class CustomerGuardShadowResolver
{
    public function resolve(SessionOwnershipContext $context): GuardShadowResolution
    {
        $wouldResolve = $context->authDomain === 'customer'
            || $context->routeDomain === 'customer_account';

        return new GuardShadowResolution(
            guardName: 'customer_guard',
            wouldResolve: $wouldResolve,
            reason: $wouldResolve ? 'customer_owned_path' : 'customer_guard_not_indicated',
        );
    }
}
