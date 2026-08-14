<?php

declare(strict_types=1);

namespace App\Actions\Store;

use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * On-demand reconciliation fallback for Stripe Connect account status.
 *
 * The account.updated webhook is the source of truth for keeping
 * stores.stripe_* columns in sync with Stripe. But if that webhook is
 * delayed, lost, or never configured (e.g. local development without
 * `stripe listen` forwarding to the ecommerce webhook endpoint), a store's
 * local status can drift from Stripe's real status indefinitely — the
 * merchant completes onboarding on Stripe's side, but the dashboard keeps
 * showing "setup incomplete" forever.
 *
 * This is a fallback, not a replacement for the webhook: it only fetches
 * from Stripe when local state still looks incomplete, and is throttled to
 * at most once per store per THROTTLE_SECONDS — it's called from
 * GET /stripe-connect/status, which the frontend polls repeatedly while
 * waiting for onboarding to finish, so it must not hit the Stripe API on
 * every single poll.
 */
class ReconcileStripeConnectStatusAction
{
    private const THROTTLE_SECONDS = 30;

    public function __construct(
        private StripeClient $stripe,
        private ApplyStripeAccountStatusAction $applyStripeAccountStatus,
    ) {}

    public function execute(Store $store): Store
    {
        if (empty($store->stripe_account_id)) {
            return $store;
        }

        if ($this->isAlreadyFullySynced($store)) {
            return $store;
        }

        $lockKey = "stripe-connect-reconcile:{$store->id}";
        if (!Cache::add($lockKey, true, self::THROTTLE_SECONDS)) {
            // Another request already reconciled this store within the
            // throttle window — skip hitting Stripe again.
            return $store;
        }

        try {
            $account = $this->stripe->accounts->retrieve($store->stripe_account_id);
        } catch (ApiErrorException $e) {
            Log::warning('Stripe Connect reconciliation: failed to retrieve account', [
                'store_id' => $store->id,
                'stripe_account_id' => $store->stripe_account_id,
                'error' => $e->getMessage(),
            ]);

            return $store;
        }

        $before = $this->snapshot($store);

        $store = $this->applyStripeAccountStatus->execute($store, $account);

        $after = $this->snapshot($store);

        if ($before !== $after) {
            // This firing at all means the account.updated webhook missed
            // (or hasn't yet delivered) this update — worth knowing about,
            // not just silently self-healing.
            Log::warning('Stripe Connect reconciliation: local status was stale, corrected from Stripe', [
                'store_id' => $store->id,
                'stripe_account_id' => $store->stripe_account_id,
                'before' => $before,
                'after' => $after,
            ]);
        }

        return $store;
    }

    private function isAlreadyFullySynced(Store $store): bool
    {
        return $store->stripe_details_submitted
            && $store->stripe_charges_enabled
            && $store->stripe_payouts_enabled;
    }

    /**
     * @return array{details_submitted: bool, charges_enabled: bool, payouts_enabled: bool}
     */
    private function snapshot(Store $store): array
    {
        return [
            'details_submitted' => $store->stripe_details_submitted,
            'charges_enabled' => $store->stripe_charges_enabled,
            'payouts_enabled' => $store->stripe_payouts_enabled,
        ];
    }
}
