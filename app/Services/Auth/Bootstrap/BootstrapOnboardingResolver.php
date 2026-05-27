<?php

declare(strict_types=1);

namespace App\Services\Auth\Bootstrap;

use App\DTOs\Auth\Bootstrap\BootstrapOnboardingDTO;
use App\Enums\Auth\OnboardingStepEnum;
use App\Models\User;
use App\Services\Auth\OnboardingApplicabilityResolver;
use App\Services\Auth\OnboardingRecoveryService;

class BootstrapOnboardingResolver
{
    public function __construct(
        private readonly OnboardingApplicabilityResolver $onboardingApplicabilityResolver,
        private readonly OnboardingRecoveryService $onboardingRecoveryService,
    ) {}

    public function resolve(User $user): BootstrapOnboardingDTO
    {
        $user = $this->onboardingRecoveryService->recover($user);
        $applicability = $this->onboardingApplicabilityResolver->resolve($user);

        if (!$applicability->applies) {
            return BootstrapOnboardingDTO::fromData(
                OnboardingStepEnum::COMPLETED,
                [],
                false,
                null,
                true,
            );
        }

        $step = $user->onboarding_step ?? OnboardingStepEnum::COMPLETED;
        $completedSteps = $this->getCompletedSteps($step);
        $storeId = $user->stores()->first()?->id;

        return BootstrapOnboardingDTO::fromData(
            $step,
            $completedSteps,
            !$user->isOnboardingCompleted(),
            $storeId !== null ? (string) $storeId : null,
            $user->isOnboardingCompleted(),
        );
    }

    private function getCompletedSteps(OnboardingStepEnum $currentStep): array
    {
        $steps = OnboardingStepEnum::values();
        $currentStepIndex = array_search($currentStep->value, $steps, true);

        if ($currentStepIndex === false) {
            return [];
        }

        return array_slice($steps, 0, $currentStepIndex);
    }
}
