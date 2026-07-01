<?php

use App\Http\Controllers\Api\Storefront\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::controller(CheckoutController::class)
    ->group(function () {
        Route::middleware(['store.context'])
            ->prefix('stores/{store}/checkout')
            ->name('storefront.checkout.')
            ->group(function () {
                Route::post('/confirm', 'confirm')->name('confirm');
                
                // Enhanced Checkout Routes (requires authentication)
                Route::middleware(['auth:sanctum'])->group(function () {
                    Route::post('/initiate-enhanced', 'initiateEnhanced')->name('initiate-enhanced');
                    Route::post('/shipping-methods', 'getShippingMethods')->name('shipping-methods');
                    Route::post('/payment-intent', 'createPaymentIntent')->name('payment-intent');
                    Route::post('/complete', 'completeEnhanced')->name('complete');
                });
            });
    });
