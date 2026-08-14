<?php

use App\Http\Controllers\Api\Platform\Billing\PlatformPlanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Platform Billing - Plan Management Routes
|--------------------------------------------------------------------------
|
| Platform Super Admin routes for managing subscription plans.
| All routes require platform.authority:platform_admin middleware.
|
*/

Route::prefix('billing')->group(function () {
    // List and show plans
    Route::get('/plans', [PlatformPlanController::class, 'index']);
    Route::get('/plans/{plan}', [PlatformPlanController::class, 'show']);

    // Create, update, archive, delete plans
    Route::post('/plans', [PlatformPlanController::class, 'store']);
    Route::put('/plans/{plan}', [PlatformPlanController::class, 'update']);
    Route::patch('/plans/{plan}/archive', [PlatformPlanController::class, 'archive']);
    Route::delete('/plans/{plan}', [PlatformPlanController::class, 'destroy']);

    // Price management
    Route::post('/plans/{plan}/prices', [PlatformPlanController::class, 'storePrice']);
    Route::patch('/plans/{plan}/prices/{price}/archive', [PlatformPlanController::class, 'archivePrice']);

    // Subscriber migration
    Route::post('/plans/migrate-subscribers', [PlatformPlanController::class, 'migrateSubscribers']);
});
