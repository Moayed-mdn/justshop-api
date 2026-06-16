<?php

namespace App\Services\Billing\Webhooks;

use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Events\Subscription\SubscriptionStatusChanged;
use App\Models\Subscription;
use App\Services\Subscription\SubscriptionStateMachine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HandleSubscriptionUpdated
{
    public function __construct(
        private SubscriptionStateMachine $stateMachine,
    ) {}

    /**
     * Handle customer.subscription.updated webhook.
     * 
     * Syncs subscription status and dates with Stripe.
     */
    public function handle(array $event): void
    {
        $stripeSubscription = $event['data']['object'];
        $stripeSubscriptionId = $stripeSubscription['id'];

        // Find local subscription
        $subscription = Subscription::where('provider_subscription_id', $stripeSubscriptionId)->first();

        if (!$subscription) {
            Log::channel('billing')->warning('webhook.subscription.updated.not_found', [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);
            return;
        }

        // Map Stripe status to local status
        $newStatus = $this->mapStripeStatus($stripeSubscription['status']);
        $oldStatus = $subscription->status;

        // Check for out-of-order webhook (provider_synced_at protection)
        $stripeUpdatedAt = Carbon::createFromTimestamp($stripeSubscription['created']);
        if ($subscription->provider_synced_at && $stripeUpdatedAt < $subscription->provider_synced_at) {
            Log::channel('billing')->warning('webhook.subscription.out_of_order', [
                'subscription_id' => $subscription->id,
                'stripe_updated_at' => $stripeUpdatedAt->toDateTimeString(),
                'local_synced_at' => $subscription->provider_synced_at->toDateTimeString(),
            ]);
            return;
        }

        // Get period dates from subscription items (not available at subscription level)
        $items = $stripeSubscription['items']['data'] ?? [];
        $updateData = [
            'provider_status' => $stripeSubscription['status'],
            'provider_synced_at' => Carbon::now(),
            'cancel_at_period_end' => $stripeSubscription['cancel_at_period_end'] ?? false,
            'canceled_at' => $stripeSubscription['canceled_at'] 
                ? Carbon::createFromTimestamp($stripeSubscription['canceled_at']) 
                : null,
        ];
        
        if (!empty($items)) {
            $firstItem = $items[0];
            
            if (isset($firstItem['current_period_start'])) {
                $updateData['current_period_starts_at'] = Carbon::createFromTimestamp($firstItem['current_period_start']);
            }
            if (isset($firstItem['current_period_end'])) {
                $updateData['current_period_ends_at'] = Carbon::createFromTimestamp($firstItem['current_period_end']);
            }
        }
        
        // Update subscription data
        $subscription->update($updateData);

        // Transition status if changed
        if ($newStatus !== $oldStatus && $oldStatus->canTransitionTo($newStatus)) {
            $subscription = $this->stateMachine->transition(
                $subscription,
                $newStatus,
                source: 'webhook',
                reason: 'stripe_status_changed',
                payload: [
                    'stripe_status' => $stripeSubscription['status'],
                ]
            );

            event(new SubscriptionStatusChanged($subscription));
        }

        Log::channel('billing')->info('webhook.subscription.updated', [
            'subscription_id' => $subscription->id,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'old_status' => $oldStatus->value,
            'new_status' => $newStatus->value,
        ]);
    }

    /**
     * Map Stripe subscription status to local SubscriptionStatusEnum.
     */
    private function mapStripeStatus(string $stripeStatus): SubscriptionStatusEnum
    {
        return match ($stripeStatus) {
            'incomplete' => SubscriptionStatusEnum::INCOMPLETE,
            'incomplete_expired' => SubscriptionStatusEnum::EXPIRED,
            'trialing' => SubscriptionStatusEnum::TRIALING,
            'active' => SubscriptionStatusEnum::ACTIVE,
            'past_due' => SubscriptionStatusEnum::PAST_DUE,
            'canceled' => SubscriptionStatusEnum::CANCELED,
            'unpaid' => SubscriptionStatusEnum::EXPIRED,
            'paused' => SubscriptionStatusEnum::PAUSED,
            default => SubscriptionStatusEnum::ACTIVE,
        };
    }
}
