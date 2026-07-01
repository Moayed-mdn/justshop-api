<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Store;
use App\Models\StoreAddressSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing store-specific address validation settings.
 * 
 * Handles CRUD operations for address settings and provides
 * convenient methods for address validation.
 */
class StoreAddressSettingsService
{
    /**
     * Get address settings for a store, creating defaults if not exists.
     */
    public function getSettings(Store $store): StoreAddressSetting
    {
        return Cache::remember(
            "store_address_settings:{$store->id}",
            now()->addHours(24),
            fn() => StoreAddressSetting::firstOrCreate(
                ['store_id' => $store->id],
                $this->getDefaultSettings($store)
            )
        );
    }

    /**
     * Update address settings for a store.
     */
    public function updateSettings(Store $store, array $data): StoreAddressSetting
    {
        $settings = StoreAddressSetting::firstOrCreate(
            ['store_id' => $store->id],
            $this->getDefaultSettings($store)
        );

        $settings->update([
            'allowed_countries' => $data['allowed_countries'] ?? $settings->allowed_countries,
            'required_fields' => $data['required_fields'] ?? $settings->required_fields,
            'validation_rules' => $data['validation_rules'] ?? $settings->validation_rules,
            'require_phone' => $data['require_phone'] ?? $settings->require_phone,
            'require_company' => $data['require_company'] ?? $settings->require_company,
            'allow_po_boxes' => $data['allow_po_boxes'] ?? $settings->allow_po_boxes,
        ]);

        // Clear cache
        Cache::forget("store_address_settings:{$store->id}");

        Log::info('Store address settings updated', [
            'store_id' => $store->id,
        ]);

        return $settings->fresh();
    }

    /**
     * Get a frontend-safe payload for the store's address settings.
     */
    public function getSettingsPayload(Store $store): array
    {
        return $this->formatSettingsPayload($this->getSettings($store));
    }

    /**
     * Validate an address against store-specific rules.
     * 
     * @param Store $store
     * @param array $addressData
     * @return array Array of validation error messages (empty if valid)
     */
    public function validateAddressForStore(Store $store, array $addressData): array
    {
        $settings = $this->getSettings($store);
        return $settings->validateAddress($addressData);
    }

    /**
     * Get list of countries allowed for shipping to this store.
     */
    public function getAvailableCountries(Store $store): array
    {
        return $this->getSettingsPayload($store)['allowed_countries'];
    }

    /**
     * Check if a country is allowed for a store.
     */
    public function isCountryAllowed(Store $store, string $countryCode): bool
    {
        $settings = $this->getSettings($store);
        return $settings->isCountryAllowed($countryCode);
    }

    /**
     * Get required fields for addresses in this store.
     */
    public function getRequiredFields(Store $store): array
    {
        return $this->getSettingsPayload($store)['required_fields'];
    }

    /**
     * Normalize address input before validation/persistence.
     */
    public function normalizeAddressData(array $addressData): array
    {
        return [
            'name' => $this->nullableTrimmedString($addressData['name'] ?? null),
            'first_name' => $this->trimmedString($addressData['first_name'] ?? null),
            'last_name' => $this->trimmedString($addressData['last_name'] ?? null),
            'company' => $this->nullableTrimmedString($addressData['company'] ?? null),
            'address_line_1' => $this->trimmedString($addressData['address_line_1'] ?? null),
            'address_line_2' => $this->nullableTrimmedString($addressData['address_line_2'] ?? null),
            'city' => $this->trimmedString($addressData['city'] ?? null),
            'state' => $this->trimmedString($addressData['state'] ?? null),
            'postal_code' => $this->trimmedString($addressData['postal_code'] ?? null),
            'country' => strtoupper($this->trimmedString($addressData['country'] ?? null)),
            'phone' => $this->nullableTrimmedString($addressData['phone'] ?? null),
            'email' => $this->nullableTrimmedString($addressData['email'] ?? null),
        ];
    }

