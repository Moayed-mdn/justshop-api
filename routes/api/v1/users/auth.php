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
Route::middleware(['auth:sanctum', 'identity.route:merchant_users,merchant,enforce'])->get('/user', function (\Illuminate\Http\Request $request) {
    return new \App\Http\Resources\UserResource($request->user());
});