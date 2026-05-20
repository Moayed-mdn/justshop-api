<?php

namespace App\Repositories\Cart;

use App\Models\CartItem;
use App\Models\Cart;

class CartItemRepository
{
    public function findById(int $id): CartItem
    {
        return CartItem::findOrFail($id);
    }

    public function findByCartAndVariant(Cart $cart, int $variantId): ?CartItem
    {
        return $cart->items()
            ->where('product_variant_id', $variantId)
            ->first();
    }

    public function create(Cart $cart, int $variantId, int $quantity, float $price): CartItem
    {
        return $cart->items()->create([
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
            'unit_price' => $price,
        ]);
    }

    public function updateQuantity(CartItem $item, int $quantity): void
    {
        $item->update(['quantity' => $quantity]);
    }

    public function delete(CartItem $item): void
    {
        $item->delete();
    }

    public function deleteByCart(Cart $cart): void
    {
        $cart->items()->delete();
    }
}