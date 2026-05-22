<?php

declare(strict_types=1);

namespace App\Support\Observability;

final readonly class RequestTraceContext
{
    public function __construct(
        public string $correlationId,
        public ?int $actorId,
        public ?string $actorType,
        public ?int $membershipId,
        public ?int $storeId,
        public string $apiDomain,
        public string $releaseVersion,
    ) {}

    public static function initialize(
        string $correlationId,
        string $apiDomain,
        string $releaseVersion,
    ): self {
        return new self(
            correlationId: $correlationId,
            actorId: null,
            actorType: null,
            membershipId: null,
            storeId: null,
            apiDomain: $apiDomain,
            releaseVersion: $releaseVersion,
        );
    }

    public function withActor(?int $actorId, ?string $actorType): self
    {
        return new self(
            correlationId: $this->correlationId,
            actorId: $actorId,
            actorType: $actorType,
            membershipId: $this->membershipId,
            storeId: $this->storeId,
            apiDomain: $this->apiDomain,
            releaseVersion: $this->releaseVersion,
        );
    }

    public function withStore(?int $storeId, ?int $membershipId = null): self
    {
        return new self(
            correlationId: $this->correlationId,
            actorId: $this->actorId,
            actorType: $this->actorType,
            membershipId: $membershipId,
            storeId: $storeId,
            apiDomain: $this->apiDomain,
            releaseVersion: $this->releaseVersion,
        );
    }

    public function toLogContext(): array
    {
        return [
            'correlation_id' => $this->correlationId,
            'actor_id' => $this->actorId,
            'actor_type' => $this->actorType,
            'membership_id' => $this->membershipId,
            'store_id' => $this->storeId,
            'api_domain' => $this->apiDomain,
            'release_version' => $this->releaseVersion,
        ];
    }
}
