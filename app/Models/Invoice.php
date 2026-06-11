<?php

namespace App\Models;

use App\Enums\Billing\InvoiceStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_account_id',
        'subscription_id',
        'provider',
        'provider_invoice_id',
        'invoice_number',
        'status',
        'currency',
        'subtotal_cents',
        'tax_cents',
        'discount_cents',
        'total_cents',
        'amount_paid_cents',
        'amount_due_cents',
        'period_starts_at',
        'period_ends_at',
        'issued_at',
        'due_at',
        'paid_at',
        'hosted_invoice_url',
        'invoice_pdf_url',
        'metadata',
    ];

    protected $casts = [
        'billing_account_id' => 'integer',
        'subscription_id'    => 'integer',
        'status'             => InvoiceStatusEnum::class,
        'subtotal_cents'     => 'integer',
        'tax_cents'          => 'integer',
        'discount_cents'     => 'integer',
        'total_cents'        => 'integer',
        'amount_paid_cents'  => 'integer',
        'amount_due_cents'   => 'integer',
        'period_starts_at'   => 'datetime',
        'period_ends_at'     => 'datetime',
        'issued_at'          => 'datetime',
        'due_at'             => 'datetime',
        'paid_at'            => 'datetime',
        'metadata'           => 'array',
    ];

    public function billingAccount(): BelongsTo
    {
        return $this->belongsTo(BillingAccount::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === InvoiceStatusEnum::PAID;
    }

    /**
     * Check if invoice is open (unpaid).
     */
    public function isOpen(): bool
    {
        return $this->status === InvoiceStatusEnum::OPEN;
    }

    /**
     * Check if invoice is void.
     */
    public function isVoid(): bool
    {
        return $this->status === InvoiceStatusEnum::VOID;
    }

    /**
     * Get formatted total.
     */
    public function getFormattedTotal(): string
    {
        $amount = $this->total_cents / 100;
        return number_format($amount, 2);
    }

    /**
     * Get total with currency symbol.
     */
    public function getFormattedTotalWithCurrency(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        $symbol = $symbols[$this->currency] ?? $this->currency . ' ';
        return $symbol . $this->getFormattedTotal();
    }

    /**
     * Scope to get only paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', InvoiceStatusEnum::PAID->value);
    }

    /**
     * Scope to get only open invoices.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', InvoiceStatusEnum::OPEN->value);
    }
}
