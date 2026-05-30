<?php

namespace App\Actions\Cart;

use App\DTOs\Cart\BulkAddToCartDTO;
use App\Exceptions\Order\OutOfStockException;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Cart\CartItemRepository;
use App\Repositories\Product\ProductVariantRepository;
use Illuminate\Support\Facades\DB;

class BulkAddToCartAction
{
    public function __construct(
        private CartRepository $cartRepository,
        private CartItemRepository $cartItemRepository,
        private ProductVariantRepository $productVariantRepository,
    ) {}

    public function execute(BulkAddToCartDTO $dto): \App\Models\Cart
    {
        return DB::transaction(function () use ($dto) {
            $user = \App\Models\User::findOrFail($dto->userId);

            $cart = $this->cartRepository->getOrCreate(
                $user,
                $dto->storeId,
            );

            foreach ($dto->items as $item) {
                $variantId = (int) $item['product_variant_id'];
                $quantity = (int) $item['quantity'];

                $variant = $this->productVariantRepository->findWithLock($variantId, $dto->storeId);

                if (!$variant->is_active) {
                    continue; // Skip inactive variants in bulk merge
                }

                $existingItem = $this->cartItemRepository->findByCartAndVariant($cart, $variantId);

                if ($existingItem) {
                    $newQty = $existingItem->quantity + $quantity;
                    // Cap at available stock for bulk merge instead of throwing
                    $finalQty = min($newQty, $variant->quantity);
                    
                    if ($finalQty > 0) {
                        $this->cartItemRepository->updateQuantity($existingItem, $finalQty);
                    }
                } else {
                    $finalQty = min($quantity, $variant->quantity);
                    if ($finalQty > 0) {
                        $this->cartItemRepository->create($cart, $variantId, $finalQty, (float) $variant->price);
                    }
                }
            }

            return $cart->load(['items.productVariant']);
        });
    }
}
