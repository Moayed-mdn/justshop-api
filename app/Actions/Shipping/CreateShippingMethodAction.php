<?php

namespace App\Actions\Shipping;

use App\DTOs\Shipping\CreateShippingMethodDTO;
use App\Models\ShippingMethod;

/**
 * Create a new shipping method for a store.
 */
class CreateShippingMethodAction
{
    public function execute(CreateShippingMethodDTO $dto): ShippingMethod
    {
        return ShippingMethod::create($dto->toArray());
    }
}
