<?php
// app/Models/Cart.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'store_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function productVariants()
    {
        return $this->belongsToMany(ProductVariant::class, 'cart_items')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    public function getTotalAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->productVariant->price;
        });
    }

    public function getTotalItemsAttribute()
    {
        return $this->items->sum('quantity');
    }

    /**
     * Calculate cart subtotal (sum of all items' subtotals).
     *
     * @return float
     */
    public function calculateSubtotal(): float
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->productVariant->price;
        });
    }
}