<?php

namespace App\Services\Billing;

use App\Enums\Billing\WebhookStatusEnum;
use App\Models\StripeWebhookEvent;
use Illuminate\Support\Collection;

class WebhookHealthMonitor
{
    /**
     * Get failed webhooks within the specified hours.
     */
    public function getFailedWebhooks(int $hours = 24): Collection
    {
        return StripeWebhookEvent::where('status', WebhookStatusEnum::FAILED->value)
            ->where('received_at', '>=', now()->subHours($hours))
            ->orderBy('received_at', 'desc')
            ->get();
    }

    /**
     * Get unprocessed webhooks within the specified hours.
     */
    public function getUnprocessedWebhooks(int $hours = 1): Collection
    {
        return StripeWebhookEvent::where('status', WebhookStatusEnum::RECEIVED->value)
            ->where('received_at', '>=', now()->subHours($hours))
            ->orderBy('received_at', 'asc')
            ->get();
    }

    /**
     * Get webhook health score and metrics.
     */
    public function getHealthScore(): array
    {
        $failedCount24h = $this->getFailedWebhooks(24)->count();
        $unprocessedCount1h = $this->getUnprocessedWebhooks(1)->count();
        $averageProcessingTime = $this->getAverageProcessingTime();

        // Calculate health score (0-100)
        $healthScore = $this->calculateHealthScore(
            $failedCount24h,
            $unprocessedCount1h,
            $averageProcessingTime
        );

        // Determine status
        $status = $this->determineHealthStatus($healthScore);

        // Should alert if health is poor or there are critical issues
        $shouldAlert = $this->shouldAlert($failedCount24h, $unprocessedCount1h, $healthScore);

        return [
            'health_score' => $healthScore,
            'status' => $status,
            'failed_webhooks_24h' => $failedCount24h,
            'unprocessed_webhooks_1h' => $unprocessedCount1h,
            'average_processing_time_ms' => $averageProcessingTime,
            'should_alert' => $shouldAlert,
        ];
    }

    /**
     * Calculate average processing time for successfully processed webhooks.
     */
    private function getAverageProcessingTime(): int
    {
        $processed = StripeWebhookEvent::where('status', WebhookStatusEnum::PROCESSED->value)
            ->whereNotNull('processed_at')
            ->where('received_at', '>=', now()->subHours(24))
            ->get();

        if ($processed->isEmpty()) {
            return 0;
        }

        $totalMs = $processed->sum(function ($webhook) {
            if (!$webhook->received_at || !$webhook->processed_at) {
                return 0;
            }
            return $webhook->received_at->diffInMilliseconds($webhook->processed_at);
        });

        return (int) round($totalMs / $processed->count());
    }

    /**
     * Calculate health score based on various metrics.
     */
    private function calculateHealthScore(
        int $failedCount,
        int $unprocessedCount,
        int $avgProcessingTime
    ): int {
        $score = 100;

        // Deduct points for failed webhooks (up to 50 points)
        $score -= min($failedCount * 10, 50);

        // Deduct points for unprocessed webhooks (up to 30 points)
        $score -= min($unprocessedCount * 15, 30);

        // Deduct points for slow processing (up to 20 points)
        if ($avgProcessingTime > 5000) {
            $score -= 20;
        } elseif ($avgProcessingTime > 2000) {
            $score -= 10;
        } elseif ($avgProcessingTime > 1000) {
            $score -= 5;
        }

        return max($score, 0);
    }

    /**
     * Determine health status from score.
     */
    private function determineHealthStatus(int $score): string
    {
        if ($score >= 90) {
            return 'healthy';
        }

        if ($score >= 70) {
            return 'warning';
        }

        return 'critical';
    }

    /**
     * Determine if an alert should be triggered.
     */
    public function shouldAlert(): bool
    {
        $health = $this->getHealthScore();

        // Alert if health score is below 70
        if ($health['health_score'] < 70) {
            return true;
        }

        // Alert if more than 5 failed webhooks in 24h
        if ($health['failed_webhooks_24h'] > 5) {
            return true;
        }

        // Alert if more than 10 unprocessed webhooks in 1h
        if ($health['unprocessed_webhooks_1h'] > 10) {
            return true;
        }

        return false;
    }

    /**
     * Get webhook processing statistics.
     */
    public function getProcessingStatistics(int $hours = 24): array
    {
        $total = StripeWebhookEvent::where('received_at', '>=', now()->subHours($hours))->count();
        $processed = StripeWebhookEvent::where('status', WebhookStatusEnum::PROCESSED->value)
            ->where('received_at', '>=', now()->subHours($hours))
            ->count();
        $failed = StripeWebhookEvent::where('status', WebhookStatusEnum::FAILED->value)
            ->where('received_at', '>=', now()->subHours($hours))
            ->count();
        $unprocessed = StripeWebhookEvent::where('status', WebhookStatusEnum::RECEIVED->value)
            ->where('received_at', '>=', now()->subHours($hours))
            ->count();

        return [
            'period_hours' => $hours,
            'total' => $total,
            'processed' => $processed,
            'failed' => $failed,
            'unprocessed' => $unprocessed,
            'success_rate' => $total > 0 ? round(($processed / $total) * 100, 2) : 100,
        ];
    }

    /**
     * Detect missing expected webhook events.
     * 
     * For example, if we have a subscription.created but no subscription.updated after it.
     */
    public function detectMissingEvents(): array
    {
        $missing = [];

        // Check for checkout.session.completed without corresponding subscription.created
        $checkoutSessions = StripeWebhookEvent::where('event_type', 'checkout.session.completed')
            ->where('received_at', '>=', now()->subHours(24))
            ->get();

        foreach ($checkoutSessions as $checkout) {
            $subscriptionId = $checkout->payload['data']['object']['subscription'] ?? null;
            
            if ($subscriptionId) {
                $hasSubscriptionCreated = StripeWebhookEvent::where('event_type', 'customer.subscription.created')
                    ->where('payload->data->object->id', $subscriptionId)
                    ->exists();

                if (!$hasSubscriptionCreated) {
                    $missing[] = [
                        'expected_event' => 'customer.subscription.created',
                        'after_event' => 'checkout.session.completed',
                        'checkout_session_id' => $checkout->provider_event_id,
                        'subscription_id' => $subscriptionId,
                    ];
                }
            }
        }

        return $missing;
    }
}
