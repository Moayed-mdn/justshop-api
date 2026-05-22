<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Bootstrap;

use App\Enums\Auth\OnboardingStepEnum;

class BootstrapOnboardingDTO
{
    public function __construct(
        public string $step,
        public bool $isCompleted,
    ) {}

    public static function fromData(OnboardingStepEnum $step, bool $isCompleted): self
    {
        return new self(
            step: $step->value,
            isCompleted: $isCompleted,
        );
    }
}
