<?php

use App\Http\Controllers\Api\Merchant\Asset\StoreAssetController;
use App\Http\Controllers\Api\Merchant\Navigation\NavigationMenuController;
use App\Http\Controllers\Api\Merchant\Navigation\NavigationMenuItemController;
use App\Http\Controllers\Api\Merchant\Navigation\NavigationResourceController;
use App\Http\Controllers\Api\Merchant\Theme\ThemeBlockController;
use App\Http\Controllers\Api\Merchant\Theme\ThemeController;
use App\Http\Controllers\Api\Merchant\Theme\ThemeSectionController;
use Illuminate\Support\Facades\Route;

// Theme Management Routes
// These routes handle theme customization, navigation, and assets for merchant stores

Route::name('merchant.')
    ->prefix('stores/{store}')
    ->middleware(['auth:sanctum', 'identity.route:merchant_admin,merchant,enforce', 'store.context'])
    ->group(function () {

        // ── Themes ──────────────────────────────────────────────
        Route::prefix('themes')->name('themes.')->group(function () {
            Route::get('/', [ThemeController::class, 'index'])->name('index');
            Route::post('/', [ThemeController::class, 'store'])->name('store');
            Route::get('/{theme}', [ThemeController::class, 'show'])->name('show');
            Route::put('/{theme}', [ThemeController::class, 'update'])->name('update');
            Route::delete('/{theme}', [ThemeController::class, 'destroy'])->name('destroy');
            Route::post('/{theme}/publish', [ThemeController::class, 'publish'])->name('publish');
            Route::post('/{theme}/duplicate', [ThemeController::class, 'duplicate'])->name('duplicate');

            // ── Theme Sections ──────────────────────────────────
            Route::prefix('{theme}/sections')->name('sections.')->group(function () {
                Route::get('/', [ThemeSectionController::class, 'index'])->name('index');
                Route::post('/', [ThemeSectionController::class, 'store'])->name('store');
                Route::post('/reorder', [ThemeSectionController::class, 'reorder'])->name('reorder');
                Route::get('/{section}', [ThemeSectionController::class, 'show'])->name('show');
                Route::put('/{section}', [ThemeSectionController::class, 'update'])->name('update');
                Route::delete('/{section}', [ThemeSectionController::class, 'destroy'])->name('destroy');

                // ── Section Blocks ──────────────────────────────
                Route::prefix('{section}/blocks')->name('blocks.')->group(function () {
                    Route::get('/', [ThemeBlockController::class, 'index'])->name('index');
                    Route::post('/', [ThemeBlockController::class, 'store'])->name('store');
                    Route::post('/reorder', [ThemeBlockController::class, 'reorder'])->name('reorder');
                    Route::get('/{block}', [ThemeBlockController::class, 'show'])->name('show');
                    Route::put('/{block}', [ThemeBlockController::class, 'update'])->name('update');
                    Route::delete('/{block}', [ThemeBlockController::class, 'destroy'])->name('destroy');
                });
            });
        });

        // ── Navigation Menus ────────────────────────────────────
        Route::prefix('navigation')->name('navigation.')->group(function () {
            Route::get('/', [NavigationMenuController::class, 'index'])->name('index');
            Route::post('/', [NavigationMenuController::class, 'store'])->name('store');
            Route::get('/{menu}', [NavigationMenuController::class, 'show'])->name('show');
            Route::put('/{menu}', [NavigationMenuController::class, 'update'])->name('update');
            Route::delete('/{menu}', [NavigationMenuController::class, 'destroy'])->name('destroy');

            // ── Menu Items ──────────────────────────────────────
            Route::prefix('{menu}/items')->name('items.')->group(function () {
                Route::post('/', [NavigationMenuItemController::class, 'store'])->name('store');
                Route::post('/reorder', [NavigationMenuItemController::class, 'reorder'])->name('reorder');
                Route::put('/{item}', [NavigationMenuItemController::class, 'update'])->name('update');
                Route::delete('/{item}', [NavigationMenuItemController::class, 'destroy'])->name('destroy');
            });

            // ── Available Resources for Linking ─────────────────
            Route::prefix('resources')->name('resources.')->group(function () {
                Route::get('/pages', [NavigationResourceController::class, 'pages'])->name('pages');
                Route::get('/categories', [NavigationResourceController::class, 'categories'])->name('categories');
                Route::get('/products', [NavigationResourceController::class, 'products'])->name('products');
                Route::get('/{type}/{id}', [NavigationResourceController::class, 'show'])->name('show');
            });

            // ── URL Validation ───────────────────────────────────
            Route::post('/validate-url', [NavigationResourceController::class, 'validateUrl'])->name('validate-url');
        });

        // ── Store Assets ────────────────────────────────────────
        Route::prefix('assets')->name('assets.')->group(function () {
            Route::get('/', [StoreAssetController::class, 'index'])->name('index');
            Route::post('/', [StoreAssetController::class, 'store'])->name('store');
            Route::put('/{asset}', [StoreAssetController::class, 'update'])->name('update');
            Route::delete('/{asset}', [StoreAssetController::class, 'destroy'])->name('destroy');
        });
    });
