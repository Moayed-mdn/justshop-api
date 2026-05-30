<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Storefront\Account\StorefrontAccountController;
use Illuminate\Support\Facades\Route;

Route::name('customer.')
    ->controller(StorefrontAccountController::class)
    ->group(function (): void {
        Route::prefix('auth')
            ->name('auth.')
            ->group(function (): void {
            Route::post('/register', 'register')->name('register');
            Route::post('/login', 'login')->name('login')->middleware('throttle:customer-login');
            Route::post('/logout', 'logout')->middleware('auth:sanctum')->name('logout');

            Route::get('/email/verify/{id}/{hash}', 'verifyEmail')->name('verification.verify');
            Route::post('/email/resend', 'resendVerificationEmail')->name('email.resend')->middleware('throttle:verification-resend');

            Route::prefix('password')
                ->name('password.')
                ->group(function (): void {
                Route::post('/forgot', 'forgotPassword')->name('forgot');
                Route::post('/reset', 'resetPassword')->name('reset');
            });
        });

        Route::middleware(['auth:sanctum', 'identity.route:customer_account,customer,enforce'])->group(function (): void {
            Route::get('/me', 'me')->name('me');
            Route::get('/bootstrap', 'bootstrap')->name('bootstrap');
            Route::put('/me/info', 'updateInfo')->name('me.update-info');
            Route::put('/me/password', 'updatePassword')->name('me.update-password');
            Route::post('/me/avatar', 'updateAvatar')->name('me.update-avatar');
            Route::delete('/me', 'destroy')->name('me.destroy');
        });
    });
