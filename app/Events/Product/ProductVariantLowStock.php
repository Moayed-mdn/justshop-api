<?php

declare(strict_types=1);

namespace App\Events\Product;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched when a product variant's quantity crosses at-or-below its
 * low_stock_threshold as a result of an order being placed. Only fired on
 * the transition (previous quantity was above threshold, new quantity is
 * not) — see EnhancedCheckoutService — so it doesn't re-fire on every
 * subsequent order once a variant is already low on stock.
 */
class ProductVariantLowStock implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $productVariantId,
        public readonly int $currentQuantity,
        public readonly int $threshold,
    ) {
    }
}
