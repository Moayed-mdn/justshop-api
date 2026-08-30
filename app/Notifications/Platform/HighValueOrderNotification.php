<?php

declare(strict_types=1);

namespace App\Notifications\Platform;

use App\Enums\Notification\NotificationTypeEnum;
use App\Models\Order;
use App\Services\Fcm\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class HighValueOrderNotification extends Notification
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
            'type' => NotificationTypeEnum::ORDER_HIGH_VALUE->value,
            'title' => __('notification.order_high_value_title'),
            'body' => $this->body(),
            'entity_type' => 'order',
            'entity_id' => $this->order->id,
            'route' => 'platform.orders.show',
            'data' => [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'store_id' => $this->order->store_id,
                'total' => (string) $this->order->total,
            ],
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return new FcmMessage(
            title: __('notification.order_high_value_title'),
            body: $this->body(),
            data: [
                'type' => NotificationTypeEnum::ORDER_HIGH_VALUE->value,
                'entity_type' => 'order',
                'entity_id' => (string) $this->order->id,
                'route' => 'platform.orders.show',
                'order_number' => $this->order->order_number,
                'store_id' => (string) $this->order->store_id,
            ],
        );
    }

    private function body(): string
    {
        return __('notification.order_high_value_body', [
            'order_number' => $this->order->order_number,
            'store' => $this->storeName,
            'total' => (string) $this->order->total,
        ]);
    }
}
