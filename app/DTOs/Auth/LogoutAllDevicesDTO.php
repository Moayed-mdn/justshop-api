<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

use App\Http\Requests\Auth\LogoutAllDevicesRequest;

class LogoutAllDevicesDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $currentSessionId,
    ) {}

    public static function fromRequest(LogoutAllDevicesRequest $request): self
    {
        return new self(
            userId: $request->user()->id,
            // Preserve the current session so the user stays logged in on this device.
            currentSessionId: $request->session()->getId(),
        );
    }
}
