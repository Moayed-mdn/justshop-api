<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Identity;

final readonly class OnboardingApplicability
{
    public function __construct(
        public bool $applies,
        public string $reason,
        public IdentityContext $identityContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'applies' => $this->applies,
            'reason' => $this->reason,
            'identity_context' => $this->identityContext->toArray(),
        ];
    }
}
