<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * Platform Routes
 * 
 * Wave 6: Explicit platform authority topology.
 * These routes are INDEPENDENT from merchant routes.
 * Platform authority is NOT merchant authority with extra permissions.
 * 
 * Middleware: platform.authority:platform_admin
 * Guard: merchant (platform actors use merchant guard)
 * Actor: SUPER_ADMIN only
 */

// Platform dashboard & analytics
Route::get('/dashboard', [\App\Http\Controllers\Api\Platform\PlatformDashboardController::class, 'index']);
Route::get('/analytics', [\App\Http\Controllers\Api\Platform\PlatformAnalyticsController::class, 'index']);

// Platform user management (NOT merchant user management)
Route::prefix('/users')->group(function (): void {
    Route::get('/', [\App\Http\Controllers\Api\Platform\PlatformUserController::class, 'index']);
    Route::get('/{user}', [\App\Http\Controllers\Api\Platform\PlatformUserController::class, 'show']);
    Route::patch('/{user}/suspend', [\App\Http\Controllers\Api\Platform\PlatformUserController::class, 'suspend']);
    Route::patch('/{user}/activate', [\App\Http\Controllers\Api\Platform\PlatformUserController::class, 'activate']);
});

// Platform store management (NOT merchant store management)
Route::prefix('/stores')->group(function (): void {
    Route::get('/', [\App\Http\Controllers\Api\Platform\PlatformStoreController::class, 'index']);
    Route::get('/{store}', [\App\Http\Controllers\Api\Platform\PlatformStoreController::class, 'show']);
    Route::patch('/{store}/suspend', [\App\Http\Controllers\Api\Platform\PlatformStoreController::class, 'suspend']);
    Route::patch('/{store}/activate', [\App\Http\Controllers\Api\Platform\PlatformStoreController::class, 'activate']);
});

// Platform audit logs
Route::prefix('/audit')->group(function (): void {
    Route::get('/logs', [\App\Http\Controllers\Api\Platform\PlatformAuditController::class, 'index']);
    Route::get('/logs/{log}', [\App\Http\Controllers\Api\Platform\PlatformAuditController::class, 'show']);
});

// Platform feature flags
Route::prefix('/features')->group(function (): void {
    Route::get('/', [\App\Http\Controllers\Api\Platform\PlatformFeatureController::class, 'index']);
    Route::patch('/{feature}', [\App\Http\Controllers\Api\Platform\PlatformFeatureController::class, 'update']);
});
