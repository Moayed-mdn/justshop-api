<?php

declare(strict_types=1);

namespace App\DTOs\Subscription;

use Carbon\Carbon;

final readonly class EnterGracePeriodDTO
{
    public function __construct(
        public int $subscriptionId,
        public Carbon $gracePeriodEndsAt,
        public ?string $reason = null,
    ) {}

    public static function fromSubscription(
        int $subscriptionId,
        int $gracePeriodDays = 3,
        ?string $reason = null,
    ): self {
        return new self(
            subscriptionId: $subscriptionId,
            gracePeriodEndsAt: now()->addDays($gracePeriodDays),
            reason: $reason ?? 'Payment retries exhausted',
        );
    }
}
