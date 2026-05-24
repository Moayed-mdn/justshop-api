<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\Enums\ErrorCode;

/**
 * Thrown when an invalid onboarding state transition is attempted.
 */
class OnboardingTransitionException extends DomainException
{
    public function __construct(string $message = "Invalid onboarding state transition.")
    {
        parent::__construct($message, ErrorCode::AUTH_002, 422);
    }
}
