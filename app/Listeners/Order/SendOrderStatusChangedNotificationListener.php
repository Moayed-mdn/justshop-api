<?php

declare(strict_types=1);

namespace App\Listeners\Order;

use App\Events\Order\OrderStatusChanged;
use App\Listeners\Concerns\EnsuresSingleNotificationDispatch;
use App\Models\Order;
use App\Notifications\Order\OrderStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderStatusChangedNotificationListener implements ShouldQueue
{
    use EnsuresSingleNotificationDispatch;

    public function handle(OrderStatusChanged $event): void
    {
        // Keyed by the full transition (not just orderId): an order
        // legitimately changes status multiple times over its lifetime
        // (pending -> processing -> shipped -> delivered), and each real
        // transition deserves its own notification. Only an exact repeat
        // of the same transition is treated as a duplicate.
        $key = "order-status-changed:{$event->orderId}:{$event->previousStatus->value}:{$event->newStatus->value}";
        if (!$this->claimOnce($key)) {
            return;
        }

        $order = Order::with('user')->find($event->orderId);

        if (!$order || !$order->user) {
            return;
        }

        $order->user->notify(new OrderStatusChangedNotification($order, $event->newStatus));
    }
}