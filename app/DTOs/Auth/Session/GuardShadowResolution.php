<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Session;

final readonly class GuardShadowResolution
{
    public function __construct(
        public string $guardName,
        public bool $wouldResolve,
        public string $reason,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'guard_name' => $this->guardName,
            'would_resolve' => $this->wouldResolve,
            'reason' => $this->reason,
        ];
    }
}
