<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Platform\PlatformAuthController;
use Illuminate\Support\Facades\Route;

/**
 * Platform Authentication Routes
 * 
 * Wave 6: Platform-specific authentication endpoints.
 * These are SEPARATE from merchant auth to ensure proper session tagging.
 * 
 * Note: These routes do NOT have platform.authority middleware.
 * The identity.route middleware is sufficient for auth endpoints.
 */

// Unauthenticated platform auth endpoint
Route::withoutMiddleware(['auth:sanctum'])
    ->prefix('auth')
    ->name('platform.auth.')
    ->group(function (): void {
        Route::post('/login', [PlatformAuthController::class, 'login'])
            ->name('login');
    });

// Authenticated platform auth endpoints  
Route::prefix('auth')
    ->name('platform.auth.')
    ->group(function (): void {
        Route::get('/me', [PlatformAuthController::class, 'me'])
            ->name('me');
        
        Route::post('/logout', [PlatformAuthController::class, 'logout'])
            ->name('logout');
    });
