<?php

use App\Http\Controllers\Api\Cms\Blog\PublicBlogController;
use App\Http\Controllers\Api\Cms\Documentation\PublicDocumentController;
use App\Http\Controllers\Api\Cms\Marketing\PublicMarketingController;
use App\Http\Controllers\Api\Cms\Seo\PublicCmsSeoController;
use Illuminate\Support\Facades\Route;

/**
 * Public Marketing CMS Routes
 * Prefix: /api/v1/public/cms
 */
Route::prefix('v1/public/cms')->group(function () {
    
    // ── Marketing Pages ───────────────────────────────────
    Route::get('/pages/{slug}', [PublicMarketingController::class, 'show']);

    // ── Blog ──────────────────────────────────────────────
    Route::prefix('blog')->controller(PublicBlogController::class)->group(function () {
        Route::get('/', 'index')->name('public.blog.index');
        Route::get('/{slug}', 'show')->name('public.blog.show');
    });

    // ── Documentation ─────────────────────────────────────
    Route::prefix('docs')->controller(PublicDocumentController::class)->group(function () {
        Route::get('/sidebar', 'sidebar');
        Route::get('/{slugPath}/navigation', 'navigation')->where('slugPath', '.*');
        Route::get('/{slugPath}', 'show')->where('slugPath', '.*');
    });

    // ── SEO & Sitemap ─────────────────────────────────────
    Route::prefix('seo')->controller(PublicCmsSeoController::class)->group(function () {
        Route::get('/sitemap/marketing', 'marketing');
        Route::get('/sitemap/blog', 'blog');
        Route::get('/sitemap/docs', 'docs');
        Route::get('/robots.txt', 'robots');
    });
});
