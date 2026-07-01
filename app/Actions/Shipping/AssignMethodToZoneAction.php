<?php

namespace App\Actions\Shipping;

use App\DTOs\Shipping\AssignMethodToZoneDTO;
use App\Models\ShippingZone;

/**
 * Assign a shipping method to a zone with optional price override.
 */
class AssignMethodToZoneAction
{
    public function execute(AssignMethodToZoneDTO $dto): void
    {
        $zone = ShippingZone::findOrFail($dto->zoneId);
        
        // Attach or update the method
        $zone->methods()->syncWithoutDetaching([
            $dto->methodId => ['price_override' => $dto->priceOverride]
        ]);
    }
}
