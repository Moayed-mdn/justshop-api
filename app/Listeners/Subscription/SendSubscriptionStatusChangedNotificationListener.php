<?php

declare(strict_types=1);

namespace App\Listeners\Subscription;

use App\Events\Subscription\SubscriptionStatusChanged;
use App\Listeners\Concerns\EnsuresSingleNotificationDispatch;
use App\Notifications\Subscription\SubscriptionStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendSubscriptionStatusChangedNotificationListener implements ShouldQueue
{
    use EnsuresSingleNotificationDispatch;

    public function handle(SubscriptionStatusChanged $event): void
    {
        // Keyed with the status (not just subscription id): a subscription
        // legitimately passes through multiple different statuses over its
        // lifetime, and each real transition deserves its own notification.
        $key = "subscription-status-changed:{$event->subscription->id}:{$event->subscription->status->value}";
        if (!$this->claimOnce($key)) {
            return;
        }

        $owner = $event->subscription->billingAccount?->owner;

        if (!$owner) {
            return;
        }

        $owner->notify(new SubscriptionStatusChangedNotification($event->subscription));
    }
}