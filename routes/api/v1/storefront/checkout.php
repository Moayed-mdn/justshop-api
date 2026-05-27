<?php

use App\Http\Controllers\Api\Storefront\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::controller(CheckoutController::class)
    ->group(function () {
        Route::middleware(['auth:sanctum', 'store.context'])
            ->prefix('stores/{store}/checkout')
            ->name('storefront.checkout.')
            ->group(function () {
                Route::post('/', 'initiate')->name('initiate');
                Route::post('/confirm', 'confirm')->name('confirm');
            });

        Route::get('checkout/status/{sessionId}', 'status')
            ->name('storefront.checkout.status');
    });
