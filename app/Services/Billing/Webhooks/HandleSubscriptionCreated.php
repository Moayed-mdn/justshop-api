<?php

namespace App\Services\Billing\Webhooks;

use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HandleSubscriptionCreated
{
    /**
     * Handle customer.subscription.created webhook.
     * 
     * Updates local subscription with Stripe subscription data.
     */
    public function handle(array $event): void
    {
        $stripeSubscription = $event['data']['object'];
        $billingAccountId = $stripeSubscription['metadata']['billing_account_id'] ?? null;

        if (!$billingAccountId) {
            Log::channel('billing')->warning('webhook.subscription.created.no_account_id', [
                'stripe_subscription_id' => $stripeSubscription['id'],
            ]);
            return;
        }

        // Find local subscription
        $subscription = Subscription::where('billing_account_id', $billingAccountId)
            ->where(function ($q) use ($stripeSubscription) {
                $q->where('provider_subscription_id', $stripeSubscription['id'])
                    ->orWhereNull('provider_subscription_id');
            })
            ->first();

        if (!$subscription) {
            Log::channel('billing')->warning('webhook.subscription.created.not_found', [
                'stripe_subscription_id' => $stripeSubscription['id'],
                'billing_account_id' => $billingAccountId,
            ]);
            return;
        }

        $updateData = [
            'provider_subscription_id' => $stripeSubscription['id'],
            'provider_status' => $stripeSubscription['status'],
            'provider_synced_at' => Carbon::now(),
        ];

        if (isset($stripeSubscription['current_period_start'])) {
            $updateData['current_period_starts_at'] = Carbon::createFromTimestamp($stripeSubscription['current_period_start']);
        }
        if (isset($stripeSubscription['current_period_end'])) {
            $updateData['current_period_ends_at'] = Carbon::createFromTimestamp($stripeSubscription['current_period_end']);
        }

        $subscription->update($updateData);

        Log::channel('billing')->info('webhook.subscription.created', [
            'subscription_id' => $subscription->id,
            'stripe_subscription_id' => $stripeSubscription['id'],
        ]);
    }
}
