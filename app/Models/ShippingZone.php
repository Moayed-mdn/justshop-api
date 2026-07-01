<?php

namespace App\Models;

use App\Models\Concerns\HasStoreScoping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Geographic shipping zone configuration.
 * 
 * Groups countries/regions together for shipping method assignment
 * and zone-specific pricing.
 */
class ShippingZone extends Model
{
    use HasStoreScoping;

    protected $fillable = [
        'store_id',
        'name',
        'countries',
        'regions',
        'postal_code_patterns',
        'is_active',
    ];

    protected $casts = [
        'countries' => 'array',
        'regions' => 'array',
        'postal_code_patterns' => 'array',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the store that owns this zone.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the shipping methods available in this zone.
     */
    public function methods(): BelongsToMany
    {
        return $this->belongsToMany(ShippingMethod::class, 'shipping_zone_methods')
            ->withPivot('price_override')
            ->withTimestamps();
    }

    /**
     * Check if a country is included in this zone.
     * 
     * @param string $countryCode ISO 2-letter country code
     * @return bool True if country is in this zone
     */
    public function includesCountry(string $countryCode): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $countries = $this->countries ?? [];
        return in_array(strtoupper($countryCode), array_map('strtoupper', $countries));
    }

    /**
     * Check if a region (state/province) is included in this zone.
     * 
     * @param string $countryCode ISO 2-letter country code
     * @param string $regionCode Region/state code
     * @return bool True if region is in this zone
     */
    public function includesRegion(string $countryCode, string $regionCode): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // First check if country is in zone
        if (!$this->includesCountry($countryCode)) {
            return false;
        }

        // If no specific regions defined, all regions in country are included
        if (empty($this->regions)) {
            return true;
        }

        $regions = $this->regions ?? [];
        $countryKey = strtoupper($countryCode);

        // Check if regions are defined for this country
        if (!isset($regions[$countryKey])) {
            return true; // No specific regions defined for this country
        }

        $countryRegions = $regions[$countryKey];
        return in_array(strtoupper($regionCode), array_map('strtoupper', $countryRegions));
    }

    /**
     * Check if a postal code matches this zone.
     * 
     * @param string $countryCode ISO 2-letter country code
     * @param string $postalCode Postal/ZIP code
     * @return bool True if postal code matches zone patterns
     */
    public function matchesPostalCode(string $countryCode, string $postalCode): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // First check if country is in zone
        if (!$this->includesCountry($countryCode)) {
            return false;
        }

        // If no postal code patterns defined, all postal codes match
        if (empty($this->postal_code_patterns)) {
            return true;
        }

        $patterns = $this->postal_code_patterns ?? [];
        $countryKey = strtoupper($countryCode);

        // Check if patterns are defined for this country
        if (!isset($patterns[$countryKey])) {
            return true; // No specific patterns for this country
        }

        $countryPatterns = $patterns[$countryKey];
        
        foreach ($countryPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $postalCode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an address is included in this zone.
     * 
     * @param array $address Address data with country, state, postal_code
     * @return bool True if address is in this zone
     */
    public function includesAddress(array $address): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $countryCode = $address['country'] ?? null;
        if (!$countryCode) {
            return false;
        }

        // Check country
        if (!$this->includesCountry($countryCode)) {
            return false;
        }

        // Check region if specified
        $regionCode = $address['state'] ?? null;
        if ($regionCode && !empty($this->regions)) {
            if (!$this->includesRegion($countryCode, $regionCode)) {
                return false;
            }
        }

        // Check postal code if specified
        $postalCode = $address['postal_code'] ?? null;
        if ($postalCode && !empty($this->postal_code_patterns)) {
            if (!$this->matchesPostalCode($countryCode, $postalCode)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get available shipping methods for this zone.
     * 
     * @param float|null $orderAmount Optional order amount to filter by
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableMethods(?float $orderAmount = null)
    {
        $query = $this->methods()->where('is_active', true);

        if ($orderAmount !== null) {
            $query->where(function ($q) use ($orderAmount) {
                $q->whereNull('min_order_amount')
                    ->orWhere('min_order_amount', '<=', $orderAmount);
            })->where(function ($q) use ($orderAmount) {
                $q->whereNull('max_order_amount')
                    ->orWhere('max_order_amount', '>=', $orderAmount);
            });
        }

        return $query->orderBy('sort_order', 'asc')
            ->orderBy('price', 'asc')
            ->get();
    }

    /**
     * Get the number of countries in this zone.
     * 
     * @return int
     */
    public function getCountryCount(): int
    {
        return count($this->countries ?? []);
    }

    /**
     * Scope query to only active zones.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to zones that include a specific country.
     */
    public function scopeForCountry($query, string $countryCode)
    {
        $countryCode = strtoupper($countryCode);
        return $query->where('is_active', true)
            ->whereJsonContains('countries', $countryCode);
    }

    /**
     * Scope query to zones that include a specific address.
     */
    public function scopeForAddress($query, array $address)
    {
        // This is a basic implementation. For production, you might want
        // to use raw SQL for more efficient JSON querying.
        return $query->where('is_active', true)->get()->filter(function ($zone) use ($address) {
            return $zone->includesAddress($address);
        });
    }
}
