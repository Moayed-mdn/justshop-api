<?php

namespace App\Listeners\Subscription;

use App\Events\Subscription\SubscriptionActivated;
use App\Events\Subscription\SubscriptionStatusChanged;
use App\Events\Subscription\TrialStarted;
use App\Models\BillingAuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Request;

class AuditSubscriptionEventListener implements ShouldQueue
{
    /**
     * Handle TrialStarted event.
     */
    public function handleTrialStarted(TrialStarted $event): void
    {
        $this->logAuditEvent(
            billingAccountId: $event->subscription->billing_account_id,
            action: 'subscription.trial_started',
            subjectType: 'subscription',
            subjectId: $event->subscription->id,
            changes: [
                'subscription_id' => $event->subscription->id,
                'store_id' => $event->storeId,
                'plan_id' => $event->subscription->plan_id,
                'trial_ends_at' => $event->subscription->trial_ends_at?->toDateTimeString(),
            ]
        );
    }

    /**
     * Handle SubscriptionActivated event.
     */
    public function handleSubscriptionActivated(SubscriptionActivated $event): void
    {
        $this->logAuditEvent(
            billingAccountId: $event->subscription->billing_account_id,
            action: 'subscription.activated',
            subjectType: 'subscription',
            subjectId: $event->subscription->id,
            changes: [
                'subscription_id' => $event->subscription->id,
                'plan_id' => $event->subscription->plan_id,
                'status' => $event->subscription->status->value,
            ]
        );
    }

    /**
     * Handle SubscriptionStatusChanged event.
     */
    public function handleSubscriptionStatusChanged(SubscriptionStatusChanged $event): void
    {
        $this->logAuditEvent(
            billingAccountId: $event->subscription->billing_account_id,
            action: 'subscription.status_changed',
            subjectType: 'subscription',
            subjectId: $event->subscription->id,
            changes: [
                'subscription_id' => $event->subscription->id,
                'status' => $event->subscription->status->value,
            ]
        );
    }

    /**
     * Log audit event to billing_audit_logs.
     */
    private function logAuditEvent(
        int $billingAccountId,
        string $action,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $changes = []
    ): void {
        BillingAuditLog::create([
            'billing_account_id' => $billingAccountId,
            'actor_user_id' => auth()->id(),
            'actor_type' => auth()->check() ? 'user' : 'system',
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'changes' => $changes,
        ]);
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
