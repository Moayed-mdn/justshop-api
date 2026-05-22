<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Bootstrap;

final readonly class BootstrapResolutionMetadata
{
    /**
     * @param array<string, float> $resolverTimingsMs
     */
    public function __construct(
        public string $responseVersion,
        public string $resolverVersion,
        public string $authorityPath,
        public array $resolverTimingsMs = [],
    ) {}

    public function withResolverTiming(string $resolver, float $elapsedMs): self
    {
        return new self(
            responseVersion: $this->responseVersion,
            resolverVersion: $this->resolverVersion,
            authorityPath: $this->authorityPath,
            resolverTimingsMs: [
                ...$this->resolverTimingsMs,
                $resolver => $elapsedMs,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toLogContext(): array
    {
        return [
            'bootstrap_response_version' => $this->responseVersion,
            'bootstrap_resolver_version' => $this->resolverVersion,
            'bootstrap_authority_path' => $this->authorityPath,
            'bootstrap_resolver_timings_ms' => $this->resolverTimingsMs,
        ];
    }
}
