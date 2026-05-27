<?php

use App\Http\Controllers\Api\Merchant\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'identity.route:merchant_users,merchant,enforce'])
    ->prefix('profile')
    ->name('merchant.profile.')
    ->controller(ProfileController::class)
    ->group(function () {
        Route::get('/', 'show')->name('show');
        Route::put('/info', 'updateInfo')->name('update-info');
        Route::put('/password', 'updatePassword')->name('update-password');
        Route::post('/avatar', 'updateAvatar')->name('update-avatar');
        Route::delete('/', 'destroy')->name('destroy');
    });
