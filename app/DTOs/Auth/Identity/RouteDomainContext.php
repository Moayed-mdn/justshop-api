<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Identity;

use App\Enums\Auth\AuthDomainEnum;
use App\Enums\Auth\RouteDomainEnforcementModeEnum;
use App\Enums\Auth\RouteDomainEnum;

final readonly class RouteDomainContext
{
    /**
     * @param string[] $allowedActorTypes
     */
    public function __construct(
        public RouteDomainEnum $routeDomain,
        public AuthDomainEnum $ownerAuthDomain,
        public RouteDomainEnforcementModeEnum $enforcementMode,
        public array $allowedActorTypes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'route_domain' => $this->routeDomain->value,
            'route_owner_auth_domain' => $this->ownerAuthDomain->value,
            'route_enforcement_mode' => $this->enforcementMode->value,
            'route_allowed_actor_types' => $this->allowedActorTypes,
        ];
    }
}
