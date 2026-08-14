<?php

namespace App\DTOs\Billing;

class MigrateSubscribersDTO
{
    public function __construct(
        public readonly int $fromPlanId,
        public readonly int $toPlanId,
        public readonly array $billingAccountIds,
        public readonly bool $grandfatherExisting = false,
        public readonly bool $dryRun = false,
    ) {}
}
