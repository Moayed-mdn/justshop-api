<?php

declare(strict_types=1);

namespace App\Services\Auth\Bootstrap;

use App\DTOs\Auth\Bootstrap\BootstrapOnboardingDTO;
use App\Enums\Auth\OnboardingStepEnum;
use App\Models\User;
use App\Services\Auth\OnboardingApplicabilityResolver;

class BootstrapOnboardingResolver
{
    public function __construct(
        private readonly OnboardingApplicabilityResolver $onboardingApplicabilityResolver,
    ) {}

    public function resolve(User $user): BootstrapOnboardingDTO
    {
        $applicability = $this->onboardingApplicabilityResolver->resolve($user);

        if (!$applicability->applies) {
            return BootstrapOnboardingDTO::fromData(
                OnboardingStepEnum::COMPLETED,
                true,
            );
        }

        $step = $user->onboarding_step ?? OnboardingStepEnum::COMPLETED;

        return BootstrapOnboardingDTO::fromData(
            $step,
            $user->isOnboardingCompleted(),
        );
    }
}
