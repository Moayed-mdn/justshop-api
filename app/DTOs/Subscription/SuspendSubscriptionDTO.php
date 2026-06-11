<?php

declare(strict_types=1);

namespace App\DTOs\Subscription;

final readonly class SuspendSubscriptionDTO
{
    public function __construct(
        public int $subscriptionId,
        public ?string $reason = null,
        public ?int $actorUserId = null,
        public string $source = 'system',
    ) {}

    public static function fromSystem(
        int $subscriptionId,
        string $reason = 'Grace period expired',
    ): self {
        return new self(
            subscriptionId: $subscriptionId,
            reason: $reason,
            actorUserId: null,
            source: 'system',
        );
    }

    public static function fromMerchant(
        int $subscriptionId,
        int $actorUserId,
        string $reason = 'Merchant paused subscription',
    ): self {
        return new self(
            subscriptionId: $subscriptionId,
            reason: $reason,
            actorUserId: $actorUserId,
            source: 'merchant',
        );
    }
}
