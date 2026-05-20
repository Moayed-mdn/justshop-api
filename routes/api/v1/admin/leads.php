<?php

use App\Http\Controllers\Api\Admin\Lead\AdminLeadController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin/leads')
    ->middleware(['auth:sanctum'])
    ->controller(AdminLeadController::class)
    ->group(function (): void {
        Route::get('/', 'index');
        Route::get('/{lead}', 'show');
        Route::patch('/{lead}/status', 'updateStatus');
        Route::delete('/{lead}', 'destroy');
    });
