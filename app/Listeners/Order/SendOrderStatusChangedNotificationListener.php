<?php

declare(strict_types=1);

namespace App\Listeners\Order;

use App\Events\Order\OrderStatusChanged;
use App\Models\Order;
use App\Notifications\Order\OrderStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderStatusChangedNotificationListener implements ShouldQueue
{
    public function handle(OrderStatusChanged $event): void
    {
        $order = Order::with('user')->find($event->orderId);

        if (!$order || !$order->user) {
            return;
        }

        $order->user->notify(new OrderStatusChangedNotification($order, $event->newStatus));
    }
}
