<?php

use App\Http\Controllers\Api\Admin\Cms\MarketingPage\AdminMarketingPageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin/cms/pages')
    ->middleware(['auth:sanctum', 'verified', 'role:super_admin'])
    ->group(function (): void {
        // --- Legacy Compatibility Routes ---
        Route::controller(AdminMarketingPageController::class)->group(function (): void {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{id}', 'show');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
            Route::post('/{id}/publish', 'publish');
        });

        // --- Platform Specific Routes ---
        Route::prefix('platform')->controller(\App\Http\Controllers\Api\Admin\Cms\Marketing\Platform\AdminPlatformMarketingPageController::class)->group(function (): void {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{id}', 'show');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
            Route::post('/{id}/publish', 'publish');
        });
    });
