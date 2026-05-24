<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\SessionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\SocialAuthController;

Route::name('v1.users.auth.')
    ->prefix('/v1/users/auth')
    ->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:login');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
        Route::get('/bootstrap', [AuthController::class, 'bootstrap'])->middleware('auth:sanctum')->name('bootstrap');

        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
        Route::post('/password/forgot', [PasswordResetController::class, 'sendResetLink'])->name('password.forgot');
        Route::post('/password/validate-token', [\App\Http\Controllers\Api\Auth\PasswordResetController::class, 'validateToken'])
            ->name('password.validate-token');
        Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.reset');
        Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail'])->name('email.resend')->middleware('throttle:verification-resend');
        Route::get('/email/status', [\App\Http\Controllers\Api\V1\EmailVerificationController::class, 'status'])
            ->middleware('auth:sanctum')
            ->name('email.status');
        Route::get('/google/redirect', [SocialAuthController::class, 'redirect']);
        Route::get('/google/callback', [SocialAuthController::class, 'callback']);
        Route::get('/me', [AuthController::class, 'bootstrap'])->middleware('auth:sanctum')->name('me');

        Route::patch('/active-store', [AuthController::class, 'updateActiveStore'])->middleware(['auth:sanctum', 'verified'])->name('active-store.update');
    });

Route::middleware('auth:sanctum')
    ->get('/v1/users/bootstrap', [AuthController::class, 'bootstrap'])
    ->name('v1.users.bootstrap');

// Session management — requires authentication only (no onboarding gate, no store context)
Route::middleware('auth:sanctum')
    ->prefix('/v1/users/sessions')
    ->name('v1.users.sessions.')
    ->group(function () {
        // GET /api/v1/users/sessions — list all active sessions for the current user
        Route::get('/', [SessionController::class, 'index'])->name('index');

        // DELETE /api/v1/users/sessions — revoke all sessions except the current one
        // Requires password confirmation (enforced in LogoutAllDevicesRequest)
        Route::delete('/', [SessionController::class, 'destroyAll'])->name('destroy-all');
        Route::delete('/{id}', [SessionController::class, 'destroy'])->name('destroy');
    });

// Authenticated user endpoint (for Next.js SPA)
Route::middleware(['auth:sanctum', 'identity.route:merchant_users,merchant,enforce'])->get('/user', function (\Illuminate\Http\Request $request) {
    return new \App\Http\Resources\UserResource($request->user());
});
