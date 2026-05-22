<?php

use App\Http\Controllers\Api\Store\StoreController;
use Illuminate\Support\Facades\Route;

// POST /api/v1/stores - no store.context middleware (store doesn't exist yet)
Route::post('/v1/stores', [StoreController::class, 'create'])
    ->middleware(['auth:sanctum'])
    ->name('stores.create');

Route::post('/v1/stores/validate-slug', [StoreController::class, 'validateSlug'])
    ->middleware(['auth:sanctum'])
    ->name('stores.validate-slug');

// GET /api/v1/stores/{store} and PUT /api/v1/stores/{store} - with store.context
Route::middleware(['auth:sanctum', 'store.context'])
    ->prefix('/v1/stores/{store}')
    ->group(function () {
        Route::get('/', [StoreController::class, 'show'])
            ->name('stores.show');

        Route::put('/', [StoreController::class, 'update'])
            ->name('stores.update');
    });
