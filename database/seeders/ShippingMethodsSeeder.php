<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShippingMethodsSeeder extends Seeder
{
    /**
     * Seed default shipping methods and zones for all stores.
     * 
     * Creates basic shipping configuration that store owners
     * can customize to match their fulfillment capabilities.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $stores = Store::all();
            
            foreach ($stores as $store) {
                $this->command->info("Setting up shipping for store #{$store->id} ({$store->name})");
                
                // Create shipping methods
                $methods = $this->createShippingMethods($store);
                
                // Create shipping zones
                $zones = $this->createShippingZones($store);
                
                // Link methods to zones
                $this->linkMethodsToZones($store, $methods, $zones);
                
                $this->command->info("Completed shipping setup for store #{$store->id}");
            }

            $this->command->info('Shipping methods and zones seeding completed!');
        });
    }

    /**
     * Create default shipping methods for a store.
     */
    private function createShippingMethods(Store $store): array
    {
        $currency = $store->currency ?? 'USD';
        
        $methodsData = [
            [
                'name' => 'Standard Shipping',
                'code' => 'standard',
                'description' => 'Delivery in 5-7 business days',
                'price' => 9.99,
                'min_delivery_days' => 5,
                'max_delivery_days' => 7,
                'estimated_delivery_days' => 6,
                'sort_order' => 1,
            ],
            [
                'name' => 'Express Shipping',
                'code' => 'express',
                'description' => 'Delivery in 2-3 business days',
                'price' => 19.99,
                'min_delivery_days' => 2,
                'max_delivery_days' => 3,
                'estimated_delivery_days' => 3,
                'sort_order' => 2,
            ],
            [
                'name' => 'Overnight Shipping',
                'code' => 'overnight',
                'description' => 'Next business day delivery',
                'price' => 29.99,
                'min_delivery_days' => 1,
                'max_delivery_days' => 1,
                'estimated_delivery_days' => 1,
                'min_order_amount' => 50.00,
                'sort_order' => 3,
            ],
            [
                'name' => 'Free Shipping',
                'code' => 'free',
                'description' => 'Free delivery on orders over $100',
                'price' => 0.00,
                'min_delivery_days' => 5,
                'max_delivery_days' => 10,
                'estimated_delivery_days' => 7,
                'min_order_amount' => 100.00,
                'sort_order' => 0,
            ],
        ];

        $methods = [];
        
        foreach ($methodsData as $data) {
            // Skip if method already exists
            $existing = ShippingMethod::where('store_id', $store->id)
                ->where('code', $data['code'])
                ->first();
                
            if ($existing) {
                $methods[] = $existing;
                continue;
            }

            $method = ShippingMethod::create([
                'store_id' => $store->id,
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'],
                'price' => $data['price'],
                'currency' => $currency,
                'min_order_amount' => $data['min_order_amount'] ?? null,
                'max_order_amount' => $data['max_order_amount'] ?? null,
                'min_delivery_days' => $data['min_delivery_days'],
                'max_delivery_days' => $data['max_delivery_days'],
                'estimated_delivery_days' => $data['estimated_delivery_days'],
                'is_active' => true,
                'sort_order' => $data['sort_order'],
            ]);

            $methods[] = $method;
            $this->command->info("  - Created shipping method: {$method->name}");
        }

        return $methods;
    }

    /**
     * Create default shipping zones for a store.
     */
    private function createShippingZones(Store $store): array
    {
        $zonesData = [
            [
                'name' => 'Domestic',
                'countries' => $this->getDomesticCountries($store),
            ],
            [
                'name' => 'International',
                'countries' => $this->getInternationalCountries($store),
            ],
        ];

        $zones = [];
        
        foreach ($zonesData as $data) {
            // Skip if zone already exists
            $existing = ShippingZone::where('store_id', $store->id)
                ->where('name', $data['name'])
                ->first();
                
            if ($existing) {
                $zones[] = $existing;
                continue;
            }

            $zone = ShippingZone::create([
                'store_id' => $store->id,
                'name' => $data['name'],
                'countries' => $data['countries'],
                'regions' => null,
                'postal_code_patterns' => null,
                'is_active' => true,
            ]);

            $zones[] = $zone;
            $this->command->info("  - Created shipping zone: {$zone->name} (" . count($data['countries']) . " countries)");
        }

        return $zones;
    }

    /**
     * Link shipping methods to zones with appropriate pricing.
     */
    private function linkMethodsToZones(Store $store, array $methods, array $zones): void
    {
        foreach ($zones as $zone) {
            foreach ($methods as $method) {
                // Check if link already exists
                $exists = DB::table('shipping_zone_methods')
                    ->where('shipping_zone_id', $zone->id)
                    ->where('shipping_method_id', $method->id)
                    ->exists();
                    
                if ($exists) {
                    continue;
                }

                // Apply price multiplier for international zones
                $priceOverride = null;
                if ($zone->name === 'International' && $method->price > 0) {
                    $priceOverride = $method->price * 2; // Double price for international
                }

                DB::table('shipping_zone_methods')->insert([
                    'shipping_zone_id' => $zone->id,
                    'shipping_method_id' => $method->id,
                    'price_override' => $priceOverride,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info("  - Linked {$zone->name} zone to " . count($methods) . " shipping methods");
    }

    /**
     * Get domestic countries based on store configuration.
     */
    private function getDomesticCountries(Store $store): array
    {
        $currency = $store->currency ?? 'USD';
        
        return match (strtoupper($currency)) {
            'USD' => ['US'],
            'EUR' => ['DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'PT', 'IE', 'FI', 'GR'],
            'GBP' => ['GB'],
            'JPY' => ['JP'],
            'AUD' => ['AU'],
            'CAD' => ['CA'],
            'CHF' => ['CH'],
            'CNY' => ['CN'],
            default => ['US'],
        };
    }

    /**
     * Get international countries (common shipping destinations).
     */
    private function getInternationalCountries(Store $store): array
    {
        // Common international shipping destinations
        return [
            'US', 'CA', 'GB', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL', 'BE', 
            'AT', 'CH', 'SE', 'NO', 'DK', 'FI', 'IE', 'PT', 'GR', 'PL',
            'CZ', 'HU', 'RO', 'BG', 'HR', 'SI', 'SK', 'LT', 'LV', 'EE',
            'JP', 'CN', 'HK', 'SG', 'MY', 'TH', 'PH', 'ID', 'VN', 'IN',
            'NZ', 'MX', 'BR', 'AR', 'CL', 'CO', 'PE', 'ZA', 'AE', 'SA',
        ];
    }
}
