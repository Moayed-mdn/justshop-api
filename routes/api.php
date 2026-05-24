<?php

use App\Http\Controllers\Api\Auth\Preparation\CsrfOwnershipPreparationController;
use Illuminate\Support\Facades\Route;

Route::middleware('identity.route:merchant_users,merchant,enforce')->group(function (): void {
    // Canonical bootstrap endpoint
    Route::get('/v1/me', [\App\Http\Controllers\Api\Auth\AuthController::class, 'bootstrap'])
        ->middleware('auth:sanctum')
        ->name('v1.me');

    // Auth (merchant-authoritative; no store context)
    require 'api/v1/users/auth.php';

    // Merchant-owned legacy /users surface (no store context)
    require 'api/v1/users/category.php';
    require 'api/v1/users/profile.php';

    // Guest checkout status (no auth, no store context)
    Route::prefix('/v1/users/checkout')
        ->controller(\App\Http\Controllers\Api\Payment\CheckoutController::class)
        ->withoutMiddleware(['auth:sanctum'])
        ->group(function (): void {
            Route::get('/status/{sessionId}', 'status');
        });
});

// Public (no store context)
Route::middleware('identity.route:public,customer,observe')->group(function (): void {
    require 'api/v1/public/cms.php';
    require 'api/v1/public/leads.php';
});

// Stripe webhook (no store context)
Route::middleware('identity.route:shared_transitional,merchant,observe')->group(function (): void {
    require 'api/v1/stripe/webhook.php';
});

// Store-scoped routes
Route::middleware('identity.route:storefront_commerce,customer,observe')->group(function (): void {
    require 'api/v1/stores/cart.php';
    require 'api/v1/stores/orders.php';
    require 'api/v1/stores/products.php';
    require 'api/v1/stores/addresses.php';
    require 'api/v1/stores/checkout.php';
    require 'api/v1/stores/search.php';
    require 'api/v1/stores/homepage.php';
});

// Store management routes (outside {store} group - POST has no store context yet)
Route::middleware('identity.route:merchant_admin,merchant,enforce')->group(function (): void {
    require 'api/v1/stores/store-management.php';
});

// Wave 6: Platform Authority Domain (SUPER_ADMIN only)
// Platform authority is INDEPENDENT from merchant authority.
// Platform routes MUST NOT inherit merchant authority implicitly.
Route::prefix('/v1/platform')
    ->middleware([
        'auth:sanctum',
        'identity.route:platform,platform,enforce',
        'platform.authority:platform_admin',
    ])
    ->group(function (): void {
        require 'api/v1/platform/platform.php';
    });

// Wave 6: Support Authority Domain (SUPPORT_AGENT, SUPER_ADMIN)
// Support authority is a SUBSET of platform authority.
// Support actors have LIMITED platform access.
Route::prefix('/v1/support')
    ->middleware([
        'auth:sanctum',
        'identity.route:support,platform,enforce',
        'support.authority',
    ])
    ->group(function (): void {
        require 'api/v1/support/support.php';
    });

// Legacy Platform Routes (TRANSITIONAL - to be migrated to /v1/platform)
// These routes still use implicit platform authority via identity.route middleware.
// Wave 6 Goal: Migrate these to explicit platform.authority middleware.
Route::middleware('identity.route:platform,platform,enforce')->group(function (): void {
    require 'api/v1/admin/admin.php';
    require 'api/v1/admin/leads.php';
    require 'api/v1/admin/cms/blog.php';
    require 'api/v1/admin/cms/marketing-pages.php';
    require 'api/v1/admin/cms/documentation.php';
});

// Additive customer account namespace
require 'api/v1/storefront/account.php';


Route::get('/sanctum/csrf-cookie', [CsrfOwnershipPreparationController::class, 'show'])
    ->middleware(['web', 'identity.route:shared_transitional,merchant,observe']);
