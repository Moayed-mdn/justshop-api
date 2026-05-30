<?php

namespace App\DTOs\Cart;

use Illuminate\Http\Request;

class BulkAddToCartDTO
{
    /**
     * @param array<int, array{product_variant_id: int, quantity: int}> $items
     */
    public function __construct(
        public int $storeId,
        public int $userId,
        public array $items,
    ) {}

    public static function fromRequest(Request $request, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            userId: $request->user()->id,
            items: $request->input('items', []),
        );
    }
}
