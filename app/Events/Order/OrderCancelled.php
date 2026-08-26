<?php

declare(strict_types=1);

namespace App\Events\Order;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched whenever an order is cancelled, regardless of which side
 * initiated it (the storefront customer via Order\CancelOrderAction, or a
 * store team member via Admin\CancelOrderAction both route through the
 * same OrderPolicy::cancel() gate and dispatch this).
 *
 * $cancelledByUserId lets the listener notify the *other* party: if the
 * customer cancelled, the store team is notified; if the store cancelled,
 * the customer is notified.
 */
class OrderCancelled implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $orderId,
        public readonly int $cancelledByUserId,
    ) {
    }
}
