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
        Route::put('/{address}', 'update')->name('update');
        Route::delete('/{address}', 'destroy')->name('destroy');
        Route::patch('/{address}/default', 'setDefault')->name('default');
    });
