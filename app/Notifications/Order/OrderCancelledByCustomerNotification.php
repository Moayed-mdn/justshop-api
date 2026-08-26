<?php

declare(strict_types=1);

namespace App\Notifications\Order;

use App\Enums\Notification\NotificationTypeEnum;
use App\Models\Order;
use App\Services\Fcm\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderCancelledByCustomerNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly string $storeName,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => NotificationTypeEnum::ORDER_CANCELLED_BY_CUSTOMER->value,
            'title' => __('notification.order_cancelled_by_customer_title'),
            'body' => $this->body(),
            'entity_type' => 'order',
            'entity_id' => $this->order->id,
            'route' => 'merchant.orders.show',
            'data' => [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'store_id' => $this->order->store_id,
            ],
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return new FcmMessage(
            title: __('notification.order_cancelled_by_customer_title'),
            body: $this->body(),
            data: [
                'type' => NotificationTypeEnum::ORDER_CANCELLED_BY_CUSTOMER->value,
                'entity_type' => 'order',
                'entity_id' => (string) $this->order->id,
                'route' => 'merchant.orders.show',
                'order_number' => $this->order->order_number,
                'store_id' => (string) $this->order->store_id,
            ],
        );
    }

    private function body(): string
    {
        return __('notification.order_cancelled_by_customer_body', [
            'order_number' => $this->order->order_number,
            'store' => $this->storeName,
        ]);
    }
}
