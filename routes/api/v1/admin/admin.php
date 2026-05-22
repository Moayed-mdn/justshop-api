<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\Api\Admin\Brand\AdminBrandController;
use App\Http\Controllers\Api\Admin\Category\AdminCategoryController;
use App\Http\Controllers\Api\Admin\Dashboard\AdminDashboardController;
use App\Http\Controllers\Api\Admin\Order\AdminOrderController;
use App\Http\Controllers\Api\Admin\Product\AdminProductController;
use App\Http\Controllers\Api\Admin\Tag\AdminTagController;
use App\Http\Controllers\Api\Admin\User\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin/stores/{store}')
    ->middleware(['auth:sanctum', 'verified', 'onboarding.completed', 'store.context'])
    ->group(function () {

        // ── User Management ────────────────────────────────────
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminUserController::class, 'index'])
                ->middleware('permission:' . PermissionEnum::USER_VIEW);

            Route::get('/{user}', [AdminUserController::class, 'show'])
                ->middleware('permission:' . PermissionEnum::USER_VIEW);

            Route::patch('/{user}/block', [AdminUserController::class, 'block'])
                ->middleware('permission:' . PermissionEnum::USER_BLOCK);

            Route::patch('/{user}/unblock', [AdminUserController::class, 'unblock'])
                ->middleware('permission:' . PermissionEnum::USER_BLOCK);

            Route::delete('/{user}', [AdminUserController::class, 'destroy'])
                ->middleware('permission:' . PermissionEnum::USER_DELETE);

            Route::patch('/{user}/restore', [AdminUserController::class, 'restore'])
                ->middleware('permission:' . PermissionEnum::USER_RESTORE);
        });

        // ── Product Management ─────────────────────────────────
        Route::prefix('products')->group(function () {
            Route::get('/', [AdminProductController::class, 'index'])
                ->middleware('permission:' . PermissionEnum::PRODUCT_VIEW);

            Route::get('/{product}', [AdminProductController::class, 'show'])
                ->middleware('permission:' . PermissionEnum::PRODUCT_VIEW);

            Route::post('/', [AdminProductController::class, 'store'])
                ->middleware('permission:' . PermissionEnum::PRODUCT_CREATE);

            Route::patch('/{product}', [AdminProductController::class, 'update'])
                ->middleware('permission:' . PermissionEnum::PRODUCT_UPDATE);

            Route::delete('/{product}', [AdminProductController::class, 'destroy'])
                ->middleware('permission:' . PermissionEnum::PRODUCT_DELETE);

            Route::patch('/{product}/restore', [AdminProductController::class, 'restore'])
                ->middleware('permission:' . PermissionEnum::PRODUCT_RESTORE);
        });

        // ── Order Management ───────────────────────────────────
        Route::prefix('orders')->group(function () {
            Route::get('/', [AdminOrderController::class, 'index'])
                ->middleware('permission:' . PermissionEnum::ORDER_VIEW);

            Route::get('/{order}', [AdminOrderController::class, 'show'])
                ->middleware('permission:' . PermissionEnum::ORDER_VIEW);

            Route::patch('/{order}/status', [AdminOrderController::class, 'updateStatus'])
                ->middleware('permission:' . PermissionEnum::ORDER_UPDATE_STATUS);

            Route::patch('/{order}/cancel', [AdminOrderController::class, 'cancel'])
                ->middleware('permission:' . PermissionEnum::ORDER_CANCEL);

            Route::patch('/{order}/refund', [AdminOrderController::class, 'refund'])
                ->middleware('permission:' . PermissionEnum::ORDER_REFUND);
        });

        // ── Dashboard ──────────────────────────────────────────
        Route::prefix('dashboard')->group(function () {
            Route::get('/stats', [AdminDashboardController::class, 'stats'])
                ->middleware('permission:' . PermissionEnum::DASHBOARD_VIEW);

            Route::get('/recent-orders', [AdminDashboardController::class, 'recentOrders'])
                ->middleware('permission:' . PermissionEnum::DASHBOARD_VIEW);

            Route::get('/top-products', [AdminDashboardController::class, 'topProducts'])
                ->middleware('permission:' . PermissionEnum::DASHBOARD_VIEW);
        });

        // ── Category Management ────────────────────────────────
        Route::prefix('categories')->group(function () {
            Route::get('/', [AdminCategoryController::class, 'index'])
                ->middleware('permission:' . PermissionEnum::CATEGORY_VIEW);

            Route::get('/{category}', [AdminCategoryController::class, 'show'])
                ->middleware('permission:' . PermissionEnum::CATEGORY_VIEW);

            Route::post('/', [AdminCategoryController::class, 'store'])
                ->middleware('permission:' . PermissionEnum::CATEGORY_CREATE);

            Route::patch('/{category}', [AdminCategoryController::class, 'update'])
                ->middleware('permission:' . PermissionEnum::CATEGORY_UPDATE);

            Route::delete('/{category}', [AdminCategoryController::class, 'destroy'])
                ->middleware('permission:' . PermissionEnum::CATEGORY_DELETE);

            Route::patch('/{category}/restore', [AdminCategoryController::class, 'restore'])
                ->middleware('permission:' . PermissionEnum::CATEGORY_RESTORE);
        });

        // ── Brand Management ───────────────────────────────────
        Route::prefix('brands')->group(function () {
            Route::get('/', [AdminBrandController::class, 'index'])
                ->middleware('permission:' . PermissionEnum::BRAND_VIEW);

            Route::get('/{brand}', [AdminBrandController::class, 'show'])
                ->middleware('permission:' . PermissionEnum::BRAND_VIEW);

            Route::post('/', [AdminBrandController::class, 'store'])
                ->middleware('permission:' . PermissionEnum::BRAND_CREATE);

            Route::patch('/{brand}', [AdminBrandController::class, 'update'])
                ->middleware('permission:' . PermissionEnum::BRAND_UPDATE);

            Route::delete('/{brand}', [AdminBrandController::class, 'destroy'])
                ->middleware('permission:' . PermissionEnum::BRAND_DELETE);

            Route::patch('/{brand}/restore', [AdminBrandController::class, 'restore'])
                ->middleware('permission:' . PermissionEnum::BRAND_RESTORE);
        });

        // ── Tag Management ───────────────────────────────────
        Route::prefix('tags')->group(function () {
            Route::get('/', [AdminTagController::class, 'index'])
                ->middleware('permission:' . PermissionEnum::TAG_VIEW)
                ->name('tags.index');

            Route::post('/', [AdminTagController::class, 'store'])
                ->middleware('permission:' . PermissionEnum::TAG_CREATE)
                ->name('tags.store');

            Route::get('/{tag}', [AdminTagController::class, 'show'])
                ->middleware('permission:' . PermissionEnum::TAG_VIEW)
                ->name('tags.show');

            Route::match(['put', 'patch'], '/{tag}', [AdminTagController::class, 'update'])
                ->middleware('permission:' . PermissionEnum::TAG_UPDATE)
                ->name('tags.update');

            Route::delete('/{tag}', [AdminTagController::class, 'destroy'])
                ->middleware('permission:' . PermissionEnum::TAG_DELETE)
                ->name('tags.destroy');
        });
    });
