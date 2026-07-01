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
Route::get('/dashboard', [\App\Http\Controllers\Api\Platform\PlatformDashboardController::class, 'index'])->name('platform.dashboard');
Route::get('/analytics', [\App\Http\Controllers\Api\Platform\PlatformAnalyticsController::class, 'index'])->name('platform.analytics');

// Platform user management (NOT merchant user management)
Route::prefix('/users')->name('platform.users.')->group(function (): void {
    Route::get('/', [\App\Http\Controllers\Api\Platform\PlatformUserController::class, 'index'])->name('index');
    Route::get('/{user}', [\App\Http\Controllers\Api\Platform\PlatformUserController::class, 'show'])->name('show');
    Route::patch('/{user}/suspend', [\App\Http\Controllers\Api\Platform\PlatformUserController::class, 'suspend'])->name('suspend');
    Route::patch('/{user}/activate', [\App\Http\Controllers\Api\Platform\PlatformUserController::class, 'activate'])->name('activate');
});

// Platform store management (NOT merchant store management)
Route::prefix('/stores')->name('platform.stores.')->group(function (): void {
    Route::get('/', [\App\Http\Controllers\Api\Platform\PlatformStoreController::class, 'index'])->name('index');
    Route::middleware(['store.context'])->group(function () {
        Route::get('/{store}', [\App\Http\Controllers\Api\Platform\PlatformStoreController::class, 'show'])->name('show');
        Route::patch('/{store}/suspend', [\App\Http\Controllers\Api\Platform\PlatformStoreController::class, 'suspend'])->name('suspend');
        Route::patch('/{store}/activate', [\App\Http\Controllers\Api\Platform\PlatformStoreController::class, 'activate'])->name('activate');
    });
});

// Platform audit logs
Route::prefix('/audit')->name('platform.audit.')->group(function (): void {
    Route::get('/logs', [\App\Http\Controllers\Api\Platform\PlatformAuditController::class, 'index'])->name('index');
    Route::get('/logs/{log}', [\App\Http\Controllers\Api\Platform\PlatformAuditController::class, 'show'])->name('show');
});

// Platform feature flags
Route::prefix('/features')->name('platform.features.')->group(function (): void {
    Route::get('/', [\App\Http\Controllers\Api\Platform\PlatformFeatureController::class, 'index'])->name('index');
    Route::patch('/{feature}', [\App\Http\Controllers\Api\Platform\PlatformFeatureController::class, 'update'])->name('update');
});
