<?php

use App\Http\Controllers\Api\Merchant\AuthController;
use App\Http\Controllers\Api\Merchant\PasswordResetController;
use App\Http\Controllers\Api\Merchant\SessionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Merchant\SocialAuthController;

Route::name('merchant.auth.')
    ->prefix('auth')
    ->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:login');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');

        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
        Route::post('/password/forgot', [PasswordResetController::class, 'sendResetLink'])->name('password.forgot');
        Route::post('/password/validate-token', [\App\Http\Controllers\Api\Merchant\PasswordResetController::class, 'validateToken'])
            ->name('password.validate-token');
        Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.reset');
        Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail'])->name('email.resend')->middleware('throttle:verification-resend');
        Route::get('/email/status', [\App\Http\Controllers\Api\Merchant\EmailVerificationController::class, 'status'])
            ->middleware('auth:sanctum')
            ->name('email.status');
        Route::get('/google/redirect', [SocialAuthController::class, 'redirect']);
        Route::get('/google/callback', [SocialAuthController::class, 'callback']);

        Route::patch('/active-store', [AuthController::class, 'updateActiveStore'])->middleware(['auth:sanctum', 'verified'])->name('active-store.update');
    });

// Session management — requires authentication only (no onboarding gate, no store context)
Route::middleware('auth:sanctum')
    ->prefix('sessions')
    ->name('merchant.sessions.')
    ->group(function () {
        // GET /api/v1/merchant/sessions — list all active sessions for the current user
        Route::get('/', [SessionController::class, 'index'])->name('index');

        // DELETE /api/v1/merchant/sessions — revoke all sessions except the current one
        // Requires password confirmation (enforced in LogoutAllDevicesRequest)
        Route::delete('/', [SessionController::class, 'destroyAll'])->name('destroy-all');
        Route::delete('/{id}', [SessionController::class, 'destroy'])->name('destroy');
    });

// Authenticated user endpoint (for Next.js SPA)
Route::middleware(['auth:sanctum', 'identity.route:merchant_users,merchant,enforce'])
    ->get('/user', function (\Illuminate\Http\Request $request) {
        return new \App\Http\Resources\UserResource($request->user());
})->name('merchant.user');
