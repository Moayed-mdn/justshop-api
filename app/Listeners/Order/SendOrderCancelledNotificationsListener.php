<?php

declare(strict_types=1);

namespace App\Listeners\Order;

use App\Enums\Notification\NotificationCategoryEnum;
use App\Events\Order\OrderCancelled;
use App\Listeners\Concerns\EnsuresSingleNotificationDispatch;
use App\Models\Order;
use App\Notifications\Order\OrderCancelledByCustomerNotification;
use App\Notifications\Order\OrderCancelledNotification;
use App\Services\Notification\StoreNotificationRecipientResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendOrderCancelledNotificationsListener implements ShouldQueue
{
    use EnsuresSingleNotificationDispatch;

    public function __construct(
        private readonly StoreNotificationRecipientResolver $storeRecipients,
    ) {
    }

    public function handle(OrderCancelled $event): void
    {
        // An order is only ever cancelled once (terminal state) — see
        // EnsuresSingleNotificationDispatch. This is a second, independent
        // layer of protection alongside the row-lock fix in
        // Order\CancelOrderAction / Admin\Order\CancelOrderAction.
        if (!$this->claimOnce("order-cancelled:{$event->orderId}")) {
            return;
        }

        $order = Order::with(['store', 'user'])->find($event->orderId);

        if (!$order) {
            return;
        }

        $cancelledByCustomer = $order->user_id !== null && $order->user_id === $event->cancelledByUserId;

        if ($cancelledByCustomer) {
            $this->notifyMerchantTeam($order);

            return;
        }

        // Cancelled by the store team (or platform support) — notify the
        // customer, if there is one to notify.
        if ($order->user) {
            $order->user->notify(new OrderCancelledNotification($order));
        }
    }

    private function notifyMerchantTeam(Order $order): void
    {
        if (!$order->store) {
            return;
        }

        $recipients = $this->storeRecipients->resolve($order->store, NotificationCategoryEnum::ORDER);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new OrderCancelledByCustomerNotification($order, $order->store->name));
        }
    }
}