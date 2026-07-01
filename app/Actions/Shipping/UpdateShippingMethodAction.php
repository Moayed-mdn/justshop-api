<?php

namespace App\Actions\Shipping;

use App\DTOs\Shipping\UpdateShippingMethodDTO;
use App\Models\ShippingMethod;

/**
 * Update an existing shipping method.
 */
class UpdateShippingMethodAction
{
    public function execute(ShippingMethod $method, UpdateShippingMethodDTO $dto): ShippingMethod
    {
        $method->update($dto->toArray());
        
        return $method->fresh();
    }
}
