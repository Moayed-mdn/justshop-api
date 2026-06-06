<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Storefront\StorefrontRuntimeController;
use Illuminate\Support\Facades\Route;

Route::prefix('runtime')
    ->middleware('storefront.runtime')
    ->group(function (): void {
        Route::get('/resolve', [StorefrontRuntimeController::class, 'resolve']);
        Route::get('/page/{id}', [StorefrontRuntimeController::class, 'page']);
        Route::get('/navigation', [StorefrontRuntimeController::class, 'navigation']);
        Route::get('/theme', [StorefrontRuntimeController::class, 'theme']);
        Route::post('/preview/validate', [StorefrontRuntimeController::class, 'validatePreview']);
    });

// Additional theme and navigation endpoints
Route::prefix('theme')->group(function (): void {
    Route::get('/', [\App\Http\Controllers\Api\Storefront\StorefrontThemeController::class, 'show']);
});

Route::prefix('navigation')->group(function (): void {
    Route::get('/{handle}', [\App\Http\Controllers\Api\Storefront\StorefrontNavigationController::class, 'show']);
});
