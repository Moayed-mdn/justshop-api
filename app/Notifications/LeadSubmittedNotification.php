<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Notification\NotificationTypeEnum;
use App\Models\Lead;
use App\Services\Fcm\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeadSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Lead $lead,
    ) {}

    public function via(object $_notifiable): array
    {
        return ['mail', 'database', 'fcm'];
    }

    public function toMail(object $_notifiable): MailMessage
    {
        $typeLabel = __('lead.type_' . $this->lead->type->value);

        return (new MailMessage)
            ->subject(__('lead.notification_subject', ['type' => $typeLabel]))
            ->line(__('lead.notification_intro'))
            ->line(__('lead.notification_type', ['type' => $typeLabel]))
            ->line(__('lead.notification_name', ['name' => $this->lead->name]))
            ->line(__('lead.notification_email', ['email' => $this->lead->email]))
            ->line(__('lead.notification_company', ['company' => $this->lead->company ?? '-']))
            ->line(__('lead.notification_source_page', ['page' => $this->lead->source_page ?? '-']))
            ->line(__('lead.notification_message', ['message' => $this->lead->message]));
    }

    public function toDatabase(object $_notifiable): array
    {
        $typeLabel = __('lead.type_' . $this->lead->type->value);

        return [
            'type' => NotificationTypeEnum::LEAD_SUBMITTED->value,
            'title' => __('lead.notification_subject', ['type' => $typeLabel]),
            'body' => __('lead.notification_name', ['name' => $this->lead->name]),
            'entity_type' => 'lead',
            'entity_id' => $this->lead->id,
            'route' => 'platform.leads.show',
            'data' => [
                'lead_id' => $this->lead->id,
            ],
        ];
    }

    public function toFcm(object $_notifiable): FcmMessage
    {
        $typeLabel = __('lead.type_' . $this->lead->type->value);

        return new FcmMessage(
            title: __('lead.notification_subject', ['type' => $typeLabel]),
            body: __('lead.notification_name', ['name' => $this->lead->name]),
            data: [
                'type' => NotificationTypeEnum::LEAD_SUBMITTED->value,
                'entity_type' => 'lead',
                'entity_id' => (string) $this->lead->id,
                'route' => 'platform.leads.show',
            ],
        );
    }
}
