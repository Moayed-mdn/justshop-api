<?php

namespace App\DTOs\Entitlement;

class RecomputeEntitlementsDTO
{
    public function __construct(
        public int $billingAccountId,
        public int $storeId,
        public bool $isGrandfathered = false,
    ) {}
}
