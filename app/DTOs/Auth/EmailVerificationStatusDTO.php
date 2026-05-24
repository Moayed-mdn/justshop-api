<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

class EmailVerificationStatusDTO
{
    public function __construct(
        public bool $emailVerified,
        public ?string $emailVerifiedAt,
    ) {}
}
