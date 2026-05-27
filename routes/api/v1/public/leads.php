<?php

use App\Http\Controllers\Api\Platform\LeadController;
use Illuminate\Support\Facades\Route;

Route::prefix('leads')
    ->middleware('throttle:lead-submissions')
    ->controller(LeadController::class)
    ->group(function (): void {
        Route::post('/contact', 'contact')->name('public.leads.contact');
        Route::post('/demo', 'demo')->name('public.leads.demo');
        Route::post('/enterprise', 'enterprise')->name('public.leads.enterprise');
    });
