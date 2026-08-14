<?php

use App\Http\Controllers\Api\Billing\BillingPortalController;
use App\Http\Controllers\Api\Billing\CheckoutController;
use App\Http\Controllers\Api\Billing\InvoiceController;
use App\Http\Controllers\Api\Billing\PlanController;
use App\Http\Controllers\Api\Billing\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Merchant Billing Routes (Phase 3-6)
|--------------------------------------------------------------------------
|
| Subscription management routes for merchants.
| Protected by authentication and merchant identity context.
|
*/

// Public Plan Catalog (unauthenticated)
Route::prefix('public/plans')->group(function () {
    Route::get('/', [PlanController::class, 'index'])
        ->name('public.plans.index');
    
    Route::get('/{code}', [PlanController::class, 'show'])
        ->name('public.plans.show');
});

// Authenticated Merchant Routes
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Plans (accessible to authenticated users)
    Route::prefix('billing/plans')->group(function () {
        Route::get('/', [PlanController::class, 'index'])
            ->name('merchant.billing.plans.index');
        
        Route::get('/{plan}', [PlanController::class, 'show'])
            ->name('merchant.billing.plans.show');
    });
    
    // Trial Signup
    Route::post('/billing/trial/start', [CheckoutController::class, 'startTrial'])
        ->name('merchant.billing.trial.start');
    
    // Checkout (Phase 3)
    Route::post('/billing/checkout', [CheckoutController::class, 'createSession'])
        ->name('merchant.billing.checkout');

    // Subscription Management (Phase 5)
    Route::prefix('billing/subscription')->group(function () {
        Route::get('/', [SubscriptionController::class, 'show'])
            ->name('merchant.billing.subscription.show');
        
        Route::get('/usage', [SubscriptionController::class, 'usage'])
            ->name('merchant.billing.subscription.usage');
        
        Route::post('/upgrade', [SubscriptionController::class, 'upgrade'])
            ->name('merchant.billing.subscription.upgrade');
        
        Route::post('/downgrade', [SubscriptionController::class, 'downgrade'])
            ->name('merchant.billing.subscription.downgrade');
        
        Route::post('/move-to-current-version', [SubscriptionController::class, 'moveToCurrentVersion'])
            ->name('merchant.billing.subscription.move_to_current_version');
        
        Route::post('/cancel', [SubscriptionController::class, 'cancel'])
            ->name('merchant.billing.subscription.cancel');
        
        Route::post('/resume', [SubscriptionController::class, 'resume'])
            ->name('merchant.billing.subscription.resume');
    });

    // Invoice & Portal (Phase 6)
    Route::prefix('billing/invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])
            ->name('merchant.billing.invoices.index');
        
        Route::get('/{invoice}', [InvoiceController::class, 'show'])
            ->name('merchant.billing.invoices.show');
        
        Route::get('/{invoice}/download', [InvoiceController::class, 'download'])
            ->name('merchant.billing.invoices.download');
    });

    Route::post('/billing/portal', [BillingPortalController::class, 'createSession'])
        ->name('merchant.billing.portal');
});
