<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lead;
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
        return ['mail'];
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
}
