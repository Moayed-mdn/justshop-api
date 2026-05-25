<?php
// app/Models/Order.php

namespace App\Models;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Models\Concerns\HasStoreScoping;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes, HasStoreScoping;

    protected $fillable = [
        'order_number',
        'store_id',
        'user_id',
        'guest_email',                    // ← NEW
        'shipping_address_id',
        'billing_address_id',
        'shipping_address_data',          // ← NEW
        'payment_method_id',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total',
        'currency',                       // ← NEW
        'status',
        'payment_status',
        'payment_intent_id',
        'stripe_checkout_session_id',     // ← NEW
        'shipping_method',
        'tracking_number',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'subtotal'              => 'decimal:2',
        'tax_amount'            => 'decimal:2',
        'shipping_amount'       => 'decimal:2',
        'discount_amount'       => 'decimal:2',
        'total'                 => 'decimal:2',
        'shipped_at'            => 'datetime',
        'delivered_at'          => 'datetime',
        'shipping_address_data' => 'array',
        'status'                => OrderStatusEnum::class,
        'payment_status'        => PaymentStatusEnum::class,
    ];

    // ── Relationships ──────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function billingAddress()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function store(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // ── Auto-generate order number ─────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });
    }

    // ── Helpers ────────────────────────────────────────────────

    /**
     * Get the customer email regardless of guest or registered user.
     */
    public function getCustomerEmailAttribute(): ?string
    {
        return $this->guest_email ?? $this->user?->email;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [OrderStatusEnum::PENDING, OrderStatusEnum::PROCESSING]);
    }

    public function markAsPaid(?string $paymentIntentId = null): void
    {
        $this->update([
            'payment_status'    => PaymentStatusEnum::PAID,
            'status'            => OrderStatusEnum::PROCESSING,
            'payment_intent_id' => $paymentIntentId,
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'payment_status' => PaymentStatusEnum::FAILED,
            'status'         => OrderStatusEnum::CANCELLED,
        ]);
    }
}