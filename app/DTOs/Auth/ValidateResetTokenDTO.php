<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

use Illuminate\Http\Request;

class ValidateResetTokenDTO
{
    public function __construct(
        public string $userEmail,
        public string $token,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userEmail: $request->input('email'),
            token: $request->input('token'),
        );
    }
}
