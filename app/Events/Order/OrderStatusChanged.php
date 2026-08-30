<?php

declare(strict_types=1);

namespace App\Events\Order;

use App\Enums\Order\OrderStatusEnum;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched when a store team member changes an order's status
 * (Admin\UpdateOrderStatusAction) — e.g. processing -> shipped ->
 * delivered. Not dispatched for cancellation; see OrderCancelled.
 */
class OrderStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $orderId,
        public readonly OrderStatusEnum $previousStatus,
        public readonly OrderStatusEnum $newStatus,
    ) {
    }
}
