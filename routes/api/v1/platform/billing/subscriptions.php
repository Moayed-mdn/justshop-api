<?php

use App\Http\Controllers\Api\Platform\Billing\PlatformSubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Platform Billing - Subscription Views
|--------------------------------------------------------------------------
|
| Read-only platform-admin views over merchant subscriptions. Sits
| alongside billing/plans.php under the same 'billing' prefix and
| platform_admin authority (applied by the enclosing route group in
| routes/api.php) — no per-action authorize() calls needed here, same
| convention as PlatformPlanController.
|
*/

Route::prefix('billing')->group(function () {
    Route::get('/subscriptions', [PlatformSubscriptionController::class, 'index']);
    Route::get('/subscriptions/{subscription}', [PlatformSubscriptionController::class, 'show']);
});
