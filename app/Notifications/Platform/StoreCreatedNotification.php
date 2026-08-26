<?php

declare(strict_types=1);

namespace App\Notifications\Platform;

use App\Enums\Notification\NotificationTypeEnum;
use App\Services\Fcm\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StoreCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $storeId,
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
            'type' => NotificationTypeEnum::STORE_CREATED->value,
            'title' => __('notification.store_created_title'),
            'body' => $this->body(),
            'entity_type' => 'store',
            'entity_id' => $this->storeId,
            'route' => 'platform.stores.show',
            'data' => [
                'store_id' => $this->storeId,
            ],
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return new FcmMessage(
            title: __('notification.store_created_title'),
            body: $this->body(),
            data: [
                'type' => NotificationTypeEnum::STORE_CREATED->value,
                'entity_type' => 'store',
                'entity_id' => (string) $this->storeId,
                'route' => 'platform.stores.show',
            ],
        );
    }

    private function body(): string
    {
        return __('notification.store_created_body', ['store' => $this->storeName]);
    }
}
