<?php

namespace App\Models;

use App\Enums\Subscription\PlanTierEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'tier',
        'is_public',
        'is_active',
        'trial_days',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'name'        => 'array',
        'description' => 'array',
        'tier'        => PlanTierEnum::class,
        'is_public'   => 'boolean',
        'is_active'   => 'boolean',
        'trial_days'  => 'integer',
        'sort_order'  => 'integer',
        'metadata'    => 'array',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get active prices for this plan.
     */
    public function activePrices(): HasMany
    {
        return $this->prices()->where('is_active', true);
    }

    /**
     * Get monthly price for a specific currency.
     */
    public function getMonthlyPrice(string $currency = 'USD'): ?PlanPrice
    {
        return $this->activePrices()
            ->where('billing_cycle', 'monthly')
            ->where('currency', $currency)
            ->first();
    }

    /**
     * Get annual price for a specific currency.
     */
    public function getAnnualPrice(string $currency = 'USD'): ?PlanPrice
    {
        return $this->activePrices()
            ->where('billing_cycle', 'annual')
            ->where('currency', $currency)
            ->first();
    }

    /**
     * Scope to get only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only public plans.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Get feature value by key.
     */
    public function getFeature(string $featureKey): ?PlanFeature
    {
        return $this->features()->where('feature_key', $featureKey)->first();
    }

    /**
     * Get numeric tier value for comparison.
     */
    public function tier_value(): int
    {
        return $this->tier->tier();
    }
}
