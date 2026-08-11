<?php

use App\Http\Controllers\Api\Merchant\StripeConnectController;
use Illuminate\Support\Facades\Route;

/**
 * Merchant Stripe Connect Routes
 * Handles merchant onboarding to Stripe Connect for split payments.
 */
Route::middleware([
    'auth:sanctum',
    'identity.route:merchant_admin,merchant,enforce',
    'store.context',
])
    ->prefix('stores/{store}/stripe-connect')
    ->name('merchant.stripe-connect.')
    ->group(function () {
        Route::get('/status', [StripeConnectController::class, 'getStatus'])
            ->name('status');

        Route::post('/onboard', [StripeConnectController::class, 'getOnboardingUrl'])
            ->middleware('subscription.active')
            ->name('onboard');
    });
