<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Identity;

use App\Enums\Auth\ActorContextEnum;
use App\Enums\Auth\AuthDomainEnum;
use App\Enums\Auth\OperationalContextEnum;

final readonly class IdentityContext
{
    public function __construct(
        public ActorContextEnum $actorType,
        public int $actorId,
        public bool $onboardingRequired,
        public OperationalContextEnum $operationalContext,
        public AuthDomainEnum $authDomain,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'actor_type' => $this->actorType->value,
            'actor_id' => $this->actorId,
            'onboarding_required' => $this->onboardingRequired,
            'operational_context' => $this->operationalContext->value,
            'auth_domain' => $this->authDomain->value,
        ];
    }
}
