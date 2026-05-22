<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\Identity\OnboardingApplicability;
use App\Enums\Auth\ActorContextEnum;
use App\Models\User;

class OnboardingApplicabilityResolver
{
    public function __construct(
        private readonly IdentityContextResolver $identityContextResolver,
    ) {}

    public function resolve(User $user): OnboardingApplicability
    {
        $identityContext = $this->identityContextResolver->resolve($user);

        return match ($identityContext->actorType) {
            ActorContextEnum::CUSTOMER => new OnboardingApplicability(
                applies: false,
                reason: 'customer_actor_bypass',
                identityContext: $identityContext,
            ),
            ActorContextEnum::SUPER_ADMIN => new OnboardingApplicability(
                applies: false,
                reason: 'super_admin_bypass',
                identityContext: $identityContext,
            ),
            default => new OnboardingApplicability(
                applies: true,
                reason: 'merchant_actor_required',
                identityContext: $identityContext,
            ),
        };
    }
}
