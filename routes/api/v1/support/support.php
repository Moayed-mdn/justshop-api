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

// Support dashboard
Route::get('/dashboard', [\App\Http\Controllers\Api\Support\SupportDashboardController::class, 'index']);

// Support ticket management
Route::prefix('/tickets')->group(function (): void {
    Route::get('/', [\App\Http\Controllers\Api\Support\SupportTicketController::class, 'index']);
    Route::get('/{ticket}', [\App\Http\Controllers\Api\Support\SupportTicketController::class, 'show']);
    Route::patch('/{ticket}/assign', [\App\Http\Controllers\Api\Support\SupportTicketController::class, 'assign']);
    Route::patch('/{ticket}/resolve', [\App\Http\Controllers\Api\Support\SupportTicketController::class, 'resolve']);
    Route::post('/{ticket}/notes', [\App\Http\Controllers\Api\Support\SupportTicketController::class, 'addNote']);
});

// Support user lookup (read-only)
Route::prefix('/users')->group(function (): void {
    Route::get('/search', [\App\Http\Controllers\Api\Support\SupportUserLookupController::class, 'search']);
    Route::get('/{user}', [\App\Http\Controllers\Api\Support\SupportUserLookupController::class, 'show']);
    Route::get('/{user}/activity', [\App\Http\Controllers\Api\Support\SupportUserLookupController::class, 'activity']);
});

// Support store lookup (read-only)
Route::prefix('/stores')->group(function (): void {
    Route::get('/search', [\App\Http\Controllers\Api\Support\SupportStoreLookupController::class, 'search']);
    Route::get('/{store}', [\App\Http\Controllers\Api\Support\SupportStoreLookupController::class, 'show']);
    Route::get('/{store}/activity', [\App\Http\Controllers\Api\Support\SupportStoreLookupController::class, 'activity']);
});

// Support impersonation (governed, audited)
Route::prefix('/impersonation')->group(function (): void {
    Route::post('/request', [\App\Http\Controllers\Api\Support\SupportImpersonationController::class, 'request']);
    Route::get('/active', [\App\Http\Controllers\Api\Support\SupportImpersonationController::class, 'active']);
    Route::delete('/terminate', [\App\Http\Controllers\Api\Support\SupportImpersonationController::class, 'terminate']);
});
