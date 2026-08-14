<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use App\Models\Store;
use Illuminate\Support\Facades\Log;

/**
 * Handle Stripe Connect account.updated webhook event
 * Syncs Stripe Connect account status to the stores table
 */
class HandleStripeConnectAccountUpdated
{
    public function handle(array $payload): void
    {
        $account = $payload['data']['object'] ?? null;
        
        if (!$account || !isset($account['id'])) {
            Log::channel('billing')->warning('stripe.account_updated.missing_data', [
                'payload_keys' => array_keys($payload),
            ]);
            return;
        }

        $stripeAccountId = $account['id'];

        $store = Store::where('stripe_account_id', $stripeAccountId)->first();

        if (!$store) {
            Log::channel('billing')->warning('stripe.account_updated.store_not_found', [
                'stripe_account_id' => $stripeAccountId,
            ]);
            return;
        }

        $updates = [
            'stripe_details_submitted' => $account['details_submitted'] ?? false,
            'stripe_charges_enabled' => $account['charges_enabled'] ?? false,
            'stripe_payouts_enabled' => $account['payouts_enabled'] ?? false,
        ];

        // Mark onboarded timestamp when fully enabled for the first time
        if (
            !$store->stripe_onboarded_at
            && $updates['stripe_details_submitted']
            && $updates['stripe_charges_enabled']
            && $updates['stripe_payouts_enabled']
        ) {
            $updates['stripe_onboarded_at'] = now();
        }

        $store->update($updates);

        Log::channel('billing')->info('stripe.account_updated.processed', [
            'store_id' => $store->id,
            'store_slug' => $store->slug,
            'stripe_account_id' => $stripeAccountId,
            'details_submitted' => $updates['stripe_details_submitted'],
            'charges_enabled' => $updates['stripe_charges_enabled'],
            'payouts_enabled' => $updates['stripe_payouts_enabled'],
            'can_receive_payments' => $store->fresh()->canReceivePayments(),
        ]);
    }
}