    /**
     * Convert validation messages into field-aware issues for API responses.
     *
     * @param array<int, string> $messages
     * @return array<int, array{field: string, message: string}>
     */
    public function formatValidationIssues(array $messages): array
    {
        return array_map(function (string $message): array {
            return [
                'field' => $this->inferFieldFromMessage($message),
                'message' => $message,
            ];
        }, $messages);
    }

    /**
     * Convert validation messages into a field => messages structure.
     *
     * @param array<int, string> $messages
     * @return array<string, array<int, string>>
     */
    public function formatValidationErrors(array $messages): array
    {
        $errors = [];

        foreach ($this->formatValidationIssues($messages) as $issue) {
            $errors[$issue['field']][] = $issue['message'];
        }

        return $errors;
    }

    /**
     * Get default settings based on store configuration.
     */
    private function getDefaultSettings(Store $store): array
    {
        $currency = $store->currency ?? 'USD';
        
        return [
            'allowed_countries' => $this->getDefaultCountriesForCurrency($currency),
            'required_fields' => StoreAddressSetting::getDefaultRequiredFields(),
            'validation_rules' => $this->getDefaultValidationRules(),
            'require_phone' => false,
            'require_company' => false,
            'allow_po_boxes' => true,
        ];
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
            'SAR' => ['SA', 'AE', 'KW', 'QA', 'BH', 'OM'],
            'EGP' => ['EG', 'SA', 'AE', 'JO'],
            default => ['US', 'CA', 'GB', 'AU', 'DE', 'FR'],
        };
    }

    /**
     * Get default validation rules for common countries.
     */
    private function getDefaultValidationRules(): array
    {
        return [
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
                'AU' => [
                    'pattern' => '^\d{4}$',
                    'example' => '2000',
                ],
                'DE' => [
                    'pattern' => '^\d{5}$',
                    'example' => '10115',
                ],
                'FR' => [
                    'pattern' => '^\d{5}$',
                    'example' => '75001',
                ],
            ],
        ];
    }

    /**
     * Reset settings to defaults for a store.
     */
    public function resetToDefaults(Store $store): StoreAddressSetting
    {
        $settings = StoreAddressSetting::where('store_id', $store->id)->first();
        
        if ($settings) {
            $settings->update($this->getDefaultSettings($store));
        } else {
            $settings = StoreAddressSetting::create([
                'store_id' => $store->id,
                ...$this->getDefaultSettings($store),
            ]);
        }

        Cache::forget("store_address_settings:{$store->id}");

        Log::info('Store address settings reset to defaults', [
            'store_id' => $store->id,
        ]);

        return $settings->fresh();
    }

    private function formatSettingsPayload(StoreAddressSetting $settings): array
    {
        return [
            'allowed_countries' => $settings->allowed_countries ?? [],
            'required_fields' => $settings->required_fields ?? StoreAddressSetting::getDefaultRequiredFields(),
            'validation_rules' => $settings->validation_rules ?? [],
            'require_phone' => (bool) $settings->require_phone,
            'require_company' => (bool) $settings->require_company,
            'allow_po_boxes' => (bool) $settings->allow_po_boxes,
        ];
    }

    private function inferFieldFromMessage(string $message): string
    {
        if (preg_match('/^The ([a-zA-Z0-9_ ]+) field is required\.$/', $message, $matches) === 1) {
            return str_replace(' ', '_', strtolower($matches[1]));
        }

        if (str_contains($message, 'Shipping to')) {
            return 'country';
        }

        if (str_contains($message, 'Phone number')) {
            return 'phone';
        }

        if (str_contains($message, 'Company name')) {
            return 'company';
        }

        if (str_contains($message, 'PO Box')) {
            return 'address_line_1';
        }

        if (preg_match('/Invalid format for ([a-zA-Z0-9_]+)/', $message, $matches) === 1) {
            return strtolower($matches[1]);
        }

        return 'address';
    }

    private function trimmedString(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function nullableTrimmedString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }
}
