<?php

declare(strict_types=1);

namespace App\Listeners\Subscription;

use App\Events\Subscription\SubscriptionActivated;
use App\Notifications\Subscription\SubscriptionActivatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendSubscriptionActivatedNotificationListener implements ShouldQueue
{
    public function handle(SubscriptionActivated $event): void
    {
        $owner = $event->subscription->billingAccount?->owner;

        if (!$owner) {
            return;
        }

        $owner->notify(new SubscriptionActivatedNotification($event->subscription));
    }
}
