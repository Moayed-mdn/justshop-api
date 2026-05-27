<?php
// routes/api/v1/stripe/webhook.php

use App\Http\Controllers\Api\Shared\Payment\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// Stripe webhook — NO auth, NO CSRF
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);