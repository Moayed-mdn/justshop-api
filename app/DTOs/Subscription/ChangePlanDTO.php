<?php

namespace App\DTOs\Subscription;

use App\Enums\Subscription\BillingCycleEnum;

final readonly class ChangePlanDTO
{
    public function __construct(
        public int $billingAccountId,
        public string $planCode,
        public BillingCycleEnum $billingCycle,
        public int $storeId,
        public ?int $actorUserId = null,
    ) {}
}
