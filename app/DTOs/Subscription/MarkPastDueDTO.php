<?php

declare(strict_types=1);

namespace App\DTOs\Subscription;

final readonly class MarkPastDueDTO
{
    public function __construct(
        public int $subscriptionId,
        public ?string $reason = null,
        public ?string $providerStatus = null,
    ) {}

    public static function fromWebhook(
        int $subscriptionId,
        string $reason,
        ?string $providerStatus = null,
    ): self {
        return new self(
            subscriptionId: $subscriptionId,
            reason: $reason,
            providerStatus: $providerStatus,
        );
    }
}
