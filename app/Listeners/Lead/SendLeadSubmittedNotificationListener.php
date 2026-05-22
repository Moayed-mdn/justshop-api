<?php

declare(strict_types=1);

namespace App\Listeners\Lead;

use App\Events\Lead\LeadSubmitted;
use App\Models\Lead;
use App\Services\Lead\LeadNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendLeadSubmittedNotificationListener implements ShouldQueue
{
    public function __construct(
        private LeadNotificationService $notificationService,
    ) {}

    public function handle(LeadSubmitted $event): void
    {
        $lead = Lead::find($event->leadId);

        if (!$lead) {
            return;
        }

        $this->notificationService->notifyAdmins($lead);
    }
}
