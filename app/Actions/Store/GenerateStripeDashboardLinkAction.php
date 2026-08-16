<?php

declare(strict_types=1);

namespace App\Actions\Store;

use App\DTOs\Store\GenerateStripeDashboardLinkDTO;
use App\Exceptions\BaseApiException;
use App\Models\Store;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Generate a Stripe Express Dashboard login link for a merchant.
 *
 * Express connected accounts have no standalone stripe.com credentials —
 * this is how their account holders view earnings, payouts, and manage
 * their own account, mirroring how platforms like Shopify Payments hand
 * merchants off to Stripe's hosted Express Dashboard rather than building
 * a custom payouts UI themselves.
 *
 * Per Stripe's own guidance, login links work even before onboarding is
 * fully complete — the Express Dashboard itself shows outstanding
 * requirements in that case — so this deliberately does not require
 * charges_enabled/payouts_enabled, only that a Stripe account exists.
 */
class GenerateStripeDashboardLinkAction
{
    public function __construct(
        private StripeClient $stripe,
    ) {}

    /**
     * @throws BaseApiException
     */
    public function execute(GenerateStripeDashboardLinkDTO $dto): string
    {
        $store = Store::findOrFail($dto->storeId);

        if (!$store->hasStripeAccount()) {
            throw new BaseApiException(
                message: 'This store has not started Stripe Connect setup yet.',
                statusCode: 422,
                errorCode: \App\Enums\ErrorCode::SYS_001->value
            );
        }

        try {
            $loginLink = $this->stripe->accounts->createLoginLink($store->stripe_account_id);

            Log::info('Stripe Express dashboard link generated', [
                'store_id' => $store->id,
                'stripe_account_id' => $store->stripe_account_id,
            ]);

            return $loginLink->url;
        } catch (ApiErrorException $e) {
            Log::error('Failed to generate Stripe Express dashboard link', [
                'store_id' => $store->id,
                'stripe_account_id' => $store->stripe_account_id,
                'error' => $e->getMessage(),
            ]);

            throw new BaseApiException(
                message: 'Failed to open Stripe dashboard: ' . $e->getMessage(),
                statusCode: 500,
                errorCode: \App\Enums\ErrorCode::SYS_001->value
            );
        }
    }
}
