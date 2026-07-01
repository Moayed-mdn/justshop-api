<?php

namespace App\Actions\Shipping;

use App\Models\ShippingMethod;
use App\Models\ShippingZone;

/**
 * Update the price override for a method in a specific zone.
 */
class UpdateZoneMethodPriceAction
{
    public function execute(ShippingZone $zone, ShippingMethod $method, ?float $priceOverride): void
    {
        $zone->methods()->updateExistingPivot($method->id, [
            'price_override' => $priceOverride
        ]);
    }
}
