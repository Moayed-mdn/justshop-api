<?php

namespace App\DTOs\Store;

class OnboardMerchantToStripeDTO
{
    public function __construct(
        public int $storeId,
    ) {}
}
