<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Session;

final readonly class SessionOwnershipContext
{
    public function __construct(
        public ?string $authDomain,
        public ?string $actorType,
        public ?string $routeDomain,
        public string $sessionOrigin,
        public string $intendedGuardFuture,
        public bool $onboardingApplicable,
        public ?int $actorId = null,
        public ?string $routeOwnerAuthDomain = null,
        public ?string $sessionId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'auth_domain' => $this->authDomain,
            'actor_type' => $this->actorType,
            'route_domain' => $this->routeDomain,
            'session_origin' => $this->sessionOrigin,
            'intended_guard_future' => $this->intendedGuardFuture,
            'onboarding_applicable' => $this->onboardingApplicable,
            'actor_id' => $this->actorId,
            'route_owner_auth_domain' => $this->routeOwnerAuthDomain,
            'session_id' => $this->sessionId,
        ];
    }
}
