<?php

namespace App\Models;

use App\Enums\Subscription\BillingCycleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'billing_cycle',
        'currency',
        'amount_cents',
        'provider',
        'provider_price_id',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'plan_id'       => 'integer',
        'billing_cycle' => BillingCycleEnum::class,
        'amount_cents'  => 'integer',
        'is_active'     => 'boolean',
        'metadata'      => 'array',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptionItems(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }

    /**
     * Get formatted price amount.
     */
    public function getFormattedAmount(): string
    {
        $amount = $this->amount_cents / 100;
        return number_format($amount, 2);
    }

    /**
     * Get price with currency symbol.
     */
    public function getFormattedPrice(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        $symbol = $symbols[$this->currency] ?? $this->currency . ' ';
        return $symbol . $this->getFormattedAmount();
    }

    /**
     * Scope to get only active prices.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
