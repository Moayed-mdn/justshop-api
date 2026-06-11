<?php

namespace App\Models;

use App\Enums\Billing\PaymentStatusEnum;
use App\Enums\Billing\PaymentTransactionTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_account_id',
        'invoice_id',
        'subscription_id',
        'provider',
        'provider_transaction_id',
        'provider_payment_method_id',
        'type',
        'status',
        'currency',
        'amount_cents',
        'failure_code',
        'failure_message',
        'processed_at',
        'metadata',
    ];

    protected $casts = [
        'billing_account_id' => 'integer',
        'invoice_id'         => 'integer',
        'subscription_id'    => 'integer',
        'type'               => PaymentTransactionTypeEnum::class,
        'status'             => PaymentStatusEnum::class,
        'amount_cents'       => 'integer',
        'processed_at'       => 'datetime',
        'metadata'           => 'array',
    ];

    public function billingAccount(): BelongsTo
    {
        return $this->belongsTo(BillingAccount::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Check if transaction succeeded.
     */
    public function isSucceeded(): bool
    {
        return $this->status === PaymentStatusEnum::SUCCEEDED;
    }

    /**
     * Check if transaction failed.
     */
    public function isFailed(): bool
    {
        return $this->status === PaymentStatusEnum::FAILED;
    }

    /**
     * Check if transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatusEnum::PENDING;
    }

    /**
     * Check if this is a charge transaction.
     */
    public function isCharge(): bool
    {
        return $this->type === PaymentTransactionTypeEnum::CHARGE;
    }

    /**
     * Check if this is a refund transaction.
     */
    public function isRefund(): bool
    {
        return $this->type === PaymentTransactionTypeEnum::REFUND;
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmount(): string
    {
        $amount = abs($this->amount_cents) / 100;
        return number_format($amount, 2);
    }

    /**
     * Scope to get only successful transactions.
     */
    public function scopeSucceeded($query)
    {
        return $query->where('status', PaymentStatusEnum::SUCCEEDED->value);
    }

    /**
     * Scope to get only failed transactions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', PaymentStatusEnum::FAILED->value);
    }
}
