<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Store;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing shipping methods and calculating shipping costs.
 * 
 * Handles shipping method CRUD, availability checks, and zone-based pricing.
 */
class ShippingMethodService
{
    /**
     * Get all shipping methods for a store.
     */
    public function getMethodsForStore(Store $store, bool $activeOnly = true): Collection
    {
        $query = ShippingMethod::where('store_id', $store->id);
        
        if ($activeOnly) {
            $query->where('is_active', true);
        }
        
        return $query->ordered()->get();
    }

    /**
     * Get available shipping methods for an address and order amount.
     * 
     * @param Store $store
     * @param array $address Address with country, state, postal_code
     * @param float $orderAmount Order subtotal
     * @return Collection
     */
    public function getAvailableMethodsForAddress(
        Store $store,
        array $address,
        float $orderAmount
    ): Collection {
        // Get all active methods for the store that match order amount
        $methods = ShippingMethod::where('store_id', $store->id)
            ->active()
            ->availableForAmount($orderAmount)
            ->ordered()
            ->with('zones')
            ->get();

        // Filter methods that are available for the address
        $availableMethods = $methods->filter(function ($method) use ($address) {
            // If method has no zones, it's available everywhere
            if ($method->zones->isEmpty()) {
                return true;
            }

            // Check if address is in any of the method's zones
            foreach ($method->zones as $zone) {
                if ($zone->is_active && $zone->includesAddress($address)) {
                    return true;
                }
            }

            return false;
        });

        // Enhance each method with zone-specific pricing
        return $availableMethods->map(function ($method) use ($address) {
            $zone = $this->findZoneForAddress($method->zones, $address);
            $method->effective_price = $method->getPriceForZone($zone);
            $method->zone_name = $zone?->name;
            return $method;
        });
    }

    /**
     * Find the matching zone for an address from a collection of zones.
     */
    private function findZoneForAddress(Collection $zones, array $address): ?ShippingZone
    {
        return $zones->first(function ($zone) use ($address) {
            return $zone->is_active && $zone->includesAddress($address);
        });
    }

    /**
     * Get a single shipping method by ID with store verification.
     */
    public function getMethod(Store $store, int $methodId): ?ShippingMethod
    {
        return ShippingMethod::where('store_id', $store->id)
            ->where('id', $methodId)
            ->first();
    }

    /**
     * Create a new shipping method for a store.
     */
    public function createMethod(Store $store, array $data): ShippingMethod
    {
        return DB::transaction(function () use ($store, $data) {
            $method = ShippingMethod::create([
                'store_id' => $store->id,
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'currency' => $data['currency'] ?? $store->currency ?? 'USD',
                'min_order_amount' => $data['min_order_amount'] ?? null,
                'max_order_amount' => $data['max_order_amount'] ?? null,
                'estimated_delivery_days' => $data['estimated_delivery_days'] ?? null,
                'min_delivery_days' => $data['min_delivery_days'] ?? null,
                'max_delivery_days' => $data['max_delivery_days'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            // Attach to zones if provided
            if (!empty($data['zone_ids'])) {
                $this->attachZones($method, $data['zone_ids'], $data['zone_price_overrides'] ?? []);
            }

            Log::info('Shipping method created', [
                'store_id' => $store->id,
                'method_id' => $method->id,
                'created_by' => auth()->id(),
            ]);

            return $method->load('zones');
        });
    }

    /**
     * Update an existing shipping method.
     */
    public function updateMethod(ShippingMethod $method, array $data): ShippingMethod
    {
        return DB::transaction(function () use ($method, $data) {
            $method->update([
                'name' => $data['name'] ?? $method->name,
                'code' => $data['code'] ?? $method->code,
                'description' => $data['description'] ?? $method->description,
                'price' => $data['price'] ?? $method->price,
                'currency' => $data['currency'] ?? $method->currency,
                'min_order_amount' => $data['min_order_amount'] ?? $method->min_order_amount,
                'max_order_amount' => $data['max_order_amount'] ?? $method->max_order_amount,
                'estimated_delivery_days' => $data['estimated_delivery_days'] ?? $method->estimated_delivery_days,
                'min_delivery_days' => $data['min_delivery_days'] ?? $method->min_delivery_days,
                'max_delivery_days' => $data['max_delivery_days'] ?? $method->max_delivery_days,
                'is_active' => $data['is_active'] ?? $method->is_active,
                'sort_order' => $data['sort_order'] ?? $method->sort_order,
            ]);

            // Update zone associations if provided
            if (isset($data['zone_ids'])) {
                $method->zones()->detach();
                if (!empty($data['zone_ids'])) {
                    $this->attachZones($method, $data['zone_ids'], $data['zone_price_overrides'] ?? []);
                }
            }

            Log::info('Shipping method updated', [
                'store_id' => $method->store_id,
                'method_id' => $method->id,
                'updated_by' => auth()->id(),
            ]);

            return $method->fresh(['zones']);
        });
    }

    /**
     * Delete a shipping method.
     */
    public function deleteMethod(ShippingMethod $method): bool
    {
        return DB::transaction(function () use ($method) {
            $storeId = $method->store_id;
            $methodId = $method->id;

            // Detach from zones
            $method->zones()->detach();
            
            // Delete the method
            $deleted = $method->delete();

            if ($deleted) {
                Log::info('Shipping method deleted', [
                    'store_id' => $storeId,
                    'method_id' => $methodId,
                    'deleted_by' => auth()->id(),
                ]);
            }

            return $deleted;
        });
    }

    /**
     * Attach zones to a shipping method with optional price overrides.
     */
    private function attachZones(ShippingMethod $method, array $zoneIds, array $priceOverrides = []): void
    {
        $syncData = [];
        
        foreach ($zoneIds as $zoneId) {
            $syncData[$zoneId] = [
                'price_override' => $priceOverrides[$zoneId] ?? null,
            ];
        }

        $method->zones()->sync($syncData);
    }

    /**
     * Toggle active status of a shipping method.
     */
    public function toggleActive(ShippingMethod $method): ShippingMethod
    {
        $method->update(['is_active' => !$method->is_active]);

        Log::info('Shipping method status toggled', [
            'store_id' => $method->store_id,
            'method_id' => $method->id,
            'is_active' => $method->is_active,
            'toggled_by' => auth()->id(),
        ]);

        return $method->fresh();
    }

    /**
     * Bulk update sort order for methods.
     */
    public function updateSortOrder(Store $store, array $methodIdToSortOrder): void
    {
        DB::transaction(function () use ($store, $methodIdToSortOrder) {
            foreach ($methodIdToSortOrder as $methodId => $sortOrder) {
                ShippingMethod::where('store_id', $store->id)
                    ->where('id', $methodId)
                    ->update(['sort_order' => $sortOrder]);
            }

            Log::info('Shipping method sort order updated', [
                'store_id' => $store->id,
                'updated_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Calculate shipping cost for a specific method and address.
     */
    public function calculateShippingCost(
        ShippingMethod $method,
        array $address,
        float $orderAmount
    ): ?float {
        // Check if method is available for order amount
        if (!$method->isAvailableForOrder($orderAmount)) {
            return null;
        }

        // Check if method is available for address
        if (!$method->isAvailableForCountry($address['country'] ?? '')) {
            return null;
        }

        // Find the appropriate zone
        $zone = $this->findZoneForAddress($method->zones, $address);

        // Return zone-specific price or base price
        return $method->getPriceForZone($zone);
    }
}
