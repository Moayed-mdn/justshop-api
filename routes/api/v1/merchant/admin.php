<?php

use App\Http\Controllers\Api\Merchant\AdminBrandController;
use App\Http\Controllers\Api\Merchant\AdminCategoryController;
use App\Http\Controllers\Api\Merchant\AdminDashboardController;
use App\Http\Controllers\Api\Merchant\AdminFileUploadController;
use App\Http\Controllers\Api\Merchant\AdminMarketingSectionTypeController;
use App\Http\Controllers\Api\Merchant\AdminMediaController;
use App\Http\Controllers\Api\Merchant\AdminStoreMarketingPageController;
use App\Http\Controllers\Api\Merchant\AdminOrderController;
use App\Http\Controllers\Api\Merchant\AdminProductController;
use App\Http\Controllers\Api\Merchant\AdminTagController;
use App\Http\Controllers\Api\Merchant\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::name('merchant.')
    ->prefix('stores/{store}')
    ->withoutMiddleware(['identity.route:merchant_users,merchant,enforce'])
    ->middleware(['auth:sanctum', 'identity.route:merchant_admin,merchant,enforce', 'store.context'])
    ->group(function () {

        // ── User Management ────────────────────────────────────
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminUserController::class, 'index'])
                ->name('users.index');

            Route::post('/', [AdminUserController::class, 'store'])
                ->name('users.store');

            Route::get('/{user}', [AdminUserController::class, 'show'])
                ->name('users.show');

            Route::patch('/{user}/block', [AdminUserController::class, 'block'])
                ->name('users.block');

            Route::patch('/{user}/unblock', [AdminUserController::class, 'unblock'])
                ->name('users.unblock');

            Route::delete('/{user}', [AdminUserController::class, 'destroy'])
                ->name('users.destroy');

            Route::patch('/{user}/restore', [AdminUserController::class, 'restore'])
                ->name('users.restore');
        });

        // ── Product Management ─────────────────────────────────
        Route::prefix('products')->group(function () {
            Route::get('/', [AdminProductController::class, 'index'])
                ->name('products.index');

            Route::get('/{product}', [AdminProductController::class, 'show'])
                ->name('products.show');

            Route::post('/', [AdminProductController::class, 'store'])
                ->name('products.store');

            Route::patch('/{product}', [AdminProductController::class, 'update'])
                ->name('products.update');

            Route::delete('/{product}', [AdminProductController::class, 'destroy'])
                ->name('products.destroy');

            Route::patch('/{product}/restore', [AdminProductController::class, 'restore'])
                ->name('products.restore');
        });

        // ── Order Management ───────────────────────────────────
        Route::prefix('orders')->group(function () {
            Route::get('/', [AdminOrderController::class, 'index'])
                ->name('orders.index');

            Route::get('/{order}', [AdminOrderController::class, 'show'])
                ->name('orders.show');

            Route::patch('/{order}/status', [AdminOrderController::class, 'updateStatus'])
                ->name('orders.status');

            Route::patch('/{order}/cancel', [AdminOrderController::class, 'cancel'])
                ->name('orders.cancel');

            Route::patch('/{order}/refund', [AdminOrderController::class, 'refund'])
                ->name('orders.refund');
        });

        // ── Dashboard ──────────────────────────────────────────
        Route::prefix('dashboard')->group(function () {
            Route::get('/stats', [AdminDashboardController::class, 'stats'])
                ->name('dashboard.stats');

            Route::get('/recent-orders', [AdminDashboardController::class, 'recentOrders'])
                ->name('dashboard.recent-orders');

            Route::get('/top-products', [AdminDashboardController::class, 'topProducts'])
                ->name('dashboard.top-products');
        });

        // ── Category Management ────────────────────────────────
        Route::prefix('categories')->group(function () {
            Route::get('/', [AdminCategoryController::class, 'index'])
                ->name('categories.index');

            Route::get('/{category}', [AdminCategoryController::class, 'show'])
                ->name('categories.show');

            Route::post('/', [AdminCategoryController::class, 'store'])
                ->name('categories.store');

            Route::patch('/{category}', [AdminCategoryController::class, 'update'])
                ->name('categories.update');

            Route::delete('/{category}', [AdminCategoryController::class, 'destroy'])
                ->name('categories.destroy');

            Route::patch('/{category}/restore', [AdminCategoryController::class, 'restore'])
                ->name('categories.restore');
        });

        // ── Brand Management ───────────────────────────────────
        Route::prefix('brands')->group(function () {
            Route::get('/', [AdminBrandController::class, 'index'])
                ->name('brands.index');

            Route::get('/{brand}', [AdminBrandController::class, 'show'])
                ->name('brands.show');

            Route::post('/', [AdminBrandController::class, 'store'])
                ->name('brands.store');

            Route::patch('/{brand}', [AdminBrandController::class, 'update'])
                ->name('brands.update');

            Route::delete('/{brand}', [AdminBrandController::class, 'destroy'])
                ->name('brands.destroy');

            Route::patch('/{brand}/restore', [AdminBrandController::class, 'restore'])
                ->name('brands.restore');
        });

        // ── Tag Management ───────────────────────────────────
        Route::prefix('tags')->group(function () {
            Route::get('/', [AdminTagController::class, 'index'])
                ->name('tags.index');

            Route::post('/', [AdminTagController::class, 'store'])
                ->name('tags.store');

            Route::get('/{tag}', [AdminTagController::class, 'show'])
                ->name('tags.show');

            Route::match(['put', 'patch'], '/{tag}', [AdminTagController::class, 'update'])
                ->name('tags.update');

            Route::delete('/{tag}', [AdminTagController::class, 'destroy'])
                ->name('tags.destroy');
        });

        // ── Store Marketing CMS ────────────────────────────────
        Route::prefix('cms/pages')
            ->controller(AdminStoreMarketingPageController::class)
            ->group(function () {
                Route::get('/', 'index')
                    ->name('cms.pages.index');
                Route::post('/', 'store')
                    ->name('cms.pages.store');
                Route::get('/check-homepage', 'checkHomepage')
                    ->name('cms.pages.check-homepage');
                Route::get('/{id}', 'show')
                    ->name('cms.pages.show');
                Route::put('/{id}', 'update')
                    ->name('cms.pages.update');
                Route::delete('/{id}', 'destroy')
                    ->name('cms.pages.destroy');
                // Publish workflow
                Route::post('/{id}/publish', 'publish')
                    ->name('cms.pages.publish');
                Route::post('/{id}/unpublish', 'unpublish')
                    ->name('cms.pages.unpublish');
            });

        // ── Marketing Section Types (backend-driven) ──────────
        Route::get('cms/section-types', [AdminMarketingSectionTypeController::class, 'index'])
            ->name('cms.section-types');

        // ── Navigation Permissions ─────────────────────────────
        Route::get('navigation-permissions', [\App\Http\Controllers\Api\Merchant\NavigationPermissionsController::class, 'index'])
            ->name('navigation-permissions');

        // ── Generic Media Upload ───────────────────────────────
        Route::prefix('media')->group(function () {
            Route::post('/upload', [AdminMediaController::class, 'upload'])
                ->name('media.upload');
            
            Route::delete('/delete', [AdminMediaController::class, 'delete'])
                ->name('media.delete');
        });
    });
