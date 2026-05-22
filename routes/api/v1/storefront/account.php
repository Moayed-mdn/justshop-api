<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Storefront\Account\StorefrontAccountController;
use Illuminate\Support\Facades\Route;

Route::prefix('/v1/storefront/account')
    ->middleware('identity.route:customer_account,customer,enforce')
    ->controller(StorefrontAccountController::class)
    ->group(function (): void {
        Route::post('/register', 'register')->name('v1.storefront.account.register');
        Route::post('/login', 'login')->name('v1.storefront.account.login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/logout', 'logout')->name('v1.storefront.account.logout');
            Route::get('/me', 'me')->name('v1.storefront.account.me');
            Route::get('/bootstrap', 'bootstrap')->name('v1.storefront.account.bootstrap');
        });
    });
