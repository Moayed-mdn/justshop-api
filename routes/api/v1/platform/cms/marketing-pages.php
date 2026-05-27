<?php

use App\Http\Controllers\Api\Platform\AdminMarketingPageController;
use Illuminate\Support\Facades\Route;

Route::prefix('cms/pages')
    ->name('platform.cms.pages.')
    ->group(function (): void {
        // --- Marketing Pages (General) ---
        Route::controller(AdminMarketingPageController::class)->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}', 'show')->name('show');
            Route::put('/{id}', 'update')->name('update');
            Route::delete('/{id}', 'destroy')->name('destroy');
            Route::post('/{id}/publish', 'publish')->name('publish');
        });

        // --- Platform Specific Marketing Pages ---
        Route::prefix('platform')
            ->name('platform.')
            ->controller(\App\Http\Controllers\Api\Platform\AdminPlatformMarketingPageController::class)
            ->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::post('/{id}/publish', 'publish')->name('publish');
            });
    });
