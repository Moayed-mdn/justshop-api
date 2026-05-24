<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

use App\Http\Requests\Auth\GetActiveSessionsRequest;

class GetActiveSessionsDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $currentSessionId,
    ) {}

    public static function fromRequest(GetActiveSessionsRequest $request): self
    {
        return new self(
            userId: $request->user()->id,
            currentSessionId: $request->session()->getId(),
        );
    }
}
