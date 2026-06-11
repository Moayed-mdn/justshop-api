<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'plan_price_id',
        'provider_item_id',
        'quantity',
        'item_type',
        'metadata',
    ];

    protected $casts = [
        'subscription_id' => 'integer',
        'plan_price_id'   => 'integer',
        'quantity'        => 'integer',
        'metadata'        => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(PlanPrice::class);
    }

    /**
     * Check if this is the base subscription item.
     */
    public function isBase(): bool
    {
        return $this->item_type === 'base';
    }

    /**
     * Check if this is an addon item.
     */
    public function isAddon(): bool
    {
        return $this->item_type === 'addon';
    }

    /**
     * Check if this is a metered item.
     */
    public function isMetered(): bool
    {
        return $this->item_type === 'metered';
    }
}
