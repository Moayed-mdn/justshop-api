<?php

declare(strict_types=1);

namespace App\Listeners\Subscription;

use App\Events\Subscription\SubscriptionActivated;
use App\Listeners\Concerns\EnsuresSingleNotificationDispatch;
use App\Notifications\Subscription\SubscriptionActivatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendSubscriptionActivatedNotificationListener implements ShouldQueue
{
    use EnsuresSingleNotificationDispatch;

    public function handle(SubscriptionActivated $event): void
    {
        if (!$this->claimOnce("subscription-activated:{$event->subscription->id}")) {
            return;
        }

        $owner = $event->subscription->billingAccount?->owner;

        if (!$owner) {
            return;
        }

        $owner->notify(new SubscriptionActivatedNotification($event->subscription));
    }
}