<?php

use App\Http\Controllers\Api\Storefront\StripeEcommerceWebhookController;
use Illuminate\Support\Facades\Route;

/**
 * Storefront Stripe Ecommerce Webhook Routes
 * Handles webhooks for order payments and Stripe Connect account updates.
 * 
 * IMPORTANT: This is separate from platform billing webhooks.
 * Do not merge these endpoints.
 */
Route::post('/webhooks/stripe/ecommerce', [StripeEcommerceWebhookController::class, 'handle'])
    ->name('storefront.webhooks.stripe.ecommerce');
