<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

use Illuminate\Http\Request;

class LogoutSessionDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $currentSessionId,
        public readonly string $sessionId,
    ) {}

    public static function fromRequest(Request $request, string $sessionId): self
    {
        return new self(
            userId: (int) $request->user()->id,
            currentSessionId: $request->session()->getId(),
            sessionId: $sessionId,
        );
    }
}
