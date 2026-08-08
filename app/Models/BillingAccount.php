<?php

namespace App\Models;

use App\Enums\Billing\BillingAccountStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_user_id',
        'billing_email',
        'legal_name',
        'country_code',
        'default_currency',
        'tax_id',
        'status',
        'trial_used',
        'stores_count',
        'stores_max',
        'metadata',
    ];

    protected $casts = [
        'owner_user_id'    => 'integer',
        'status'           => BillingAccountStatusEnum::class,
        'trial_used'       => 'boolean',
        'stores_count'     => 'integer',
        'stores_max'       => 'integer',
        'metadata'         => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function billingCustomers(): HasMany
    {
        return $this->hasMany(BillingCustomer::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(BillingAuditLog::class);
    }

    public function entitlementSnapshots(): HasMany
    {
        return $this->hasMany(StoreEntitlementSnapshot::class);
    }

    /**
     * Get the active subscription for this billing account.
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', [
                'trialing',
                'active',
                'past_due',
                'grace_period',
                'paused',
            ])
            ->latest();
    }

    /**
     * Get billing customer for a specific provider.
     */
    public function getBillingCustomer(string $provider = 'stripe'): ?BillingCustomer
    {
        return $this->billingCustomers()
            ->where('provider', $provider)
            ->first();
    }

    /**
     * Check if trial has been used.
     */
    public function hasUsedTrial(): bool
    {
        return $this->trial_used;
    }

    /**
     * Check if account is active.
     */
    public function isActive(): bool
    {
        return $this->status === BillingAccountStatusEnum::ACTIVE;
    }

    /**
     * Scope to get only active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', BillingAccountStatusEnum::ACTIVE->value);
    }
}
