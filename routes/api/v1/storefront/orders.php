<?php

use App\Http\Controllers\Api\Storefront\OrderController;
use Illuminate\Support\Facades\Route;

// Guest lookup
Route::prefix('orders/guest')
    ->name('storefront.orders.guest.')
    ->controller(OrderController::class)
    ->group(function () {
        Route::post('/lookup', 'guestLookup')->name('lookup');
    });

// Authenticated + store-scoped
Route::middleware(['auth:sanctum', 'store.context'])
    ->prefix('stores/{store}/orders')
    ->name('storefront.orders.')
    ->controller(OrderController::class)
    ->group(function () {
        Route::get('/filters', 'filters')->name('filters');
        Route::get('/', 'index')->name('index');
        Route::get('/{orderNumber}', 'show')->name('show');
        Route::post('/{orderNumber}/cancel', 'cancel')->name('cancel');
        Route::post('/{orderNumber}/reorder', 'reorder')->name('reorder');
    });
