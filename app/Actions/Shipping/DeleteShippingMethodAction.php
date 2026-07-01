<?php

namespace App\Actions\Shipping;

use App\Models\ShippingMethod;

/**
 * Delete a shipping method.
 * 
 * Note: Should check if method is in use by orders before deletion.
 */
class DeleteShippingMethodAction
{
    public function execute(ShippingMethod $method): bool
    {
        // Detach from all zones first
        $method->zones()->detach();
        
        return $method->delete();
    }
}
