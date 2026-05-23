<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Session;

class GuardResolutionResult
{
    public function __construct(
        public readonly string $guard,
        public readonly string $authDomain,
        public readonly bool $isFallback,
        public readonly array $telemetry = [],
    ) {}

    public function toArray(): array
    {
        return [
            'guard' => $this->guard,
            'auth_domain' => $this->authDomain,
            'is_fallback' => $this->isFallback,
            'telemetry' => $this->telemetry,
        ];
    }
}
