<?php

declare(strict_types=1);

namespace App\Listeners\Order;

use App\Enums\Notification\NotificationCategoryEnum;
use App\Events\Order\OrderPlaced;
use App\Models\Order;
use App\Notifications\Order\NewOrderReceivedNotification;
use App\Notifications\Order\OrderPlacedNotification;
use App\Notifications\Platform\HighValueOrderNotification;
use App\Repositories\Notification\PlatformRecipientRepository;
use App\Services\Notification\StoreNotificationRecipientResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendOrderPlacedNotificationsListener implements ShouldQueue
{
    public function __construct(
        private readonly StoreNotificationRecipientResolver $storeRecipients,
        private readonly PlatformRecipientRepository $platformRecipients,
    ) {
    }

    public function handle(OrderPlaced $event): void
    {
        $order = Order::with(['store', 'user'])->find($event->orderId);

        if (!$order) {
            return;
        }

        // Guest checkouts have no user account to notify.
        if ($order->user) {
            $order->user->notify(new OrderPlacedNotification($order));
        }

        if ($order->store) {
            $merchantRecipients = $this->storeRecipients->resolve($order->store, NotificationCategoryEnum::ORDER);

            if ($merchantRecipients->isNotEmpty()) {
                Notification::send($merchantRecipients, new NewOrderReceivedNotification($order, $order->store->name));
            }
        }

        $this->notifyAdminsIfHighValue($order);
    }

    private function notifyAdminsIfHighValue(Order $order): void
    {
        $threshold = config('notifications.high_value_order_threshold');

        if ($threshold === null || $order->total < (float) $threshold) {
            return;
        }

        $admins = $this->platformRecipients->listAdminRecipients();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new HighValueOrderNotification($order, $order->store?->name ?? ''));
        }
    }
}
