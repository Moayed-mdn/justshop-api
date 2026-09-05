<?php

namespace App\Actions\Cart;

use App\DTOs\Cart\RemoveCartItemDTO;
use App\Repositories\Cart\CartItemRepository;

class RemoveCartItemAction
{
    public function __construct(
        private CartItemRepository $cartItemRepository,
    ) {}

    public function execute(RemoveCartItemDTO $dto): void
    {
        $item = $this->cartItemRepository->findById($dto->itemId);

        if ($item->cart->store_id !== $dto->storeId || $item->cart->user_id !== $dto->userId) {
            throw new \App\Exceptions\Store\UnauthorizedStoreAccessException();
        }
        
        $this->cartItemRepository->delete($item);
    }
}