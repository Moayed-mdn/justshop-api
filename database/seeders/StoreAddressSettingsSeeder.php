<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreAddressSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreAddressSettingsSeeder extends Seeder
{
    /**
     * Seed default address settings for all stores.
     * 
     * This seeder creates sensible defaults for address validation
     * that store owners can customize later.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $stores = Store::all();
            
            foreach ($stores as $store) {
                // Skip if settings already exist
                if (StoreAddressSetting::where('store_id', $store->id)->exists()) {
                    $this->command->info("Store #{$store->id} ({$store->name}) already has address settings, skipping.");
                    continue;
                }

                // Determine default countries based on store currency
                $defaultCountries = $this->getDefaultCountriesForCurrency($store->currency ?? 'USD');

                StoreAddressSetting::create([
                    'store_id' => $store->id,
                    'allowed_countries' => $defaultCountries,
                    'required_fields' => [
                        'first_name',
                        'last_name',
                        'address_line_1',
                        'city',
                        'state',
                        'postal_code',
                        'country',
                    ],
                    'validation_rules' => [
                        'postal_code' => [
                            'US' => [
                                'pattern' => '^\d{5}(-\d{4})?$',
                                'example' => '12345 or 12345-6789',
                            ],
                            'CA' => [
                                'pattern' => '^[A-Z]\d[A-Z] \d[A-Z]\d$',
                                'example' => 'K1A 0B1',
                            ],
                            'GB' => [
                                'pattern' => '^[A-Z]{1,2}\d{1,2}[A-Z]? \d[A-Z]{2}$',
                                'example' => 'SW1A 1AA',
                            ],
                        ],
                    ],
                    'require_phone' => false,
                    'require_company' => false,
                    'allow_po_boxes' => true,
                ]);

                $this->command->info("Created address settings for store #{$store->id} ({$store->name})");
            }

            $this->command->info('Store address settings seeding completed!');
        });
    }

    /**
     * Get appropriate default countries based on store currency.
     */
    private function getDefaultCountriesForCurrency(string $currency): array
    {
        return match (strtoupper($currency)) {
            'USD' => ['US', 'CA', 'MX'],
            'EUR' => ['DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'PT', 'IE', 'FI', 'GR'],
            'GBP' => ['GB', 'IE'],
            'JPY' => ['JP'],
            'AUD' => ['AU', 'NZ'],
            'CAD' => ['CA', 'US'],
            'CHF' => ['CH', 'LI'],
            'CNY' => ['CN', 'HK', 'MO'],
            'SEK' => ['SE', 'NO', 'DK', 'FI'],
            'NZD' => ['NZ', 'AU'],
            'INR' => ['IN'],
            'BRL' => ['BR'],
            'RUB' => ['RU'],
            'ZAR' => ['ZA'],
            'AED' => ['AE', 'SA', 'KW', 'QA', 'BH', 'OM'],
            // Default fallback: major shipping markets
            default => ['US', 'CA', 'GB', 'AU', 'DE', 'FR'],
        };
    }
}
