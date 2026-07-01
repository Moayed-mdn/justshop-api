<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Storefront\StorefrontRuntimeController;
use Illuminate\Support\Facades\Route;

Route::middleware('storefront.runtime')
    ->group(function (): void {
        Route::get('/resolve', [StorefrontRuntimeController::class, 'resolve']);
        Route::get('/page/{id}', [StorefrontRuntimeController::class, 'page']);
        Route::get('/navigation', [StorefrontRuntimeController::class, 'navigation']);
        Route::get('/theme', [StorefrontRuntimeController::class, 'theme']);
        Route::get('/template/{type}', [StorefrontRuntimeController::class, 'systemTemplate']);
        Route::get('/section-groups', [StorefrontRuntimeController::class, 'sectionGroups']);
        Route::post('/preview/validate', [StorefrontRuntimeController::class, 'validatePreview']);
    });
