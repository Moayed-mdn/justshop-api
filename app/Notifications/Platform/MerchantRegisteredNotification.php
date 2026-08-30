<?php

declare(strict_types=1);

namespace App\Notifications\Platform;

use App\Enums\Notification\NotificationTypeEnum;
use App\Services\Fcm\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MerchantRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $merchantUserId,
        private readonly string $merchantName,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => NotificationTypeEnum::MERCHANT_REGISTERED->value,
            'title' => __('notification.merchant_registered_title'),
            'body' => $this->body(),
            'entity_type' => 'user',
            'entity_id' => $this->merchantUserId,
            'route' => 'platform.merchants.show',
            'data' => [
                'merchant_user_id' => $this->merchantUserId,
            ],
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return new FcmMessage(
            title: __('notification.merchant_registered_title'),
            body: $this->body(),
            data: [
                'type' => NotificationTypeEnum::MERCHANT_REGISTERED->value,
                'entity_type' => 'user',
                'entity_id' => (string) $this->merchantUserId,
                'route' => 'platform.merchants.show',
            ],
        );
    }

    private function body(): string
    {
        return __('notification.merchant_registered_body', ['name' => $this->merchantName]);
    }
}
