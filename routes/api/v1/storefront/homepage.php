<?php

use App\Http\Controllers\Api\Storefront\HomePageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['store.context'])
    ->withoutMiddleware([
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    ])
    ->prefix('stores/{store}/homepage')
    ->name('storefront.homepage.')
    ->controller(HomePageController::class)
    ->group(function () {
        Route::get('/best-seller', 'bestSeller')->name('best-seller');
        // Hero banners removed - use CMS pages with is_homepage = true instead
    });
