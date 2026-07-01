<?php

use App\Http\Controllers\Api\Storefront\AddressController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'store.context'])
    ->prefix('stores/{store}/addresses')
    ->name('storefront.addresses.')
    ->controller(AddressController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('/validate', 'validate')->name('validate');
        Route::put('/{address}', 'update')->name('update');
        Route::delete('/{address}', 'destroy')->name('destroy');
        Route::patch('/{address}/default', 'setDefault')->name('default');
        
        // Enhanced Checkout - Default address routes
        Route::post('/{address}/set-default-shipping', 'setDefaultShipping')->name('set-default-shipping');
        Route::post('/{address}/set-default-billing', 'setDefaultBilling')->name('set-default-billing');
        
        // Get complete address settings for the store
        Route::get('/settings', 'getSettings')->name('settings');

        // Get allowed countries for the store
        Route::get('/allowed-countries', 'getAllowedCountries')->name('allowed-countries');
    });
