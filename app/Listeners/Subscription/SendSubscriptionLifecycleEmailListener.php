<?php

namespace App\Listeners\Subscription;

use App\Events\Subscription\SubscriptionActivated;
use App\Events\Subscription\SubscriptionStatusChanged;
use App\Events\Subscription\TrialStarted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendSubscriptionLifecycleEmailListener implements ShouldQueue
{
    /**
     * Handle TrialStarted event.
     * 
     * TODO: Send "Welcome to Trial" email
     */
    public function handleTrialStarted(TrialStarted $event): void
    {
        Log::channel('billing')->info('email.trial_started.queued', [
            'subscription_id' => $event->subscription->id,
            'billing_account_id' => $event->subscription->billing_account_id,
        ]);

        // TODO Phase 6: Implement email notification
        // - Welcome to trial
        // - Trial duration and benefits
        // - Link to upgrade
    }

    /**
     * Handle SubscriptionActivated event.
     * 
     * TODO: Send "Subscription Activated" email
     */
    public function handleSubscriptionActivated(SubscriptionActivated $event): void
    {
        Log::channel('billing')->info('email.subscription_activated.queued', [
            'subscription_id' => $event->subscription->id,
            'billing_account_id' => $event->subscription->billing_account_id,
        ]);

        // TODO Phase 6: Implement email notification
        // - Confirmation of active subscription
        // - Invoice details
        // - Next billing date
    }

    /**
     * Handle SubscriptionStatusChanged event.
     * 
     * TODO: Send status-specific emails (past_due, canceled, etc.)
     */
    public function handleSubscriptionStatusChanged(SubscriptionStatusChanged $event): void
    {
        Log::channel('billing')->info('email.subscription_status_changed.queued', [
            'subscription_id' => $event->subscription->id,
            'status' => $event->subscription->status->value,
            'billing_account_id' => $event->subscription->billing_account_id,
        ]);

        // TODO Phase 6: Implement status-specific email notifications
        // - past_due: Payment failed, retry notification
        // - grace_period: Final warning before suspension
        // - canceled: Confirmation and retain access details
        // - expired: Subscription ended notification
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            TrialStarted::class => 'handleTrialStarted',
            SubscriptionActivated::class => 'handleSubscriptionActivated',
            SubscriptionStatusChanged::class => 'handleSubscriptionStatusChanged',
        ];
    }
}
