<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\SocialAuthController;

Route::name('v1.users.auth.')
    ->prefix('/v1/users/auth')
    ->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');

        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
        Route::post('/password/forgot', [PasswordResetController::class, 'sendResetLink'])->name('password.forgot');
        Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail'])->name('email.resend')->middleware('throttle:verification-resend');
        Route::get('/google/redirect', [SocialAuthController::class, 'redirect']);
        Route::get('/google/callback', [SocialAuthController::class, 'callback']);
        Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum')->name('me');
        Route::get('/bootstrap', [AuthController::class, 'bootstrap'])->middleware('auth:sanctum')->name('bootstrap');
        Route::patch('/active-store', [AuthController::class, 'updateActiveStore'])->middleware(['auth:sanctum', 'verified'])->name('active-store.update');
    });

// Authenticated user endpoint (for Next.js SPA)
Route::middleware('auth:sanctum')->get('/user', function () {
    return new \App\Http\Resources\UserResource(auth()->user());
});

// Debug auth route (temporary - remove before production)
Route::middleware('auth:sanctum')->get('/debug-auth', function () {
    return response()->json([
        'user' => auth()->user(),
        'session_id' => session()->getId(),
        'cookies' => request()->cookies->all(),
    ]);
});