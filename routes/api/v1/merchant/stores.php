<?php

use App\Http\Controllers\Api\Merchant\StoreController;
use Illuminate\Support\Facades\Route;

// Merchant Store Management
// These routes handle store creation, validation, and individual store settings.

Route::name('merchant.stores.')
    ->prefix('stores')
    ->group(function () {
        // POST /api/v1/merchant/stores - no store.context middleware (store doesn't exist yet)
        Route::post('/', [StoreController::class, 'create'])
            ->middleware(['auth:sanctum', 'identity.route:merchant_users,merchant,enforce', 'verified'])
            ->name('create');

        Route::post('/validate-slug', [StoreController::class, 'validateSlug'])
            ->middleware(['auth:sanctum', 'identity.route:merchant_users,merchant,enforce', 'verified'])
            ->name('validate-slug');

        Route::get('/slug-check', [\App\Http\Controllers\Api\Merchant\StoreSlugController::class, '__invoke'])
            ->middleware(['auth:sanctum', 'identity.route:merchant_users,merchant,enforce', 'verified'])
            ->name('slug-check-global');

        Route::get('/{store}/provisioning-status', [\App\Http\Controllers\Api\Merchant\ProvisioningStatusController::class, '__invoke'])
            ->middleware(['auth:sanctum', 'identity.route:merchant_users,merchant,enforce', 'verified'])
            ->name('provisioning-status');

        // GET /api/v1/merchant/stores/{store} and PUT /api/v1/merchant/stores/{store} - with store.context
        Route::middleware(['auth:sanctum', 'identity.route:merchant_admin,merchant,enforce', 'store.context'])
            ->prefix('{store}')
            ->group(function () {
                // Read operations
                Route::get('/', [StoreController::class, 'show'])
                    ->name('show');

                // Write operations - require active subscription
                Route::put('/', [StoreController::class, 'update'])
                    ->middleware('subscription.active')
                    ->name('update');
            });
    });
