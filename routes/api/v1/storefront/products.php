<?php

use App\Http\Controllers\Api\Storefront\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['store.context'])
    ->prefix('stores/{store}/products')
    ->name('storefront.products.')
    ->controller(ProductController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/category/{slug}', 'indexByCategory')->name('category');
        Route::get('/{slug}/related', 'related')->name('related');
        Route::get('/{slug}', 'show')->name('show');
    });
