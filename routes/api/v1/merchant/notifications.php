<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Merchant\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'identity.route:merchant_users,merchant,enforce'])
    ->prefix('notifications')
    ->name('merchant.notifications.')
    ->controller(NotificationController::class)
    ->group(function (): void {
        Route::post('/device-tokens', 'registerDeviceToken')->name('device-tokens.store');
        Route::delete('/device-tokens/{token}', 'removeDeviceToken')->name('device-tokens.destroy');

        Route::get('/', 'listNotifications')->name('index');
        Route::get('/unread-count', 'unreadCount')->name('unread-count');
        Route::patch('/read-all', 'markAllAsRead')->name('read-all');
        Route::patch('/{notification}/read', 'markAsRead')->name('read');
    });
