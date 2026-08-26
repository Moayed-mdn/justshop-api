<?php

declare(strict_types=1);

namespace App\Notifications\Store;

use App\Enums\Notification\NotificationTypeEnum;
use App\Models\Store;
use App\Services\Fcm\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StripeConnectStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Store $store,
        private readonly bool $newlyOnboarded,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => NotificationTypeEnum::STORE_STRIPE_CONNECT_STATUS_CHANGED->value,
            'title' => $this->title(),
            'body' => $this->body(),
            'entity_type' => 'store',
            'entity_id' => $this->store->id,
            'route' => 'merchant.settings.payments',
            'data' => [
                'store_id' => $this->store->id,
                'onboarded' => $this->newlyOnboarded,
            ],
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return new FcmMessage(
            title: $this->title(),
            body: $this->body(),
            data: [
                'type' => NotificationTypeEnum::STORE_STRIPE_CONNECT_STATUS_CHANGED->value,
                'entity_type' => 'store',
                'entity_id' => (string) $this->store->id,
                'route' => 'merchant.settings.payments',
                'onboarded' => $this->newlyOnboarded ? '1' : '0',
            ],
        );
    }

    private function title(): string
    {
        return $this->newlyOnboarded
            ? __('notification.stripe_connect_enabled_title')
            : __('notification.stripe_connect_restricted_title');
    }

    private function body(): string
    {
        return $this->newlyOnboarded
            ? __('notification.stripe_connect_enabled_body', ['store' => $this->store->name])
            : __('notification.stripe_connect_restricted_body', ['store' => $this->store->name]);
    }
}
