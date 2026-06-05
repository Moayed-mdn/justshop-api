<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\Api\Merchant\AdminBrandController;
use App\Http\Controllers\Api\Merchant\AdminCategoryController;
use App\Http\Controllers\Api\Merchant\AdminDashboardController;
use App\Http\Controllers\Api\Merchant\AdminFileUploadController;
use App\Http\Controllers\Api\Merchant\AdminHeroBannerController;
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
                ->middleware('permission:' . PermissionEnum::USER_VIEW)
                ->name('users.index');

            Route::post('/', [AdminUserController::class, 'store'])
                ->middleware('permission:' . PermissionEnum::USER_CREATE)
                ->name('users.store');

            Route::get('/{user}', [AdminUserController::class, 'show'])
                ->middleware('permission:' . PermissionEnum::USER_VIEW)
                ->name('users.show');

            Route::patch('/{user}/block', [AdminUserController::class, 'block'])
                ->middleware('permission:' . PermissionEnum::USER_BLOCK)
                ->name('users.block');

            Route::patch('/{user}/unblock', [AdminUserController::class, 'unblock'])
                ->middleware('permission:' . PermissionEnum::USER_BLOCK)
                ->name('users.unblock');

            Route::delete('/{user}', [AdminUserController::class, 'destroy'])
                ->middleware('permission:' . PermissionEnum::USER_DELETE)
                ->name('users.destroy');

            Route::patch('/{user}/restore', [AdminUserController::class, 'restore'])
                ->middleware('permission:' . PermissionEnum::USER_RESTORE)
                ->name('users.restore');
        });

        // ── Product Management ─────────────────────────────────
        Route::prefix('products')->group(function () {
            Route::get('/', [AdminProductController::class, 'index'])
                ->middleware('permission:' . PermissionEnum::PRODUCT_VIEW)
                ->name('products.index');

            Route::get('/{product}', [AdminProductController::class, 'show'])
                ->middleware('permission:' . PermissionEnum::PRODUCT_VIEW)
                ->name('products.show');

            Route::post('/', [AdminProductController::class, 'store'])
                ->middleware('permission:' . PermissionEnum::PRODUCT_CREATE)
                ->name('products.store');

            Route::patch('/{product}', [AdminProductController::class, 'update'])
                ->middleware('permission:' . PermissionEnum::PRODUCT_UPDATE)
                ->name('products.update');

            Route::delete('/{product}', [AdminProductController::class, 'destroy'])
                ->middleware('permission:' . PermissionEnum::PRODUCT_DELETE)
                ->name('products.destroy');

            Route::patch('/{product}/restore', [AdminProductController::class, 'restore'])
                ->middleware('permission:' . PermissionEnum::PRODUCT_RESTORE)
                ->name('products.restore');
        });

        // ── Order Management ───────────────────────────────────
        Route::prefix('orders')->group(function () {
            Route::get('/', [AdminOrderController::class, 'index'])
                ->middleware('permission:' . PermissionEnum::ORDER_VIEW)
                ->name('orders.index');

            Route::get('/{order}', [AdminOrderController::class, 'show'])
                ->middleware('permission:' . PermissionEnum::ORDER_VIEW)
                ->name('orders.show');

            Route::patch('/{order}/status', [AdminOrderController::class, 'updateStatus'])
                ->middleware('permission:' . PermissionEnum::ORDER_UPDATE_STATUS)
                ->name('orders.status');

            Route::patch('/{order}/cancel', [AdminOrderController::class, 'cancel'])
                ->middleware('permission:' . PermissionEnum::ORDER_CANCEL)
                ->name('orders.cancel');

            Route::patch('/{order}/refund', [AdminOrderController::class, 'refund'])
                ->middleware('permission:' . PermissionEnum::ORDER_REFUND)
                ->name('orders.refund');
        });

        // ── Dashboard ──────────────────────────────────────────
        Route::prefix('dashboard')->group(function () {
            Route::get('/stats', [AdminDashboardController::class, 'stats'])
                ->middleware('permission:' . PermissionEnum::DASHBOARD_VIEW)
                ->name('dashboard.stats');

            Route::get('/recent-orders', [AdminDashboardController::class, 'recentOrders'])
                ->middleware('permission:' . PermissionEnum::DASHBOARD_VIEW)
                ->name('dashboard.recent-orders');

            Route::get('/top-products', [AdminDashboardController::class, 'topProducts'])
                ->middleware('permission:' . PermissionEnum::DASHBOARD_VIEW)
                ->name('dashboard.top-products');
        });

        // ── Category Management ────────────────────────────────
        Route::prefix('categories')->group(function () {
            Route::get('/', [AdminCategoryController::class, 'index'])
                ->middleware('permission:' . PermissionEnum::CATEGORY_VIEW)
                ->name('categories.index');

            Route::get('/{category}', [AdminCategoryController::class, 'show'])
                ->middleware('permission:' . PermissionEnum::CATEGORY_VIEW)
                ->name('categories.show');

            Route::post('/', [AdminCategoryController::class, 'store'])
                ->middleware('permission:' . PermissionEnum::CATEGORY_CREATE)
                ->name('categories.store');

            Route::patch('/{category}', [AdminCategoryController::class, 'update'])
                ->middleware('permission:' . PermissionEnum::CATEGORY_UPDATE)
                ->name('categories.update');

            Route::delete('/{category}', [AdminCategoryController::class, 'destroy'])
                ->middleware('permission:' . PermissionEnum::CATEGORY_DELETE)
                ->name('categories.destroy');

            Route::patch('/{category}/restore', [AdminCategoryController::class, 'restore'])
                ->middleware('permission:' . PermissionEnum::CATEGORY_RESTORE)
                ->name('categories.restore');
        });

        // ── Brand Management ───────────────────────────────────
        Route::prefix('brands')->group(function () {
            Route::get('/', [AdminBrandController::class, 'index'])
                ->middleware('permission:' . PermissionEnum::BRAND_VIEW)
                ->name('brands.index');

            Route::get('/{brand}', [AdminBrandController::class, 'show'])
                ->middleware('permission:' . PermissionEnum::BRAND_VIEW)
                ->name('brands.show');

            Route::post('/', [AdminBrandController::class, 'store'])
                ->middleware('permission:' . PermissionEnum::BRAND_CREATE)
                ->name('brands.store');

            Route::patch('/{brand}', [AdminBrandController::class, 'update'])
                ->middleware('permission:' . PermissionEnum::BRAND_UPDATE)
                ->name('brands.update');

            Route::delete('/{brand}', [AdminBrandController::class, 'destroy'])
                ->middleware('permission:' . PermissionEnum::BRAND_DELETE)
                ->name('brands.destroy');

            Route::patch('/{brand}/restore', [AdminBrandController::class, 'restore'])
                ->middleware('permission:' . PermissionEnum::BRAND_RESTORE)
                ->name('brands.restore');
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

        // ── Hero Banner Management ─────────────────────────────
        Route::prefix('hero-banners')->group(function () {
            Route::get('/', [AdminHeroBannerController::class, 'index'])
                ->name('hero-banners.index');

            Route::post('/', [AdminHeroBannerController::class, 'store'])
                ->name('hero-banners.store');

            Route::get('/{id}', [AdminHeroBannerController::class, 'show'])
                ->name('hero-banners.show');

            Route::match(['put', 'patch'], '/{id}', [AdminHeroBannerController::class, 'update'])
                ->name('hero-banners.update');

            Route::delete('/{id}', [AdminHeroBannerController::class, 'destroy'])
                ->name('hero-banners.destroy');

            Route::patch('/{id}/restore', [AdminHeroBannerController::class, 'restore'])
                ->name('hero-banners.restore');
            
            // File upload routes
            Route::post('/upload-image', [AdminFileUploadController::class, 'uploadHeroBannerImage'])
                ->name('hero-banners.upload-image');
            
            Route::delete('/delete-image', [AdminFileUploadController::class, 'deleteHeroBannerImage'])
                ->name('hero-banners.delete-image');
        });

        // ── Store Marketing CMS ────────────────────────────────
        Route::prefix('cms/pages')
            ->controller(AdminStoreMarketingPageController::class)
            ->group(function () {
                Route::get('/', 'index')->name('cms.pages.index');
                Route::post('/', 'store')->name('cms.pages.store');
                Route::get('/{id}', 'show')->name('cms.pages.show');
                Route::put('/{id}', 'update')->name('cms.pages.update');
                Route::delete('/{id}', 'destroy')->name('cms.pages.destroy');
                // Publish workflow
                Route::post('/{id}/publish', 'publish')->name('cms.pages.publish');
                Route::post('/{id}/unpublish', 'unpublish')->name('cms.pages.unpublish');
            });

        // ── Marketing Section Types (backend-driven) ──────────
        Route::get('cms/section-types', [AdminMarketingSectionTypeController::class, 'index'])
            ->name('cms.section-types');

        // ── Generic Media Upload ───────────────────────────────
        Route::prefix('media')->group(function () {
            Route::post('/upload', [AdminMediaController::class, 'upload'])
                ->name('media.upload');
            
            Route::delete('/delete', [AdminMediaController::class, 'delete'])
                ->name('media.delete');
        });
    });
