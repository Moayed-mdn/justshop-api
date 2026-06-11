<?php

namespace App\Models;

use App\Enums\Subscription\SubscriptionEventTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'event_type',
        'from_status',
        'to_status',
        'actor_user_id',
        'source',
        'reason',
        'payload',
    ];

    protected $casts = [
        'subscription_id' => 'integer',
        'event_type'      => SubscriptionEventTypeEnum::class,
        'actor_user_id'   => 'integer',
        'payload'         => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * Check if event was triggered by webhook.
     */
    public function isWebhookTriggered(): bool
    {
        return $this->source === 'webhook';
    }

    /**
     * Check if event was triggered by system.
     */
    public function isSystemTriggered(): bool
    {
        return $this->source === 'system';
    }

    /**
     * Check if event was triggered by merchant.
     */
    public function isMerchantTriggered(): bool
    {
        return $this->source === 'merchant';
    }

    /**
     * Scope to get events by type.
     */
    public function scopeOfType($query, SubscriptionEventTypeEnum $type)
    {
        return $query->where('event_type', $type->value);
    }

    /**
     * Scope to get events by source.
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }
}
