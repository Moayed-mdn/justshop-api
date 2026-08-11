<?php

namespace App\Services\Billing\Webhooks;

use App\Actions\Entitlement\RecomputeEntitlementsAction;
use App\DTOs\Entitlement\RecomputeEntitlementsDTO;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Events\Subscription\SubscriptionStatusChanged;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Services\Subscription\SubscriptionStateMachine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HandleSubscriptionUpdated
{
    public function __construct(
        private SubscriptionStateMachine $stateMachine,
        private RecomputeEntitlementsAction $recomputeEntitlements,
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
        // Use event timestamp, not subscription.created (which is fixed)
        $eventCreatedAt = Carbon::createFromTimestamp($event['created']);
        if ($subscription->provider_synced_at && $eventCreatedAt < $subscription->provider_synced_at) {
            Log::channel('billing')->warning('webhook.subscription.out_of_order', [
                'subscription_id' => $subscription->id,
                'event_created_at' => $eventCreatedAt->toDateTimeString(),
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
        
        $planChanged = false;
        
        if (!empty($items)) {
            $firstItem = $items[0];
            
            if (isset($firstItem['current_period_start'])) {
                $updateData['current_period_starts_at'] = Carbon::createFromTimestamp($firstItem['current_period_start']);
            }
            if (isset($firstItem['current_period_end'])) {
                $updateData['current_period_ends_at'] = Carbon::createFromTimestamp($firstItem['current_period_end']);
            }

            // CRITICAL FIX: Plan changes made through Stripe's hosted Customer/Billing
            // Portal don't go through UpgradePlanAction/DowngradePlanAction — they
            // land here as customer.subscription.updated. Without this, local
            // plan_id/plan_price_id (and therefore entitlements) silently drift
            // from what the merchant is actually being billed for.
            $stripePriceId = $firstItem['price']['id'] ?? null;

            if ($stripePriceId) {
                $planPrice = PlanPrice::where('provider_price_id', $stripePriceId)->first();

                if ($planPrice && $planPrice->plan_id !== $subscription->plan_id) {
                    $updateData['plan_id'] = $planPrice->plan_id;
                    $updateData['plan_price_id'] = $planPrice->id;
                    $planChanged = true;

                    Log::channel('billing')->info('webhook.subscription.plan_changed_via_portal', [
                        'subscription_id' => $subscription->id,
                        'old_plan_id' => $subscription->plan_id,
                        'new_plan_id' => $planPrice->plan_id,
                        'old_plan_price_id' => $subscription->plan_price_id,
                        'new_plan_price_id' => $planPrice->id,
                        'stripe_price_id' => $stripePriceId,
                    ]);
                } elseif (!$planPrice) {
                    Log::channel('billing')->warning('webhook.subscription.unknown_price_id', [
                        'subscription_id' => $subscription->id,
                        'stripe_price_id' => $stripePriceId,
                    ]);
                }
            }
        }
        
        // Update subscription data
        $subscription->update($updateData);

        // Recompute entitlements for every store on this account if the plan changed
        // This ensures stores_max, products_max, and all feature flags match the
        // plan the merchant is actually being charged for.
        if ($planChanged) {
            $stores = $subscription->billingAccount->owner->stores;
            foreach ($stores as $store) {
                $this->recomputeEntitlements->execute(
                    new RecomputeEntitlementsDTO(
                        billingAccountId: $subscription->billing_account_id,
                        storeId: $store->id,
                    )
                );
            }

            Log::channel('billing')->info('webhook.subscription.entitlements_recomputed', [
                'subscription_id' => $subscription->id,
                'stores_count' => $stores->count(),
            ]);
        }

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
