<?php

use App\Http\Controllers\Api\Platform\AdminPlatformMarketingPageController;
use Illuminate\Support\Facades\Route;

$controller = AdminPlatformMarketingPageController::class;

Route::prefix('cms/pages')
    ->name('platform.cms.pages.')
    ->controller($controller)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::post('/{id}/publish', 'publish')->name('publish');
    });
