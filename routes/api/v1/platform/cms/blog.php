<?php

use App\Http\Controllers\Api\Platform\AdminBlogController;
use App\Http\Controllers\Api\Platform\Mock\PlatformBlogController;
use Illuminate\Support\Facades\Route;

// Use mock controller for frontend development
// TODO: Switch back to AdminBlogController when backend is ready
$useMock = true;
$controller = $useMock ? PlatformBlogController::class : AdminBlogController::class;

Route::prefix('cms/blog')
    ->name('platform.cms.blog.')
    ->controller($controller)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::post('/{id}/publish', 'publish')->name('publish');
        Route::post('/{id}/unpublish', 'unpublish')->name('unpublish');
        Route::post('/{id}/schedule', 'schedule')->name('schedule');
        
        // Additional endpoints for frontend
        Route::get('/meta/categories', 'categories')->name('categories');
        Route::get('/meta/tags', 'tags')->name('tags');
    });
