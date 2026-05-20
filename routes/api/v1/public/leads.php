<?php

use App\Http\Controllers\Api\Lead\LeadController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/leads')
    ->middleware('throttle:lead-submissions')
    ->controller(LeadController::class)
    ->group(function (): void {
        Route::post('/contact', 'contact');
        Route::post('/demo', 'demo');
        Route::post('/enterprise', 'enterprise');
    });
