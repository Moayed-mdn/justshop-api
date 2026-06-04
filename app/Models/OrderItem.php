<?php
// app/Models/OrderItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'sku',
        'unit_price',
        'unit_discount_percentage',
        'quantity',
        'subtotal',
        'total',
        'attributes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'unit_discount_percentage' => 'decimal:2',
        'attributes' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function getSubtotalAttribute()
    {
        return $this->unit_price * $this->quantity;
    }
}