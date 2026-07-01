<?php

namespace App\Actions\Shipping;

use App\Models\ShippingZone;

/**
 * Delete a shipping zone.
 */
class DeleteShippingZoneAction
{
    public function execute(ShippingZone $zone): bool
    {
        // Detach all methods first
        $zone->methods()->detach();
        
        return $zone->delete();
    }
}
