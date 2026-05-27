# Legacy URL Migration Guide (Updated 2026-05-25)

This document tracks the status of hardcoded URL migration across the codebase.

## Status Overview
- **Notifications**: 100% Migrated (Using `FrontendUrlBuilder` and canonical route names).
- **Mailables**: 100% Migrated.
- **Service Logic**: 100% Migrated (Refactored `SocialAuthService`, `CheckoutService`).
- **Tests**: 100% Migrated (All security and feature tests updated to canonical routes).

## Completed Migrations (Phase 3)

### Frontend URL Generation
- **Abstraction**: Introduced `App\Support\System\FrontendUrlBuilder`.
- **Status**: Used in all Notifications and Redirect services.

### Email Verification
- **Old**: Manual string concatenation with `config('app.frontend_url')`.
- **New**: `FrontendUrlBuilder::buildSigned()`.
- **Status**: Fixed in `app/Notifications/VerifyEmail.php`.

### Social Auth Redirects
- **Old**: Hardcoded `/auth/google/callback` strings.
- **New**: `FrontendUrlBuilder::build('/auth/google/callback')`.
- **Status**: Fixed in `app/Services/SocialAuthService.php`.

### Checkout URLs
- **Old**: Hardcoded success/cancel paths.
- **New**: `FrontendUrlBuilder::build('/checkout/success', ...)`.
- **Status**: Fixed in `app/Services/CheckoutService.php`.

## Remaining Cleanup
1.  **Documentation Reference**: `docs/reference/routes.md` (if it exists) should be updated to reflect context prefixes.
2.  **Telemetry Review**: Monitor `HandleDeprecatedRoute` logs for any remaining frontend calls to `/v1/admin` or `/v1/users`.

## Future Audits
Run the following command periodically to find any newly introduced hardcoded legacy URLs:
```bash
grep -rE "/api/v1/(admin|users|stores|storefront)" . --exclude-dir={vendor,docs,node_modules,storage}
```
