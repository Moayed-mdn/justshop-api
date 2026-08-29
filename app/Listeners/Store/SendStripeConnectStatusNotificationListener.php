<?php

declare(strict_types=1);

namespace App\Listeners\Store;

use App\Events\Store\StripeConnectStatusChanged;
use App\Listeners\Concerns\EnsuresSingleNotificationDispatch;
use App\Models\Store;
use App\Notifications\Store\StripeConnectStatusChangedNotification;
use App\Services\Notification\StoreNotificationRecipientResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendStripeConnectStatusNotificationListener implements ShouldQueue
{
    use EnsuresSingleNotificationDispatch;

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

        // Short TTL (unlike the default 24h elsewhere): a store's Stripe
        // status can legitimately flip the same direction again much
        // later (re-enabled, then restricted again months later) and each
        // occurrence deserves its own notification — this guard should
        // only catch a near-term duplicate dispatch of the *same*
        // transition, not suppress a genuinely new one.
        $key = 'stripe-connect:'.$event->storeId.':'.($event->newlyOnboarded() ? 'onboarded' : 'restricted');
        if (!$this->claimOnce($key, ttlHours: 1)) {
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