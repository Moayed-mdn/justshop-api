<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Identity;

use App\Enums\Auth\AuthDomainEnum;
use App\Enums\Auth\ActorContextEnum;

final readonly class SessionBoundaryMetadata
{
    public function __construct(
        public ?string $sessionId,
        public ?AuthDomainEnum $authDomain,
        public ?ActorContextEnum $actorType,
        public ?int $actorId,
        public string $authorityModel,
        public string $isolationState,
        public ?string $ownershipKey,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'auth_domain' => $this->authDomain?->value,
            'actor_type' => $this->actorType?->value,
            'actor_id' => $this->actorId,
            'authority_model' => $this->authorityModel,
            'isolation_state' => $this->isolationState,
            'ownership_key' => $this->ownershipKey,
        ];
    }
}
