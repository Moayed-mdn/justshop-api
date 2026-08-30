<?php

declare(strict_types=1);

namespace App\Notifications\Order;

use App\Enums\Notification\NotificationTypeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Models\Order;
use App\Services\Fcm\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly OrderStatusEnum $newStatus,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => NotificationTypeEnum::ORDER_STATUS_CHANGED->value,
            'title' => __('notification.order_status_changed_title'),
            'body' => $this->body(),
            'entity_type' => 'order',
            'entity_id' => $this->order->id,
            'route' => 'orders.show',
            'data' => [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'store_id' => $this->order->store_id,
                'status' => $this->newStatus->value,
            ],
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return new FcmMessage(
            title: __('notification.order_status_changed_title'),
            body: $this->body(),
            data: [
                'type' => NotificationTypeEnum::ORDER_STATUS_CHANGED->value,
                'entity_type' => 'order',
                'entity_id' => (string) $this->order->id,
                'route' => 'orders.show',
                'order_number' => $this->order->order_number,
                'store_id' => (string) $this->order->store_id,
                'status' => $this->newStatus->value,
            ],
        );
    }

    private function body(): string
    {
        return __('notification.order_status_changed_body', [
            'order_number' => $this->order->order_number,
            'status' => $this->newStatus->value,
        ]);
    }
}
