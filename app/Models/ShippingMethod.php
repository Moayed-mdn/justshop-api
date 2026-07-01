<?php

namespace App\Models;

use App\Models\Concerns\HasStoreScoping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shipping method configuration for a store.
 * 
 * Defines available shipping options with pricing, delivery estimates,
 * and order amount restrictions.
 */
class ShippingMethod extends Model
{
    use HasStoreScoping;

    protected $fillable = [
        'store_id',
        'name',
        'code',
        'description',
        'price',
        'currency',
        'min_order_amount',
        'max_order_amount',
        'estimated_delivery_days',
        'min_delivery_days',
        'max_delivery_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_order_amount' => 'decimal:2',
        'estimated_delivery_days' => 'integer',
        'min_delivery_days' => 'integer',
        'max_delivery_days' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    /**
     * Get the store that owns this shipping method.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the shipping zones where this method is available.
     */
    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(ShippingZone::class, 'shipping_zone_methods')
            ->withPivot('price_override')
            ->withTimestamps();
    }

    /**
     * Get orders that used this shipping method.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Check if this method is available for a given order amount.
     * 
     * @param float $orderAmount The order subtotal
     * @return bool True if the method is available for this order amount
     */
    public function isAvailableForOrder(float $orderAmount): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->min_order_amount && $orderAmount < $this->min_order_amount) {
            return false;
        }

        if ($this->max_order_amount && $orderAmount > $this->max_order_amount) {
            return false;
        }

        return true;
    }

    /**
     * Check if this method is available for a specific country.
     * 
     * @param string $countryCode ISO 2-letter country code
     * @return bool True if method is available in this country
     */
    public function isAvailableForCountry(string $countryCode): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // If no zones are defined, method is available everywhere
        $zones = $this->zones()->where('is_active', true)->get();
        if ($zones->isEmpty()) {
            return true;
        }

        // Check if country is in any of the method's zones
        foreach ($zones as $zone) {
            if ($zone->includesCountry($countryCode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the price for this method in a specific zone.
     * 
     * @param ShippingZone|null $zone
     * @return float The price (base or zone-specific override)
     */
    public function getPriceForZone(?ShippingZone $zone = null): float
    {
        if (!$zone) {
            return (float) $this->price;
        }

        // Check if there's a price override for this zone
        $pivot = $this->zones()->where('shipping_zone_id', $zone->id)->first()?->pivot;
        
        if ($pivot && $pivot->price_override !== null) {
            return (float) $pivot->price_override;
        }

        return (float) $this->price;
    }

    /**
     * Get formatted delivery time estimate.
     * 
     * @return string Human-readable delivery estimate
     */
    public function getDeliveryEstimate(): string
    {
        if ($this->min_delivery_days && $this->max_delivery_days) {
            if ($this->min_delivery_days === $this->max_delivery_days) {
                return "{$this->min_delivery_days} business " . 
                    ($this->min_delivery_days === 1 ? 'day' : 'days');
            }
            return "{$this->min_delivery_days}-{$this->max_delivery_days} business days";
        }

        if ($this->estimated_delivery_days) {
            return "{$this->estimated_delivery_days} business days";
        }

        return 'Delivery time varies';
    }

    /**
     * Get formatted price display.
     * 
     * @param ShippingZone|null $zone
     * @return string Formatted price with currency
     */
    public function getFormattedPrice(?ShippingZone $zone = null): string
    {
        $price = $this->getPriceForZone($zone);
        
        if ($price == 0) {
            return 'Free';
        }

        // Basic currency formatting (could be enhanced with Intl)
        $currencySymbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
        ];

        $symbol = $currencySymbols[$this->currency] ?? $this->currency . ' ';
        
        return $symbol . number_format($price, 2);
    }

    /**
     * Scope query to only active methods.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to methods available for a specific order amount.
     */
    public function scopeAvailableForAmount($query, float $amount)
    {
        return $query->where('is_active', true)
            ->where(function ($q) use ($amount) {
                $q->whereNull('min_order_amount')
                    ->orWhere('min_order_amount', '<=', $amount);
            })
            ->where(function ($q) use ($amount) {
                $q->whereNull('max_order_amount')
                    ->orWhere('max_order_amount', '>=', $amount);
            });
    }

    /**
     * Scope query to order by sort_order, then by price.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')
            ->orderBy('price', 'asc');
    }
}
