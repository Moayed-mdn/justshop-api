<?php

declare(strict_types=1);

namespace App\DTOs\Subscription;

final readonly class ExpireSubscriptionDTO
{
    public function __construct(
        public int $subscriptionId,
        public ?string $reason = null,
        public string $source = 'system',
    ) {}

    public static function fromTrialExpiry(int $subscriptionId): self
    {
        return new self(
            subscriptionId: $subscriptionId,
            reason: 'Trial period expired without conversion',
            source: 'system',
        );
    }

    public static function fromGraceExpiry(int $subscriptionId): self
    {
        return new self(
            subscriptionId: $subscriptionId,
            reason: 'Grace period expired without payment',
            source: 'system',
        );
    }

    public static function fromCancellation(int $subscriptionId): self
    {
        return new self(
            subscriptionId: $subscriptionId,
            reason: 'Subscription canceled and period ended',
            source: 'system',
        );
    }
}
