<?php

use App\Http\Controllers\Api\Billing\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Stripe Webhook Routes
|--------------------------------------------------------------------------
|
| Stripe webhook endpoint for subscription lifecycle events.
| No authentication - verified via Stripe signature.
|
*/

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');
