<?php

declare(strict_types=1);

namespace App\Services\Lead;

use App\Models\Lead;
use App\Notifications\LeadSubmittedNotification;
use App\Repositories\Lead\LeadRepository;
use Illuminate\Support\Facades\Notification;

class LeadNotificationService
{
    public function __construct(
        private LeadRepository $repository,
    ) {}

    public function notifyAdmins(Lead $lead): void
    {
        $recipients = $this->repository->listAdminRecipients();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new LeadSubmittedNotification($lead));

            return;
        }

        $fallbackAddress = (string) config('mail.from.address');

        if ($fallbackAddress !== '') {
            Notification::route('mail', $fallbackAddress)
                ->notify(new LeadSubmittedNotification($lead));
        }
    }
}
