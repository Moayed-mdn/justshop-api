<?php

use App\Http\Controllers\Api\Merchant\CategoryController;
use Illuminate\Support\Facades\Route;

Route::controller(CategoryController::class)
    ->name('merchant.categories.')
    ->prefix('categories')
    ->group(function () {
        Route::get('/{category:slug}/breadcrumb', 'breadcrumb')->name('breadcrumb');
    });
