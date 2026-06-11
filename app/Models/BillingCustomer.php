<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_account_id',
        'provider',
        'provider_customer_id',
        'default_payment_method_id',
        'metadata',
    ];

    protected $casts = [
        'billing_account_id' => 'integer',
        'metadata'           => 'array',
    ];

    public function billingAccount(): BelongsTo
    {
        return $this->belongsTo(BillingAccount::class);
    }

    /**
     * Check if this is a Stripe customer.
     */
    public function isStripe(): bool
    {
        return $this->provider === 'stripe';
    }

    /**
     * Check if customer has a default payment method.
     */
    public function hasPaymentMethod(): bool
    {
        return !empty($this->default_payment_method_id);
    }
}
