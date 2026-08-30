<?php

declare(strict_types=1);

namespace App\Notifications\Subscription;

use App\Enums\Notification\NotificationTypeEnum;
use App\Models\Subscription;
use App\Services\Fcm\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubscriptionTrialStartedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Subscription $subscription,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => NotificationTypeEnum::SUBSCRIPTION_TRIAL_STARTED->value,
            'title' => __('notification.subscription_trial_started_title'),
            'body' => __('notification.subscription_trial_started_body'),
            'entity_type' => 'subscription',
            'entity_id' => $this->subscription->id,
            'route' => 'merchant.billing.subscription',
            'data' => [
                'subscription_id' => $this->subscription->id,
            ],
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return new FcmMessage(
            title: __('notification.subscription_trial_started_title'),
            body: __('notification.subscription_trial_started_body'),
            data: [
                'type' => NotificationTypeEnum::SUBSCRIPTION_TRIAL_STARTED->value,
                'entity_type' => 'subscription',
                'entity_id' => (string) $this->subscription->id,
                'route' => 'merchant.billing.subscription',
            ],
        );
    }
}
