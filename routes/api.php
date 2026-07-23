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

// Platform Authentication Routes (without platform.authority middleware)

Route::prefix('/v1/platform')
    ->middleware([
        'web',
        'auth:sanctum',
        'identity.route:platform,platform,enforce',
        'platform.context',
    ])
    ->group(function (): void {
        require 'api/v1/platform/auth.php';
    });

// Other Platform Routes (with platform.authority middleware)
Route::prefix('/v1/platform')
    ->middleware([
        'web',
        'auth:sanctum',
        'identity.route:platform,platform,enforce',
        'platform.context',
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
Route::get('/v1/me', [\App\Http\Controllers\Api\Merchant\AuthController::class, 'bootstrap'])
    ->middleware([
        //'web',
        'auth:sanctum',
        'identity.route:merchant_users,merchant,enforce',
        'api.deprecated',
    ])
    ->name('merchant.me.legacy');

Route::prefix('/v1/stores')
    ->middleware([
        //'web',
        'api.deprecated',
    ])
    ->group(function (): void {
        Route::post('/', [\App\Http\Controllers\Api\Merchant\StoreController::class, 'create'])
            ->middleware(['auth:sanctum', 'identity.route:merchant_users,merchant,enforce'])
            ->name('merchant.stores.legacy.create');

        Route::get('/{store}/provisioning-status', [\App\Http\Controllers\Api\Merchant\ProvisioningStatusController::class, '__invoke'])
            ->middleware(['auth:sanctum', 'identity.route:merchant_users,merchant,enforce'])
            ->name('merchant.stores.legacy.provisioning-status');

        Route::middleware(['auth:sanctum', 'identity.route:merchant_admin,merchant,enforce', 'store.context'])
            ->group(function (): void {
                Route::get('/{store}', [\App\Http\Controllers\Api\Merchant\StoreController::class, 'show'])
                    ->name('merchant.stores.legacy.show');

                Route::put('/{store}', [\App\Http\Controllers\Api\Merchant\StoreController::class, 'update'])
                    ->name('merchant.stores.legacy.update');
            });
    });

Route::prefix('/v1')
    ->middleware([
        //'web',
        'api.deprecated',
    ])
    ->group(function (): void {
        Route::get('/store-slug/check', [\App\Http\Controllers\Api\Merchant\StoreSlugController::class, '__invoke'])
            ->middleware(['auth:sanctum', 'identity.route:merchant_users,merchant,enforce'])
            ->name('merchant.stores.legacy.slug-check');
    });

Route::prefix('/v1/users')
    ->middleware([
        //'web',
        'identity.route:merchant_users,merchant,observe',
        'api.deprecated',
    ])
    ->group(function (): void {
        Route::get('/bootstrap', [\App\Http\Controllers\Api\Merchant\AuthController::class, 'bootstrap'])
            ->middleware(['auth:sanctum'])
            ->name('merchant.users.legacy.bootstrap');

        Route::prefix('/auth')
            ->name('merchant.users.legacy.auth.')
            ->group(function (): void {
                Route::get('/bootstrap', [\App\Http\Controllers\Api\Merchant\AuthController::class, 'bootstrap'])
                    ->middleware(['auth:sanctum'])
                    ->name('bootstrap');

                Route::get('/me', [\App\Http\Controllers\Api\Merchant\AuthController::class, 'me'])
                    ->middleware(['auth:sanctum'])
                    ->name('me');

                Route::post('/register', [\App\Http\Controllers\Api\Merchant\AuthController::class, 'register'])
                    ->name('register');

                Route::post('/login', [\App\Http\Controllers\Api\Merchant\AuthController::class, 'login'])
                    ->middleware('throttle:login')
                    ->name('login');

                // Keep the legacy Google OAuth callback URL working for existing provider config.
                Route::get('/google/redirect', [\App\Http\Controllers\Api\Merchant\SocialAuthController::class, 'redirect'])
                    ->name('google.redirect');

                Route::get('/google/callback', [\App\Http\Controllers\Api\Merchant\SocialAuthController::class, 'callback'])
                    ->name('google.callback');

                Route::post('/logout', [\App\Http\Controllers\Api\Merchant\AuthController::class, 'logout'])
                    ->middleware(['auth:sanctum'])
                    ->name('logout');

                Route::post('/password/validate-token', [\App\Http\Controllers\Api\Merchant\PasswordResetController::class, 'validateToken'])
                    ->name('password.validate-token');

                Route::patch('/active-store', [\App\Http\Controllers\Api\Merchant\AuthController::class, 'updateActiveStore'])
                    ->middleware(['auth:sanctum', 'verified'])
                    ->name('active-store.update');
            });

        Route::prefix('/sessions')
            ->middleware(['auth:sanctum'])
            ->name('merchant.users.legacy.sessions.')
            ->group(function (): void {
                Route::get('/', [\App\Http\Controllers\Api\Merchant\SessionController::class, 'index'])
                    ->name('index');

                Route::delete('/', [\App\Http\Controllers\Api\Merchant\SessionController::class, 'destroyAll'])
                    ->name('destroy-all');

                Route::delete('/{id}', [\App\Http\Controllers\Api\Merchant\SessionController::class, 'destroy'])
                    ->name('destroy');
            });
    });

Route::prefix('/v1/merchant')
    ->middleware([
        //'web',
        'identity.route:merchant_users,merchant,enforce',
    ])
    ->group(function (): void {
        // Canonical bootstrap endpoint
        Route::get('/me', [\App\Http\Controllers\Api\Merchant\AuthController::class, 'bootstrap'])
            ->middleware(['auth:sanctum','identity.route:merchant_users,merchant,enforce'])
            ->name('merchant.me');

        require 'api/v1/merchant/auth.php';
        require 'api/v1/merchant/profile.php';
        require 'api/v1/merchant/category.php';
        require 'api/v1/merchant/admin.php';
        require 'api/v1/merchant/stores.php';
        require 'api/v1/merchant/theme.php';
        require 'api/v1/merchant/billing.php'; // Phase 3: Subscription & Billing
        require 'api/v1/merchant/shipping.php'; // Shipping Management
    });

// ── 3. STOREFRONT CONTEXT ────────────────────────────────────────────────
// Public ecommerce APIs (Customers and guests browsing stores).
Route::prefix('/v1/storefront/runtime')
    ->middleware([
        //'web',
    ])
    ->group(function (): void {
        require 'api/v1/storefront/runtime.php';
    });
Route::prefix('/v1/storefront')
    ->middleware([
        //'web',
        'identity.route:storefront_commerce,customer,enforce',
    ])
    ->group(function (): void {
        // Additional theme and navigation endpoints
        Route::prefix('theme')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Api\Storefront\StorefrontThemeController::class, 'show']);
        });

        Route::prefix('navigation')->group(function (): void {
            Route::get('/{handle}', [\App\Http\Controllers\Api\Storefront\StorefrontNavigationController::class, 'show']);
        });

        require 'api/v1/storefront/products.php';
        require 'api/v1/storefront/cart.php';
        require 'api/v1/storefront/orders.php';
        require 'api/v1/storefront/addresses.php';
        require 'api/v1/storefront/checkout.php';
        require 'api/v1/storefront/search.php';
        require 'api/v1/storefront/homepage.php';
    });

Route::prefix('/v1/storefront/account')
    ->middleware([
        //'web',
        'api.deprecated',
        'identity.route:customer_account,customer,enforce',
    ])
    ->group(function (): void {
        Route::get('/bootstrap', [\App\Http\Controllers\Api\Storefront\Account\StorefrontAccountController::class, 'bootstrap'])
            ->middleware(['auth:sanctum'])
            ->name('customer.storefront.legacy.bootstrap');

        Route::post('/logout', [\App\Http\Controllers\Api\Storefront\Account\StorefrontAccountController::class, 'logout'])
            ->middleware(['auth:sanctum'])
            ->name('customer.storefront.legacy.logout');
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
        //'web',
        'auth:sanctum',
        'identity.route:support,platform,enforce',
        'platform.context',
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
