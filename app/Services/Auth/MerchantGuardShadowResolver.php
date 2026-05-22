<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\Session\GuardShadowResolution;
use App\DTOs\Auth\Session\SessionOwnershipContext;

class MerchantGuardShadowResolver
{
    public function resolve(SessionOwnershipContext $context): GuardShadowResolution
    {
        $wouldResolve = in_array($context->authDomain, ['merchant', 'platform'], true)
            || in_array($context->routeDomain, ['merchant_users', 'merchant_admin'], true)
            || $context->onboardingApplicable;

        return new GuardShadowResolution(
            guardName: 'merchant_guard',
            wouldResolve: $wouldResolve,
            reason: $wouldResolve ? 'merchant_or_platform_owned_path' : 'merchant_guard_not_indicated',
        );
    }
}
