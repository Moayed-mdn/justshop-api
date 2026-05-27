# Legacy Controller Cleanup Report

This report identifies legacy controllers that are candidates for removal or require deprecation markers.

## Removal Readiness Summary

| Legacy Controller | Replacement | Status | Blockers |
| :--- | :--- | :--- | :--- |
| `Api/Auth/AuthController` | `Api/Merchant/AuthController` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/Auth/SessionController` | `Api/Merchant/SessionController` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/Auth/PasswordResetController` | `Api/Merchant/PasswordResetController` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/Auth/ProfileController` | `Api/Merchant/ProfileController` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/Auth/SocialAuthController` | `Api/Merchant/SocialAuthController` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/Auth/UserController` | - | **REMOVED** | None |
| `Api/Admin/*` | `Api/Merchant/*` or `Api/Platform/*` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/Product/ProductController` | `Api/Storefront/ProductController` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/Category/CategoryController` | `Api/Merchant/CategoryController` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/Search/SearchController` | `Api/Storefront/SearchController` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/Payment/CheckoutController` | `Api/Storefront/CheckoutController` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/Address/AddressController` | `Api/Storefront/AddressController` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/Cart/CartController` | `Api/Storefront/CartController` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/Order/OrderController` | `Api/Storefront/OrderController` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/Homepage/HomePageController` | `Api/Storefront/HomePageController` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/V1/StoreSlugController` | `Api/Merchant/StoreSlugController` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/V1/EmailVerificationController` | `Api/Merchant/EmailVerificationController` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/V1/ProvisioningStatusController` | `Api/Merchant/ProvisioningStatusController` | **REMOVED** | Legacy route aliases in api.php |
| `Api/Store/StoreController` | `Api/Merchant/StoreController` | **REMOVED** | Legacy route aliases in `api.php` |
| `Api/Payment/StripeWebhookController` | `Api/Shared/Payment/StripeWebhookController` | **MOVED** | None |
| `Api/Auth/Preparation/CsrfOwnershipPreparationController` | `Api/Shared/Auth/Preparation/CsrfOwnershipPreparationController` | **MOVED** | None |
| `Api/Lead/LeadController` | `Api/Platform/LeadController` | **REMOVED** | Referenced in `routes/api/v1/public/leads.php` |

## Removal Readiness Summary (Updated 2026-05-25)
All legacy controller files have been physically removed from the filesystem. Legacy routes in `api.php` now point directly to their canonical replacements in the `Merchant`, `Platform`, or `Storefront` namespaces.

## Safe Deletion Candidates
The following controllers have been removed:
- `app/Http/Controllers/Api/Auth/*`
- `app/Http/Controllers/Api/Admin/*`
- `app/Http/Controllers/Api/V1/*`
- `app/Http/Controllers/Api/Product/*`
- `app/Http/Controllers/Api/Order/*`
- `app/Http/Controllers/Api/Cart/*`
- `app/Http/Controllers/Api/Address/*`
- `app/Http/Controllers/Api/Search/*`
- `app/Http/Controllers/Api/Homepage/*`

## Remaining Blockers
1.  **Legacy Route Aliases**: `routes/api.php` still contains the `LEGACY COMPATIBILITY` section. These should be removed in Phase 4.
2.  **Tests**: Some tests still hit legacy endpoints (e.g., `/api/v1/admin/*`).
3.  **Notifications**: Notification link generation has been audited and refactored to use canonical routes.

## Phased Removal Plan
- **Phase 3 (Current)**: Completed physical removal of legacy controller files. Refactored notification links. Updated architecture docs.
- **Phase 4**: Remove legacy route aliases from `routes/api.php` once telemetry shows zero usage.
- **Phase 5**: Final system validation.
