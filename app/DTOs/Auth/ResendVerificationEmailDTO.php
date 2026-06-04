<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

use App\Http\Requests\Auth\ResendVerificationEmailRequest;

class ResendVerificationEmailDTO
{
    public function __construct(
        public string $email,
        public string $ip,
    ) {}

    public static function fromRequest(ResendVerificationEmailRequest $request): self
    {
        // Prefer the authenticated user's email (setup flow) over the request body (unauthenticated flow).
        $email = $request->user()?->email ?? (string) $request->string('email');

        return new self(
            $email,
            (string) $request->ip(),
        );
    }
}
