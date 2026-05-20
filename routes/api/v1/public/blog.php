<?php

use App\Http\Controllers\Api\Cms\Blog\BlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/public/blog')
    ->controller(BlogController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('public.blog.index');
        Route::get('/{slug}', 'show')->name('public.blog.show');
    });
