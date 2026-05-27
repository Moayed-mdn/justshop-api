<?php

use App\Http\Controllers\Api\Storefront\CartController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'store.context'])
    ->prefix('stores/{store}/cart')
    ->name('storefront.cart.')
    ->controller(CartController::class)
    ->group(function () {
        Route::get('/', 'show')->name('show');
        Route::post('/items', 'addItem')->name('items.add');
        Route::patch('/items/{itemId}', 'updateItem')->name('items.update');
        Route::delete('/items/{itemId}', 'removeItem')->name('items.remove');
        Route::delete('/clear', 'clear')->name('clear');
    });
