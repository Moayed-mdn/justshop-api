<?php

namespace App\Actions\Shipping;

use App\Models\ShippingMethod;
use App\Models\ShippingZone;

/**
 * Remove a shipping method from a zone.
 */
class RemoveMethodFromZoneAction
{
    public function execute(ShippingZone $zone, ShippingMethod $method): void
    {
        $zone->methods()->detach($method->id);
    }
}
