<?php

namespace App\Services\Billing;

use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Models\StripeWebhookEvent;
use App\Repositories\Subscription\SubscriptionRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReconciliationReportGenerator
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepo,
        private WebhookHealthMonitor $webhookHealthMonitor,
    ) {}

    /**
     * Generate a complete reconciliation report.
     */
    public function generate(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'drift_report' => $this->generateDriftReport(),
            'webhook_health' => $this->generateWebhookHealthReport(),
            'subscription_health' => $this->generateSubscriptionHealthReport(),
        ];
    }

    /**
     * Generate drift report (to be populated after reconciliation).
     */
    public function generateDriftReport(): array
    {
        // This will be populated by the reconcile command
        // For now, return a structure for the report
        return [
            'subscriptions_checked' => 0,
            'drift_detected' => 0,
            'fixed' => 0,
            'failed' => 0,
            'drift_details' => [],
        ];
    }

    /**
     * Generate webhook health report.
     */
    public function generateWebhookHealthReport(): array
    {
        $health = $this->webhookHealthMonitor->getHealthScore();

        return [
            'health_score' => $health['health_score'],
            'status' => $health['status'],
            'failed_webhooks_24h' => $health['failed_webhooks_24h'],
            'unprocessed_webhooks_1h' => $health['unprocessed_webhooks_1h'],
            'average_processing_time_ms' => $health['average_processing_time_ms'],
            'should_alert' => $health['should_alert'],
            'recent_failures' => $this->webhookHealthMonitor->getFailedWebhooks(24)->map(function ($webhook) {
                return [
                    'event_id' => $webhook->provider_event_id,
                    'event_type' => $webhook->event_type,
                    'attempts' => $webhook->attempts,
                    'error' => $webhook->error_message,
                    'received_at' => $webhook->received_at->toIso8601String(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate subscription health report.
     */
    public function generateSubscriptionHealthReport(): array
    {
        $activeSubscriptions = Subscription::withAccess()->count();
        $pastDueSubscriptions = $this->getPastDueSubscriptions();
        $gracePeriodSubscriptions = $this->getGracePeriodSubscriptions();
        $trialExpiringSubscriptions = $this->getTrialExpiringSubscriptions();

        return [
            'active_subscriptions' => $activeSubscriptions,
            'past_due_count' => $pastDueSubscriptions->count(),
            'grace_period_count' => $gracePeriodSubscriptions->count(),
            'trial_expiring_soon' => $trialExpiringSubscriptions->count(),
            'health_status' => $this->determineSubscriptionHealthStatus(
                $pastDueSubscriptions->count(),
                $gracePeriodSubscriptions->count()
            ),
            'past_due_details' => $pastDueSubscriptions->map(function ($sub) {
                return [
                    'subscription_id' => $sub->id,
                    'billing_account_id' => $sub->billing_account_id,
                    'plan' => $sub->plan->code ?? null,
                    'past_due_since' => $sub->current_period_ends_at?->toIso8601String(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Get past due subscriptions (> 3 days).
     */
    private function getPastDueSubscriptions(): Collection
    {
        return Subscription::where('status', SubscriptionStatusEnum::PAST_DUE->value)
            ->where('current_period_ends_at', '<', now()->subDays(3))
            ->get();
    }

    /**
     * Get grace period subscriptions.
     */
    private function getGracePeriodSubscriptions(): Collection
    {
        return Subscription::where('status', SubscriptionStatusEnum::GRACE_PERIOD->value)
            ->whereNotNull('grace_period_ends_at')
            ->get();
    }

    /**
     * Get trials expiring soon (< 3 days).
     */
    private function getTrialExpiringSubscriptions(): Collection
    {
        return Subscription::where('status', SubscriptionStatusEnum::TRIALING->value)
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(3)])
            ->get();
    }

    /**
     * Determine overall subscription health status.
     */
    private function determineSubscriptionHealthStatus(int $pastDueCount, int $gracePeriodCount): string
    {
        if ($pastDueCount === 0 && $gracePeriodCount === 0) {
            return 'healthy';
        }

        if ($pastDueCount <= 2 && $gracePeriodCount === 0) {
            return 'warning';
        }

        return 'critical';
    }
}
