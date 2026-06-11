<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_amount_cents',
        'total_cents',
        'currency',
        'period_starts_at',
        'period_ends_at',
        'metadata',
    ];

    protected $casts = [
        'invoice_id'        => 'integer',
        'quantity'          => 'integer',
        'unit_amount_cents' => 'integer',
        'total_cents'       => 'integer',
        'period_starts_at'  => 'datetime',
        'period_ends_at'    => 'datetime',
        'metadata'          => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get formatted unit amount.
     */
    public function getFormattedUnitAmount(): string
    {
        $amount = $this->unit_amount_cents / 100;
        return number_format($amount, 2);
    }

    /**
     * Get formatted total.
     */
    public function getFormattedTotal(): string
    {
        $amount = $this->total_cents / 100;
        return number_format($amount, 2);
    }
}
