<?php

namespace App\Models;

use App\Enums\Subscription\BillingCycleEnum;
use App\Enums\Subscription\SubscriptionStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'billing_account_id',
        'plan_id',
        'plan_price_id',
        'status',
        'billing_cycle',
        'provider',
        'provider_subscription_id',
        'provider_status',
        'provider_synced_at',
        'trial_starts_at',
        'trial_ends_at',
        'current_period_starts_at',
        'current_period_ends_at',
        'grace_period_ends_at',
        'canceled_at',
        'cancel_at_period_end',
        'collection_paused',
        'ended_at',
        'pending_plan_id',
        'pending_plan_effective_at',
        'metadata',
    ];

    protected $casts = [
        'billing_account_id'       => 'integer',
        'plan_id'                  => 'integer',
        'plan_price_id'            => 'integer',
        'status'                   => SubscriptionStatusEnum::class,
        'billing_cycle'            => BillingCycleEnum::class,
        'cancel_at_period_end'     => 'boolean',
        'collection_paused'        => 'boolean',
        'trial_starts_at'          => 'datetime',
        'trial_ends_at'            => 'datetime',
        'current_period_starts_at' => 'datetime',
        'current_period_ends_at'   => 'datetime',
        'grace_period_ends_at'     => 'datetime',
        'canceled_at'              => 'datetime',
        'ended_at'                 => 'datetime',
        'provider_synced_at'       => 'datetime',
        'pending_plan_effective_at'=> 'datetime',
        'metadata'                 => 'array',
    ];

    public function billingAccount(): BelongsTo
    {
        return $this->belongsTo(BillingAccount::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(PlanPrice::class);
    }

    public function pendingPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'pending_plan_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class);
    }

    public function entitlementSnapshots(): HasMany
    {
        return $this->hasMany(StoreEntitlementSnapshot::class);
    }

    /**
     * Check if subscription is on trial.
     */
    public function isTrialing(): bool
    {
        return $this->status === SubscriptionStatusEnum::TRIALING;
    }

    /**
     * Check if subscription is active (paid and current).
     */
    public function isActive(): bool
    {
        return $this->status === SubscriptionStatusEnum::ACTIVE;
    }

    /**
     * Check if subscription is past due.
     */
    public function isPastDue(): bool
    {
        return $this->status === SubscriptionStatusEnum::PAST_DUE;
    }

    /**
     * Check if subscription is in grace period.
     */
    public function isInGracePeriod(): bool
    {
        return $this->status === SubscriptionStatusEnum::GRACE_PERIOD;
    }

    /**
     * Check if subscription is canceled.
     */
    public function isCanceled(): bool
    {
        return $this->status === SubscriptionStatusEnum::CANCELED;
    }

    /**
     * Check if subscription is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === SubscriptionStatusEnum::EXPIRED;
    }

    /**
     * Check if subscription grants full access.
     */
    public function grantsFullAccess(): bool
    {
        return $this->status->grantsFullAccess();
    }

    /**
     * Check if subscription grants read-only access.
     */
    public function grantsReadOnlyAccess(): bool
    {
        return $this->status->grantsReadOnlyAccess();
    }

    /**
     * Check if subscription grants any access.
     */
    public function grantsAccess(): bool
    {
        return $this->status->grantsAccess();
    }

    /**
     * Get days remaining in trial.
     */
    public function getTrialDaysRemaining(): ?int
    {
        if (!$this->isTrialing() || !$this->trial_ends_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }

    /**
     * Check if trial is ending soon (3 days or less).
     */
    public function isTrialEndingSoon(): bool
    {
        $daysRemaining = $this->getTrialDaysRemaining();
        return $daysRemaining !== null && $daysRemaining <= 3;
    }

    /**
     * Check if subscription has a pending plan change.
     */
    public function hasPendingPlanChange(): bool
    {
        return $this->pending_plan_id !== null;
    }

    /**
     * Get the effective plan (pending plan if exists, otherwise current plan).
     */
    public function getEffectivePlan(): Plan
    {
        return $this->hasPendingPlanChange() 
            ? $this->pendingPlan 
            : $this->plan;
    }

    /**
     * Scope to get only active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            SubscriptionStatusEnum::TRIALING->value,
            SubscriptionStatusEnum::ACTIVE->value,
        ]);
    }

    /**
     * Scope to get subscriptions that grant access.
     */
    public function scopeWithAccess($query)
    {
        return $query->whereIn('status', [
            SubscriptionStatusEnum::TRIALING->value,
            SubscriptionStatusEnum::ACTIVE->value,
            SubscriptionStatusEnum::PAST_DUE->value,
            SubscriptionStatusEnum::GRACE_PERIOD->value,
            SubscriptionStatusEnum::CANCELED->value,
        ]);
    }

    /**
     * Scope to get expiring trials.
     */
    public function scopeExpiringTrials($query, int $daysThreshold = 3)
    {
        return $query->where('status', SubscriptionStatusEnum::TRIALING->value)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now()->addDays($daysThreshold))
            ->where('trial_ends_at', '>', now());
    }

    /**
     * Scope to get expired trials.
     */
    public function scopeExpiredTrials($query)
    {
        return $query->where('status', SubscriptionStatusEnum::TRIALING->value)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now());
    }

    /**
     * Scope to get expired grace periods.
     */
    public function scopeExpiredGracePeriods($query)
    {
        return $query->where('status', SubscriptionStatusEnum::GRACE_PERIOD->value)
            ->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<=', now());
    }
}
