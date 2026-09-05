<?php

use App\Http\Controllers\Api\Platform\AdminBlogController;
use Illuminate\Support\Facades\Route;

// Real blog controller implementation.
//
// NOTE: this used to point at App\Http\Controllers\Api\Platform\
// PlatformBlogController, which imported 11 Action classes and 3 Resource
// classes that did not exist anywhere in the codebase (a pre-Admin/Public
// split draft that was never updated) -- every request to this group would
// 500. AdminBlogController is the complete, correct, already-implemented
// version (proper CreateBlogPostAction/DTO/Request, BlogPostPolicy
// authorization, AdminBlogPostResource) that was simply never wired to a
// route. This file now points here instead.
//
// Known follow-up (not required by any current test, and intentionally not
// built speculatively): the old dead controller also exposed
// GET /meta/categories and /meta/tags convenience endpoints. Those would
// need BlogCategory/BlogTag translations() relations and their own
// authorization policy added first -- out of scope for this fix.
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
