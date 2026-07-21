<?php

use App\Http\Controllers\Api\Platform\PlatformBlogController;
use Illuminate\Support\Facades\Route;

// Real blog controller implementation
Route::prefix('cms/blog')
    ->name('platform.cms.blog.')
    ->controller(PlatformBlogController::class)
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
