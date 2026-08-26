<?php

declare(strict_types=1);

namespace App\Listeners\Subscription;

use App\Events\Subscription\SubscriptionStatusChanged;
use App\Notifications\Subscription\SubscriptionStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendSubscriptionStatusChangedNotificationListener implements ShouldQueue
{
    public function handle(SubscriptionStatusChanged $event): void
    {
        $owner = $event->subscription->billingAccount?->owner;

        if (!$owner) {
            return;
        }

        $owner->notify(new SubscriptionStatusChangedNotification($event->subscription));
    }
}
