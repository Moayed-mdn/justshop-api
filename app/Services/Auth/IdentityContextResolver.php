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

class IdentityContextResolver
{
    public function resolve(User $user): IdentityContext
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return new IdentityContext(
                actorType: ActorContextEnum::SUPER_ADMIN,
                actorId: (int) $user->id,
                onboardingRequired: false,
                operationalContext: OperationalContextEnum::PLATFORM_ADMIN,
                authDomain: AuthDomainEnum::PLATFORM,
            );
        }

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

        return new IdentityContext(
            actorType: ActorContextEnum::CUSTOMER,
            actorId: (int) $user->id,
            onboardingRequired: false,
            operationalContext: OperationalContextEnum::CUSTOMER_ACCOUNT,
            authDomain: AuthDomainEnum::CUSTOMER,
        );
    }
}
