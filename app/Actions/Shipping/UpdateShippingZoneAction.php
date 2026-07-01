<?php

namespace App\Actions\Shipping;

use App\DTOs\Shipping\UpdateShippingZoneDTO;
use App\Models\ShippingZone;

/**
 * Update an existing shipping zone.
 */
class UpdateShippingZoneAction
{
    public function execute(ShippingZone $zone, UpdateShippingZoneDTO $dto): ShippingZone
    {
        $zone->update($dto->toArray());
        
        return $zone->fresh();
    }
}
