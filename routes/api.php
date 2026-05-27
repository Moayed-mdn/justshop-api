<?php

use App\Http\Controllers\Api\Shared\Auth\Preparation\CsrfOwnershipPreparationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Context-Based Architecture (v1)
|--------------------------------------------------------------------------
|
| The API is organized into explicit application contexts based on the actor
| and their intent. Each context has its own middleware, authentication,
| and identity boundaries.
|
*/

// ── 1. PLATFORM CONTEXT ──────────────────────────────────────────────────
// Internal SaaS operator tooling (SUPER_ADMIN only).
Route::prefix('/v1/platform')
    ->middleware([
        'web',
        'auth:sanctum',
        'identity.route:platform,platform,enforce',
        'platform.authority:platform_admin',
    ])
    ->group(function (): void {
        require 'api/v1/platform/platform.php';
        require 'api/v1/platform/leads.php';
        require 'api/v1/platform/cms/blog.php';
        require 'api/v1/platform/cms/marketing-pages.php';
        require 'api/v1/platform/cms/documentation.php';
    });

// ── 2. MERCHANT CONTEXT ──────────────────────────────────────────────────
// Tenant/store administration (Store owners and staff).
Route::prefix('/v1/merchant')
    ->middleware([
        'web',
        'identity.route:merchant_users,merchant,enforce',
    ])
    ->group(function (): void {
        // Canonical bootstrap endpoint
        Route::get('/me', [\App\Http\Controllers\Api\Merchant\AuthController::class, 'bootstrap'])
            ->middleware(['auth:sanctum','identity.route:merchant_users,merchant,enforce']) // disabled temporarily, use 'identity.route:merchant_users,merchant,enforce'
            ->name('merchant.me');

        require 'api/v1/merchant/auth.php';
        require 'api/v1/merchant/profile.php';
        require 'api/v1/merchant/category.php';
        require 'api/v1/merchant/admin.php';
        require 'api/v1/merchant/stores.php';
    });

// ── 3. STOREFRONT CONTEXT ────────────────────────────────────────────────
// Public ecommerce APIs (Customers and guests browsing stores).
Route::prefix('/v1/storefront')
    ->middleware([
        'web',
        'identity.route:storefront_commerce,customer,enforce',
    ])
    ->group(function (): void {
        require 'api/v1/storefront/products.php';
        require 'api/v1/storefront/cart.php';
        require 'api/v1/storefront/orders.php';
        require 'api/v1/storefront/addresses.php';
        require 'api/v1/storefront/checkout.php';
        require 'api/v1/storefront/search.php';
        require 'api/v1/storefront/homepage.php';
    });

// ── 4. CUSTOMER CONTEXT ──────────────────────────────────────────────────
// Customer identity and account management.
Route::prefix('/v1/customer')
    ->middleware([
        'web',
        'identity.route:customer_account,customer,enforce',
    ])
    ->group(function (): void {
        require 'api/v1/customer/account.php';
    });

// ── 5. SUPPORT CONTEXT ───────────────────────────────────────────────────
// Internal support operations (SUPPORT_AGENT, SUPER_ADMIN).
Route::prefix('/v1/support')
    ->middleware([
        'web',
        'auth:sanctum',
        'identity.route:support,platform,enforce',
        'support.authority',
    ])
    ->group(function (): void {
        require 'api/v1/support/support.php';
    });

// ── 6. PUBLIC CONTEXT ────────────────────────────────────────────────────
// Marketing site, CMS, docs, and SEO (Unauthenticated public access).
Route::prefix('/v1/public')
    ->group(function (): void {
        require 'api/v1/public/cms.php';
        require 'api/v1/public/leads.php';
    });

// ── 7. SYSTEM & SHARED ───────────────────────────────────────────────────
// Routes that cross context boundaries or serve infrastructure needs.

// Stripe webhooks
Route::middleware('identity.route:shared_transitional,merchant,observe')
    ->group(function (): void {
        require 'api/v1/stripe/webhook.php';
    });

// CSRF Ownership Preparation
Route::get('/sanctum/csrf-cookie', [CsrfOwnershipPreparationController::class, 'show'])
    ->middleware(['web', 'identity.route:shared_transitional,merchant,observe']);

