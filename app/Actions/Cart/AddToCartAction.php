<?php

namespace App\Actions\Cart;

use App\DTOs\Cart\AddToCartDTO;
use App\Exceptions\Order\OutOfStockException;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Cart\CartItemRepository;
use App\Repositories\Product\ProductVariantRepository;
use Illuminate\Support\Facades\DB;

class AddToCartAction
{
    public function __construct(
        private CartRepository $cartRepository,
        private CartItemRepository $cartItemRepository,
        private ProductVariantRepository $productVariantRepository,
    ) {}

    public function execute(AddToCartDTO $dto): \App\Models\Cart
    {
        return DB::transaction(function () use ($dto) {
            $cart = $this->cartRepository->getOrCreate(
                \App\Models\User::findOrFail($dto->userId)
            );

            $variant = $this->productVariantRepository->findWithLock($dto->productVariantId);

            if (!$variant->is_active || $variant->quantity < $dto->quantity) {
                throw new OutOfStockException(__('cart.variant_not_available'));
            }

            $existingItem = $this->cartItemRepository->findByCartAndVariant($cart, $dto->productVariantId);

            if ($existingItem) {
                $newQty = $existingItem->quantity + $dto->quantity;

                if ($variant->quantity < $newQty) {
                    throw new OutOfStockException(__('cart.not_enough_stock'));
                }

                $this->cartItemRepository->updateQuantity($existingItem, $newQty);
            } else {
                $this->cartItemRepository->create(
                    $cart,
                    $dto->productVariantId,
                    $dto->quantity,
                    $variant->price
                );
            }

            return $cart->load(['items.productVariant']);
        });
    }
}