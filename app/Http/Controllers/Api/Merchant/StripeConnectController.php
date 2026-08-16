<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant;

use App\Actions\Store\GenerateStripeDashboardLinkAction;
use App\Actions\Store\OnboardMerchantToStripeAction;
use App\Actions\Store\ReconcileStripeConnectStatusAction;
use App\DTOs\Store\GenerateStripeDashboardLinkDTO;
use App\DTOs\Store\OnboardMerchantToStripeDTO;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Traits\ApiResponserTrait;

/**
 * Merchant-facing controller for Stripe Connect onboarding.
 */
class StripeConnectController extends Controller
{
    use ApiResponserTrait;

    /**
     * Generate Stripe Connect onboarding URL for the merchant.
     * 
     * @param Store $store Route-model-bound store
     * @param OnboardMerchantToStripeAction $action
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOnboardingUrl(Store $store, OnboardMerchantToStripeAction $action)
    {
        $this->authorize('update', $store);

        $onboardingUrl = $action->execute(
            new OnboardMerchantToStripeDTO(storeId: $store->id)
        );

        // Refresh to get updated stripe_account_id and capabilities from the action
        $store->refresh();

        return $this->success([
            'onboarding_url' => $onboardingUrl,
            'stripe_account_id' => $store->stripe_account_id,
            'is_onboarded' => $store->canReceivePayments(),
        ]);
    }

    /**
     * Get current Stripe Connect account status.
     *
     * Falls back to reconciling directly from Stripe (throttled) when the
     * account.updated webhook hasn't caught local state up yet — see
     * ReconcileStripeConnectStatusAction.
     *
     * @param Store $store Route-model-bound store
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus(Store $store, ReconcileStripeConnectStatusAction $reconcile)
    {
        $this->authorize('view', $store);

        $store = $reconcile->execute($store);

        return $this->success([
            'stripe_account_id' => $store->stripe_account_id,
            'stripe_account_type' => $store->stripe_account_type,
            'details_submitted' => $store->stripe_details_submitted,
            'charges_enabled' => $store->stripe_charges_enabled,
            'payouts_enabled' => $store->stripe_payouts_enabled,
            'onboarded_at' => $store->stripe_onboarded_at?->toIso8601String(),
            'can_receive_payments' => $store->canReceivePayments(),
        ]);
    }

    /**
     * Generate a fresh Stripe Express Dashboard login link for the merchant.
     *
     * Works as soon as a Stripe account exists — even mid-onboarding, since
     * the Express Dashboard itself walks the merchant through any
     * outstanding requirements. Single-use, expires quickly: the frontend
     * must call this fresh right before opening the link, never cache it.
     *
     * @param Store $store Route-model-bound store
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardLink(Store $store, GenerateStripeDashboardLinkAction $action)
    {
        $this->authorize('view', $store);

        $url = $action->execute(
            new GenerateStripeDashboardLinkDTO(storeId: $store->id)
        );

        return $this->success(['url' => $url]);
    }
}
