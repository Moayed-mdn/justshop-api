<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\Enums\ErrorCode;

class OnboardingIncompleteException extends DomainException
{
    public function __construct(string $message = "Onboarding is incomplete.")
    {
        parent::__construct($message, ErrorCode::AUTH_002, 403);
    }
}
