<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * Support Routes
 * 
 * Wave 6: Explicit support authority topology.
 * Support routes are a SUBSET of platform authority.
 * Support actors have LIMITED platform access.
 * 
 * Middleware: support.authority
 * Guard: merchant (support actors use merchant guard)
 * Actor: SUPPORT_AGENT, SUPER_ADMIN
 */

Route::name('support.')->group(function () {
    // Support dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Api\Support\SupportDashboardController::class, 'index'])->name('dashboard');

    // Support ticket management
    Route::prefix('/tickets')->name('tickets.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\Api\Support\SupportTicketController::class, 'index'])->name('index');
        Route::get('/{ticket}', [\App\Http\Controllers\Api\Support\SupportTicketController::class, 'show'])->name('show');
        Route::patch('/{ticket}/assign', [\App\Http\Controllers\Api\Support\SupportTicketController::class, 'assign'])->name('assign');
        Route::patch('/{ticket}/resolve', [\App\Http\Controllers\Api\Support\SupportTicketController::class, 'resolve'])->name('resolve');
        Route::post('/{ticket}/notes', [\App\Http\Controllers\Api\Support\SupportTicketController::class, 'addNote'])->name('notes');
    });

    // Support user lookup (read-only)
    Route::prefix('/users')->name('users.')->group(function (): void {
        Route::get('/search', [\App\Http\Controllers\Api\Support\SupportUserLookupController::class, 'search'])->name('search');
        Route::get('/{user}', [\App\Http\Controllers\Api\Support\SupportUserLookupController::class, 'show'])->name('show');
        Route::get('/{user}/activity', [\App\Http\Controllers\Api\Support\SupportUserLookupController::class, 'activity'])->name('activity');
    });

    // Support store lookup (read-only)
    Route::prefix('/stores')->name('stores.')->group(function (): void {
        Route::get('/search', [\App\Http\Controllers\Api\Support\SupportStoreLookupController::class, 'search'])->name('search');
        Route::middleware(['store.context'])->group(function () {
            Route::get('/{store}', [\App\Http\Controllers\Api\Support\SupportStoreLookupController::class, 'show'])->name('show');
            Route::get('/{store}/activity', [\App\Http\Controllers\Api\Support\SupportStoreLookupController::class, 'activity'])->name('activity');
        });
    });

    // Support impersonation (governed, audited)
    Route::prefix('/impersonation')->name('impersonation.')->group(function (): void {
        Route::post('/request', [\App\Http\Controllers\Api\Support\SupportImpersonationController::class, 'request'])->name('request');
        Route::get('/active', [\App\Http\Controllers\Api\Support\SupportImpersonationController::class, 'active'])->name('active');
        Route::delete('/terminate', [\App\Http\Controllers\Api\Support\SupportImpersonationController::class, 'terminate'])->name('terminate');
    });
});
