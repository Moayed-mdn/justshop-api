<?php

declare(strict_types=1);

namespace App\Actions\Store;

use App\Events\Store\StripeConnectStatusChanged;
use App\Models\Store;
use Stripe\Account;

/**
 * Applies a Stripe Account object's status flags onto the local Store row.
 *
 * Shared by StripeEcommerceWebhookController::handleAccountUpdated() and
 * ReconcileStripeConnectStatusAction, so both the webhook path and the
 * on-demand reconciliation fallback compute the exact same result from an
 * Account object — one place to update if Stripe adds/changes fields.
 */
class ApplyStripeAccountStatusAction
{
    public function execute(Store $store, Account $account): Store
    {
        $before = $this->snapshot($store);

        $updates = [
            'stripe_details_submitted' => $account->details_submitted ?? false,
            'stripe_charges_enabled' => $account->charges_enabled ?? false,
            'stripe_payouts_enabled' => $account->payouts_enabled ?? false,
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
        $store = $store->fresh();

        $after = $this->snapshot($store);

        if ($before !== $after) {
            StripeConnectStatusChanged::dispatch($store->id, $before, $after);
        }

        return $store;
    }

    /**
     * @return array{details_submitted: bool, charges_enabled: bool, payouts_enabled: bool}
     */
    private function snapshot(Store $store): array
    {
        return [
            'details_submitted' => (bool) $store->stripe_details_submitted,
            'charges_enabled' => (bool) $store->stripe_charges_enabled,
            'payouts_enabled' => (bool) $store->stripe_payouts_enabled,
        ];
    }
}
