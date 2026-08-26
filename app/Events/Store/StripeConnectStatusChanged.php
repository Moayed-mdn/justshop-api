<?php

declare(strict_types=1);

namespace App\Events\Store;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched from ApplyStripeAccountStatusAction whenever the store's
 * stripe_details_submitted / stripe_charges_enabled / stripe_payouts_enabled
 * flags actually change (either direction — onboarding completing, or a
 * previously-enabled account becoming restricted).
 *
 * Not ShouldDispatchAfterCommit: the action isn't run inside a DB
 * transaction (see StripeEcommerceWebhookController::handleAccountUpdated
 * and ReconcileStripeConnectStatusAction), so immediate dispatch is
 * correct here.
 *
 * @param array{details_submitted: bool, charges_enabled: bool, payouts_enabled: bool} $before
 * @param array{details_submitted: bool, charges_enabled: bool, payouts_enabled: bool} $after
 */
class StripeConnectStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly int $storeId,
        public readonly array $before,
        public readonly array $after,
    ) {
    }

    public function newlyOnboarded(): bool
    {
        return !($this->before['charges_enabled'] && $this->before['payouts_enabled'])
            && $this->after['charges_enabled'] && $this->after['payouts_enabled'];
    }

    public function newlyRestricted(): bool
    {
        return ($this->before['charges_enabled'] || $this->before['payouts_enabled'])
            && !($this->after['charges_enabled'] && $this->after['payouts_enabled']);
    }
}
