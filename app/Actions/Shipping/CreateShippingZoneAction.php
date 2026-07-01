<?php

namespace App\Actions\Shipping;

use App\DTOs\Shipping\CreateShippingZoneDTO;
use App\Models\ShippingZone;

/**
 * Create a new shipping zone for a store.
 */
class CreateShippingZoneAction
{
    public function execute(CreateShippingZoneDTO $dto): ShippingZone
    {
        return ShippingZone::create($dto->toArray());
    }
}
