<?php

use App\Http\Controllers\Api\Cms\Blog\AdminBlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin/cms/blog')
    ->middleware(['auth:sanctum'])
    ->controller(AdminBlogController::class)
    ->group(function (): void {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{blogPost}', 'show');
        Route::put('/{blogPost}', 'update');
        Route::delete('/{blogPost}', 'destroy');
        Route::post('/{blogPost}/publish', 'publish');
        Route::post('/{blogPost}/unpublish', 'unpublish');
        Route::post('/{blogPost}/schedule', 'schedule');
    });
