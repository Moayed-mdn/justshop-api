<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Store-specific address validation settings.
 * 
 * Allows store owners to configure which countries they ship to,
 * which address fields are required, and custom validation rules.
 */
class StoreAddressSetting extends Model
{
    protected $fillable = [
        'store_id',
        'allowed_countries',
        'required_fields',
        'validation_rules',
        'require_phone',
        'require_company',
        'allow_po_boxes',
    ];

    protected $casts = [
        'allowed_countries' => 'array',
        'required_fields' => 'array',
        'validation_rules' => 'array',
        'require_phone' => 'boolean',
        'require_company' => 'boolean',
        'allow_po_boxes' => 'boolean',
    ];

    /**
     * Get the store that owns these settings.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Check if a country code is allowed for shipping.
     */
    public function isCountryAllowed(string $countryCode): bool
    {
        $allowedCountries = $this->allowed_countries ?? [];
        return in_array(strtoupper($countryCode), array_map('strtoupper', $allowedCountries));
    }

    /**
     * Validate an address against store-specific rules.
     * 
     * @param array $addressData Address data to validate
     * @return array Array of validation error messages (empty if valid)
     */
    public function validateAddress(array $addressData): array
    {
        $errors = [];

        // Check required fields
        $requiredFields = $this->required_fields ?? ['first_name', 'last_name', 'address_line_1', 'city', 'country', 'postal_code'];
        foreach ($requiredFields as $field) {
            if (empty($addressData[$field] ?? null)) {
                $fieldName = ucwords(str_replace('_', ' ', $field));
                $errors[] = "The {$fieldName} field is required.";
            }
        }

        // Check country allowance
        if (!empty($addressData['country'])) {
            if (!$this->isCountryAllowed($addressData['country'])) {
                $errors[] = "Shipping to {$addressData['country']} is not available for this store.";
            }
        }

        // Check phone requirement
        if ($this->require_phone && empty($addressData['phone'])) {
            $errors[] = "Phone number is required.";
        }

        // Check company requirement
        if ($this->require_company && empty($addressData['company'])) {
            $errors[] = "Company name is required.";
        }

        // Check PO Box restriction
        if (!$this->allow_po_boxes && !empty($addressData['address_line_1'])) {
            if ($this->isPOBox($addressData['address_line_1'])) {
                $errors[] = "PO Box addresses are not allowed.";
            }
        }

        // Apply custom validation rules
        if (!empty($this->validation_rules)) {
            $errors = array_merge($errors, $this->applyCustomValidationRules($addressData));
        }

        return $errors;
    }

    /**
     * Check if an address line appears to be a PO Box.
     */
    private function isPOBox(string $addressLine): bool
    {
        $patterns = [
            '/\bP\.?O\.?\s*BOX\b/i',
            '/\bPOST\s*OFFICE\s*BOX\b/i',
            '/\bPOSTAL\s*BOX\b/i',
            '/\bPMB\b/i', // Private Mail Box
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $addressLine)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply custom validation rules defined in the settings.
     */
    private function applyCustomValidationRules(array $addressData): array
    {
        $errors = [];

        foreach ($this->validation_rules as $field => $rules) {
            $value = $addressData[$field] ?? null;

            if (empty($value)) {
                continue;
            }

            // Handle country-specific rules (e.g., postal code patterns)
            if (is_array($rules) && isset($addressData['country'])) {
                $countryCode = strtoupper($addressData['country']);
                if (isset($rules[$countryCode])) {
                    $countryRules = $rules[$countryCode];
                    
                    if (isset($countryRules['pattern'])) {
                        $pattern = '/' . $countryRules['pattern'] . '/';
                        if (!preg_match($pattern, $value)) {
                            $example = $countryRules['example'] ?? '';
                            $errors[] = "Invalid format for {$field}. " . ($example ? "Example: {$example}" : '');
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Get the default required fields for addresses.
     */
    public static function getDefaultRequiredFields(): array
    {
        return [
            'first_name',
            'last_name',
            'address_line_1',
            'city',
            'state',
            'postal_code',
            'country',
        ];
    }

    /**
     * Get common country codes organized by region.
     */
    public static function getCommonCountries(): array
    {
        return [
            'North America' => ['US', 'CA', 'MX'],
            'Europe' => ['GB', 'DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'CH', 'SE', 'NO', 'DK', 'FI', 'IE', 'PT'],
            'Asia Pacific' => ['AU', 'NZ', 'JP', 'CN', 'HK', 'SG', 'MY', 'TH', 'PH', 'ID', 'VN', 'IN', 'KR'],
            'Middle East' => ['AE', 'SA', 'KW', 'QA', 'BH', 'OM', 'IL', 'TR'],
            'Latin America' => ['BR', 'AR', 'CL', 'CO', 'PE', 'VE', 'EC'],
            'Africa' => ['ZA', 'EG', 'NG', 'KE', 'MA'],
        ];
    }
}
