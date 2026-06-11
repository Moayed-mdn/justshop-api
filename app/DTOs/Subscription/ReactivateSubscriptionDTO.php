<?php

declare(strict_types=1);

namespace App\DTOs\Subscription;

final readonly class ReactivateSubscriptionDTO
{
    public function __construct(
        public int $subscriptionId,
        public ?string $reason = null,
        public ?int $actorUserId = null,
        public string $source = 'webhook',
    ) {}

    public static function fromWebhook(
        int $subscriptionId,
        string $reason = 'Payment succeeded after past_due',
    ): self {
        return new self(
            subscriptionId: $subscriptionId,
            reason: $reason,
            actorUserId: null,
            source: 'webhook',
        );
    }

    public static function fromMerchant(
        int $subscriptionId,
        int $actorUserId,
        string $reason = 'Merchant resumed subscription',
    ): self {
        return new self(
            subscriptionId: $subscriptionId,
            reason: $reason,
            actorUserId: $actorUserId,
            source: 'merchant',
        );
    }
}
