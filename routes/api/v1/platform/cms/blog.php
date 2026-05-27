<?php

use App\Http\Controllers\Api\Platform\AdminBlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('cms/blog')
    ->name('platform.cms.blog.')
    ->controller(AdminBlogController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{blogPost}', 'show')->name('show');
        Route::put('/{blogPost}', 'update')->name('update');
        Route::delete('/{blogPost}', 'destroy')->name('destroy');
        Route::post('/{blogPost}/publish', 'publish')->name('publish');
        Route::post('/{blogPost}/unpublish', 'unpublish')->name('unpublish');
        Route::post('/{blogPost}/schedule', 'schedule')->name('schedule');
    });
