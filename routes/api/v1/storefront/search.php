<?php

use App\Http\Controllers\Api\Storefront\SearchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['store.context'])
    ->prefix('stores/{store}/search')
    ->name('storefront.search.')
    ->controller(SearchController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
    });
