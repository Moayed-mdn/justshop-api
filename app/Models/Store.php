<?php

namespace App\Models;

use App\Enums\Store\StoreStatusEnum;
use App\Enums\Store\ProvisioningStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'domain',
        'currency',
        'timezone',
        'is_active',
        'status',
        'status_changed_at',
        'status_changed_by_actor_type',
        'status_changed_by_actor_id',
        'setup_completed_at',
        'provisioning_status',
        'provisioning_progress',
        'provisioning_current_step',
        'provisioning_message',
        'provisioning_retryable',
        'provisioning_started_at',
        'provisioning_last_heartbeat_at',
        'provisioning_completed_at',
        'provisioning_failed_at',
        'provisioning_attempts',
        'provisioning_last_error',
    ];

    protected function casts(): array
    {
        return [
            'is_active'          => 'boolean',
            'status'             => StoreStatusEnum::class,
            'status_changed_at'  => 'datetime',
            'setup_completed_at' => 'datetime',
            'provisioning_status' => ProvisioningStatusEnum::class,
            'provisioning_progress' => 'integer',
            'provisioning_retryable' => 'boolean',
            'provisioning_started_at' => 'datetime',
            'provisioning_last_heartbeat_at' => 'datetime',
            'provisioning_completed_at' => 'datetime',
            'provisioning_failed_at' => 'datetime',
            'provisioning_attempts' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_user')
            ->withPivot([
                'role',
                'lifecycle_status',
                'lifecycle_changed_at',
                'lifecycle_changed_by_actor_type',
                'lifecycle_changed_by_actor_id'
            ])
            ->withTimestamps();
    }

    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function carts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Cart::class);
    }

    // ── Helpers ────────────────────────────────────────────────

    /**
     * Returns true if the store is fully operational.
     * Checks both the lifecycle status and the is_active flag for
     * backwards compatibility during the transition period.
     */
    public function isOperational(): bool
    {
        return $this->is_active && $this->status === StoreStatusEnum::ACTIVE;
    }
}
