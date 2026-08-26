<?php

declare(strict_types=1);

namespace App\Listeners\Store;

use App\Events\Store\StripeConnectStatusChanged;
use App\Models\Store;
use App\Notifications\Store\StripeConnectStatusChangedNotification;
use App\Services\Notification\StoreNotificationRecipientResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendStripeConnectStatusNotificationListener implements ShouldQueue
{
    public function __construct(
        private readonly StoreNotificationRecipientResolver $storeRecipients,
    ) {
    }

    public function handle(StripeConnectStatusChanged $event): void
    {
        // Only the two transitions that actually matter operationally are
        // worth a notification — a partial flag change (e.g. just
        // details_submitted flipping) that doesn't cross into "fully
        // onboarded" or "no longer able to charge/payout" isn't
        // actionable for the merchant.
        if (!$event->newlyOnboarded() && !$event->newlyRestricted()) {
            return;
        }

        $store = Store::find($event->storeId);

        if (!$store) {
            return;
        }

        $admins = $this->storeRecipients->resolveAdminsOnly($store);

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new StripeConnectStatusChangedNotification($store, $event->newlyOnboarded()));
    }
}
