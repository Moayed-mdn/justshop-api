<?php

namespace App\DTOs\Billing;

final readonly class CreatePortalSessionDTO
{
    public function __construct(
        public int $billingAccountId,
        public string $returnUrl,
    ) {}
}
