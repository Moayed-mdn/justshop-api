<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\Identity\IdentityContext;
use App\Enums\Auth\ActorContextEnum;
use App\Enums\Auth\AuthDomainEnum;
use App\Enums\Auth\OperationalContextEnum;
use App\Enums\Auth\OnboardingStepEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use App\Services\Platform\Impersonation\ImpersonationLifecycleManager;

class IdentityContextResolver
{
    public function resolve(User $user): IdentityContext
    {
        // Rule 1: Super Admin Context (Platform Domain)
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            // Check for governed impersonation — if active, the identity context 
            // remains SUPER_ADMIN but the authority checks (policies) will allow 
            // access to merchant resources via the impersonation context.
            return new IdentityContext(
                actorType: ActorContextEnum::SUPER_ADMIN,
                actorId: (int) $user->id,
                onboardingRequired: false,
                operationalContext: OperationalContextEnum::PLATFORM_ADMIN,
                authDomain: AuthDomainEnum::PLATFORM,
            );
        }

        // Rule 2: Support Context (Platform Domain)
        if ($user->hasRole(RoleEnum::SUPPORT->value)) {
            return new IdentityContext(
                actorType: ActorContextEnum::SUPPORT_AGENT,
                actorId: (int) $user->id,
                onboardingRequired: false,
                operationalContext: OperationalContextEnum::PLATFORM_ADMIN,
                authDomain: AuthDomainEnum::PLATFORM,
            );
        }

        // Rule 3: Merchant Context
        $hasStoreMembership = $user->stores()->exists();
        $merchantCandidate = $hasStoreMembership || $user->onboarding_step !== null;

        if ($merchantCandidate) {
            $operationalContext = $user->onboarding_step === OnboardingStepEnum::COMPLETED && $hasStoreMembership
                ? OperationalContextEnum::MERCHANT_OPERATIONAL
                : OperationalContextEnum::MERCHANT_ONBOARDING;

            return new IdentityContext(
                actorType: ActorContextEnum::MERCHANT,
                actorId: (int) $user->id,
                onboardingRequired: true,
                operationalContext: $operationalContext,
                authDomain: AuthDomainEnum::MERCHANT,
            );
        }

        // Rule 4: Customer Context (Final Fallback)
        return new IdentityContext(
            actorType: ActorContextEnum::CUSTOMER,
            actorId: (int) $user->id,
            onboardingRequired: false,
            operationalContext: OperationalContextEnum::CUSTOMER_ACCOUNT,
            authDomain: AuthDomainEnum::CUSTOMER,
        );
    }
}
