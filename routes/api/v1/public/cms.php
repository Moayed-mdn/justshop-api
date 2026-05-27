<?php

use App\Http\Controllers\Api\Public\PublicBlogController;
use App\Http\Controllers\Api\Public\PublicDocumentController;
use App\Http\Controllers\Api\Public\PublicMarketingController;
use App\Http\Controllers\Api\Public\PublicCmsSeoController;
use Illuminate\Support\Facades\Route;

/**
 * Public Marketing CMS Routes
 */
Route::prefix('cms')
    ->name('public.cms.')
    ->group(function () {
    
    // ── Marketing Pages ───────────────────────────────────
    Route::get('/pages/{slug}', [PublicMarketingController::class, 'show'])->name('pages.show');

    // ── Blog ──────────────────────────────────────────────
    Route::prefix('blog')
        ->name('blog.')
        ->controller(PublicBlogController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{slug}', 'show')->name('show');
        });

    // ── Documentation ─────────────────────────────────────
    Route::prefix('docs')
        ->name('docs.')
        ->controller(PublicDocumentController::class)
        ->group(function () {
            Route::get('/sidebar', 'sidebar')->name('sidebar');
            Route::get('/{slugPath}/navigation', 'navigation')->where('slugPath', '.*')->name('navigation');
            Route::get('/{slugPath}', 'show')->where('slugPath', '.*')->name('show');
        });

    // ── SEO & Sitemap ─────────────────────────────────────
    Route::prefix('seo')
        ->name('seo.')
        ->controller(PublicCmsSeoController::class)
        ->group(function () {
            Route::get('/sitemap/marketing', 'marketing')->name('sitemap.marketing');
            Route::get('/sitemap/blog', 'blog')->name('sitemap.blog');
            Route::get('/sitemap/docs', 'docs')->name('sitemap.docs');
            Route::get('/robots.txt', 'robots')->name('robots');
        });
});
