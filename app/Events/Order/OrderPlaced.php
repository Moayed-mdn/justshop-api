<?php

declare(strict_types=1);

namespace App\Events\Order;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched once an order has been paid and finalized
 * (EnhancedCheckoutService::completeCheckout()). Not the same as "order
 * row created" — a checkout can create a PENDING order and never reach
 * this event if payment never completes.
 */
class OrderPlaced implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $orderId,
    ) {
    }
}
