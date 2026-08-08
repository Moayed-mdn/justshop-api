<?php

namespace App\Models;

use App\Enums\Entitlement\EntitlementStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreEntitlementSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'billing_account_id',
        'subscription_id',
        'plan_id',
        'entitlement_status',
        'features',
        'products_count',
        'expires_at',
        'refreshed_at',
    ];

    protected $casts = [
        'store_id'           => 'integer',
        'billing_account_id' => 'integer',
        'subscription_id'    => 'integer',
        'plan_id'            => 'integer',
        'entitlement_status' => EntitlementStatusEnum::class,
        'features'           => 'array',
        'products_count'     => 'integer',
        'expires_at'         => 'datetime',
        'refreshed_at'       => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function billingAccount(): BelongsTo
    {
        return $this->belongsTo(BillingAccount::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Check if snapshot grants write access.
     */
    public function grantsWriteAccess(): bool
    {
        return $this->entitlement_status->grantsWriteAccess();
    }

    /**
     * Check if snapshot grants read access.
     */
    public function grantsReadAccess(): bool
    {
        return $this->entitlement_status->grantsReadAccess();
    }

    /**
     * Get feature value by key.
     */
    public function getFeature(string $key): mixed
    {
        return $this->features[$key] ?? null;
    }

    /**
     * Check if a specific feature is enabled.
     */
    public function hasFeature(string $key): bool
    {
        $value = $this->getFeature($key);
        
        // Boolean features
        if (is_bool($value)) {
            return $value;
        }
        
        // Numeric features (null = unlimited)
        return $value !== null;
    }

    /**
     * Check if quota is available for a feature.
     */
    public function hasQuotaAvailable(string $featureKey, int $currentCount): bool
    {
        $limit = $this->getFeature($featureKey);
        
        // Unlimited
        if ($limit === null) {
            return true;
        }
        
        // Check against limit
        return $currentCount < $limit;
    }

    /**
     * Check if snapshot is stale (needs refresh).
     */
    public function isStale(int $ttlMinutes = 60): bool
    {
        if (!$this->refreshed_at) {
            return true;
        }
        
        return $this->refreshed_at->diffInMinutes(now()) > $ttlMinutes;
    }

    /**
     * Check if snapshot is expired.
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        
        return $this->expires_at->isPast();
    }

    /**
     * Scope to get only write-enabled snapshots.
     */
    public function scopeWithWriteAccess($query)
    {
        return $query->whereIn('entitlement_status', [
            EntitlementStatusEnum::ENTITLED->value,
            EntitlementStatusEnum::TRIAL->value,
            EntitlementStatusEnum::GRANDFATHERED->value,
        ]);
    }

    /**
     * Scope to get stale snapshots.
     */
    public function scopeStale($query, int $ttlMinutes = 60)
    {
        return $query->where(function ($q) use ($ttlMinutes) {
            $q->whereNull('refreshed_at')
              ->orWhere('refreshed_at', '<=', now()->subMinutes($ttlMinutes));
        });
    }
}
