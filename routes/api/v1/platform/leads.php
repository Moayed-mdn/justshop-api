<?php

use App\Http\Controllers\Api\Platform\AdminLeadController;
use Illuminate\Support\Facades\Route;

Route::prefix('leads')
    ->name('platform.leads.')
    ->controller(AdminLeadController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/{lead}', 'show')->name('show');
        Route::patch('/{lead}/status', 'updateStatus')->name('status');
        Route::delete('/{lead}', 'destroy')->name('destroy');
    });
