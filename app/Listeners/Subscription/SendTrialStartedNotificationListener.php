<?php

declare(strict_types=1);

namespace App\Listeners\Subscription;

use App\Events\Subscription\TrialStarted;
use App\Notifications\Subscription\SubscriptionTrialStartedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * NOTE: TrialStarted, SubscriptionActivated, and SubscriptionStatusChanged
 * are dispatched from real billing code (see Actions/Subscription/*) but,
 * before this change, had zero registered listeners —
 * SendSubscriptionLifecycleEmailListener exists with TODO bodies and a
 * subscribe() method, but nothing in the app ever calls
 * Event::subscribe(), so it has never actually run. That's a pre-existing
 * gap unrelated to push notifications and is out of scope to fix here;
 * this listener is registered independently via Event::listen() in
 * AppServiceProvider, the pattern already used everywhere else in this
 * app.
 */
class SendTrialStartedNotificationListener implements ShouldQueue
{
    public function handle(TrialStarted $event): void
    {
        $owner = $event->subscription->billingAccount?->owner;

        if (!$owner) {
            return;
        }

        $owner->notify(new SubscriptionTrialStartedNotification($event->subscription));
    }
}
