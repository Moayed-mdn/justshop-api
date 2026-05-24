<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Bootstrap;

use App\Enums\Auth\OnboardingStepEnum;

class BootstrapOnboardingDTO
{
    public function __construct(
        public string $step,
        public array $completedSteps,
        public bool $canResume,
        public ?string $storeId,
        public bool $isCompleted,
    ) {}

    public static function fromData(
        OnboardingStepEnum $step,
        array $completedSteps,
        bool $canResume,
        ?string $storeId,
        bool $isCompleted
    ): self
    {
        return new self(
            step: $step->value,
            completedSteps: $completedSteps,
            canResume: $canResume,
            storeId: $storeId,
            isCompleted: $isCompleted,
        );
    }
}
