# Frontend Route Migration Guide

This guide provides the necessary information for frontend teams to migrate from legacy API routes to the new context-based routing architecture.

## Overview

The API has been refactored to use explicit **Application Contexts**. This change improves security and prepares the platform for future scalability.

### Canonical Contexts
- `/api/v1/platform/*` - SaaS Admin
- `/api/v1/merchant/*` - Store Owner/Staff
- `/api/v1/storefront/*` - Public Shopper
- `/api/v1/customer/*` - Registered Shopper Account
- `/api/v1/support/*` - Internal Support
- `/api/v1/public/*` - Marketing/SEO/Docs

## Mapping Table

| Legacy Path | New Canonical Path | Context |
| :--- | :--- | :--- |
| `/v1/me` | `/v1/merchant/me` | Merchant |
| `/v1/users/auth/*` | `/v1/merchant/auth/*` | Merchant |
| `/v1/users/profile/*` | `/v1/merchant/profile/*` | Merchant |
| `/v1/admin/stores/{store}/*` | `/v1/merchant/stores/{store}/*` | Merchant |
| `/v1/stores/{store}/*` | `/v1/storefront/stores/{store}/*` | Storefront |
| `/v1/storefront/account/*` | `/v1/customer/auth/*` | Customer |
| `/v1/admin/leads` | `/v1/platform/leads` | Platform |
| `/v1/admin/cms/*` | `/v1/platform/cms/*` | Platform |

## Migration Timeline

1.  **Phase 1: Dual Support (Current)**
    - Both legacy and canonical routes are active.
    - Legacy routes return an `X-API-Deprecated: true` header.
    - Legacy routes return an `X-API-Suggested-New-Route` header with the target path.
2.  **Phase 2: Warning (Scheduled: 2026-09-01)**
    - Legacy routes will begin returning a `410 Gone` or `301 Moved Permanently` in staging environments.
3.  **Phase 3: Removal (Scheduled: 2027-01-01)**
    - Legacy routes will be fully removed.

## How to Migrate

### 1. Update Base URLs
If your frontend application is specific to a context (e.g., the Merchant Dashboard), update your API client configuration to include the context prefix:
```typescript
// Old
const API_BASE = 'https://api.laratenant.com/v1';

// New
const API_BASE = 'https://api.laratenant.com/v1/merchant';
```

### 2. Check Deprecation Headers
Monitor your network requests for the `X-API-Deprecated` header. If found, use the `X-API-Suggested-New-Route` header to identify the correct endpoint.

### 3. Update Route Names (if using Ziggy/Laravel Router)
If you use named routes in your frontend, update them to the new naming convention:
- `v1.users.auth.login` -> `merchant.auth.login`
- `v1.stores.product.index` -> `storefront.products.index`

## Support
If you encounter any issues during migration, please contact the Backend Architecture team in the `#api-refactor` Slack channel.
