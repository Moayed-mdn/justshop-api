<?php

namespace App\Actions\Subscription;

use App\Contracts\Billing\BillingProviderInterface;
use App\DTOs\Subscription\SyncSubscriptionDTO;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Repositories\Subscription\SubscriptionRepository;
use App\Services\Subscription\SubscriptionStateMachine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncStripeSubscriptionAction
{
    public function __construct(
        private BillingProviderInterface $billingProvider,
        private SubscriptionRepository $subscriptionRepo,
        private SubscriptionStateMachine $stateMachine,
    ) {}

    /**
     * Sync local subscription with Stripe subscription data.
     * 
     * Used for:
     * - Drift detection / reconciliation
     * - Webhook processing
     * - Manual sync after provider operations
     */
    public function execute(SyncSubscriptionDTO $dto): Subscription
    {
        return DB::transaction(function () use ($dto) {
            $subscription = Subscription::lockForUpdate()->findOrFail($dto->subscriptionId);

            // Fetch latest data from Stripe
            $providerData = $this->billingProvider->getSubscription(
                $subscription->provider_subscription_id
            );

            $providerStatus = $providerData['status'];
            $providerSyncedAt = Carbon::parse($providerData['current_period_start'] ?? now());

            // Out-of-order protection: skip if this data is older than what we have
            if ($subscription->provider_synced_at && $providerSyncedAt->lt($subscription->provider_synced_at)) {
                Log::channel('billing')->warning('subscription.sync_skipped_older_data', [
                    'subscription_id' => $subscription->id,
                    'provider_synced_at' => $subscription->provider_synced_at->toIso8601String(),
                    'incoming_synced_at' => $providerSyncedAt->toIso8601String(),
                ]);

                return $subscription;
            }

            // Map Stripe status to our status enum
            $localStatus = $this->mapProviderStatus($providerStatus);

            // Detect drift
            $hasStatusDrift = $subscription->status !== $localStatus->value;
            $hasPeriodDrift = $subscription->current_period_ends_at?->ne(
                Carbon::parse($providerData['current_period_end'])
            );

            if ($hasStatusDrift || $hasPeriodDrift) {
                Log::channel('billing')->warning('subscription.drift_detected', [
                    'subscription_id' => $subscription->id,
                    'local_status' => $subscription->status,
                    'provider_status' => $providerStatus,
                    'has_status_drift' => $hasStatusDrift,
                    'has_period_drift' => $hasPeriodDrift,
                ]);
            }

            // Transition status if needed
            if ($hasStatusDrift) {
                $subscription = $this->stateMachine->transition(
                    $subscription,
                    $localStatus,
                    source: 'sync',
                    reason: 'drift_correction',
                );
            }

            // Update subscription data
            $subscription->update([
                'provider_status' => $providerStatus,
                'provider_synced_at' => $providerSyncedAt,
                'current_period_starts_at' => Carbon::parse($providerData['current_period_start']),
                'current_period_ends_at' => Carbon::parse($providerData['current_period_end']),
                'cancel_at_period_end' => $providerData['cancel_at_period_end'] ?? false,
                'canceled_at' => isset($providerData['canceled_at'])
                    ? Carbon::parse($providerData['canceled_at'])
                    : null,
            ]);

            Log::channel('billing')->info('subscription.synced', [
                'subscription_id' => $subscription->id,
                'status' => $localStatus->value,
                'had_drift' => $hasStatusDrift || $hasPeriodDrift,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Map Stripe subscription status to local SubscriptionStatusEnum.
     */
    private function mapProviderStatus(string $providerStatus): SubscriptionStatusEnum
    {
        return match ($providerStatus) {
            'incomplete' => SubscriptionStatusEnum::INCOMPLETE,
            'trialing' => SubscriptionStatusEnum::TRIALING,
            'active' => SubscriptionStatusEnum::ACTIVE,
            'past_due' => SubscriptionStatusEnum::PAST_DUE,
            'canceled' => SubscriptionStatusEnum::CANCELED,
            'unpaid' => SubscriptionStatusEnum::EXPIRED,
            'paused' => SubscriptionStatusEnum::PAUSED,
            default => throw new \InvalidArgumentException(
                "Unknown provider status: {$providerStatus}"
            ),
        };
    }
}
