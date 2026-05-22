<?php

use App\Http\Controllers\Api\Cms\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sitemap Routes
|--------------------------------------------------------------------------
|
| These routes serve structured sitemap data as JSON for Next.js.
| Next.js consumes these in its sitemap.ts file to generate XML sitemaps.
|
| All endpoints are:
| - Public (no auth required)
| - Cached (tag-based cache, invalidated on publish)
| - Published-only (never expose draft content)
|
*/

Route::prefix('v1/cms/sitemap')
    ->controller(SitemapController::class)
    ->group(function (): void {
        Route::get('/marketing', 'marketing')->name('cms.sitemap.marketing');
        Route::get('/blog', 'blog')->name('cms.sitemap.blog');
    });
