<?php

use App\Http\Controllers\Api\Store\StoreController;
use Illuminate\Support\Facades\Route;

// POST /api/v1/stores - no store.context middleware (store doesn't exist yet)
// Requires: authenticated + email verified + onboarding at CREATE_STORE step
Route::post('/v1/stores', [StoreController::class, 'create'])
    ->middleware(['auth:sanctum', 'verified'])
    ->name('stores.create');

Route::post('/v1/stores/validate-slug', [StoreController::class, 'validateSlug'])
    ->middleware(['auth:sanctum', 'verified'])
    ->name('stores.validate-slug');

Route::get('/v1/store-slug/check', [\App\Http\Controllers\Api\V1\StoreSlugController::class, '__invoke'])
    ->middleware(['auth:sanctum', 'verified'])
    ->name('stores.slug-check-global');

Route::get('/v1/stores/{store}/provisioning-status', [\App\Http\Controllers\Api\V1\ProvisioningStatusController::class, '__invoke'])
    ->middleware(['auth:sanctum', 'verified'])
    ->name('stores.provisioning-status');

// GET /api/v1/stores/{store} and PUT /api/v1/stores/{store} - with store.context
Route::middleware(['auth:sanctum', 'store.context'])
    ->prefix('/v1/stores/{store}')
    ->group(function () {
        Route::get('/', [StoreController::class, 'show'])
            ->name('stores.show');

        Route::put('/', [StoreController::class, 'update'])
            ->name('stores.update');
    });
