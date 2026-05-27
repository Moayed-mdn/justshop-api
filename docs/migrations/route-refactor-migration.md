# Route Refactor Migration Guide

This document outlines the changes made during the API architecture refactor and the path for migrating existing consumers to the new context-based structure.

## Why Refactor?
The previous routing structure mixed actors, resources, and audiences inconsistently. The new architecture organizes the API into explicit **Application Contexts**, improving security, clarity, and scalability.

## Changes Overview

### Prefix Changes
| Old Prefix | New Prefix | Context |
| :--- | :--- | :--- |
| `/v1/admin/leads` | `/v1/platform/leads` | Platform |
| `/v1/admin/cms` | `/v1/platform/cms` | Platform |
| `/v1/users/auth` | `/v1/merchant/auth` | Merchant |
| `/v1/users/profile` | `/v1/merchant/profile` | Merchant |
| `/v1/admin/stores/{store}` | `/v1/merchant/stores/{store}` | Merchant |
| `/v1/stores/{store}` | `/v1/storefront/stores/{store}` | Storefront |
| `/v1/storefront/account` | `/v1/customer` | Customer |

### Route Name Changes
Route names have been refactored to match their contexts:
- `v1.users.auth.*` -> `merchant.auth.*`
- `v1.stores.product.*` -> `storefront.products.*`
- `v1.storefront.account.*` -> `customer.*`
- `v1.admin.*` -> `platform.*` or `merchant.*`

## Backward Compatibility
To prevent breaking existing consumers, the legacy routes are preserved in the `routes/api.php` file under the `LEGACY COMPATIBILITY` section. These routes:
1. Alias the new context-based route files.
2. Maintain the old URL structure.
3. Will be deprecated and removed in v2.

## Migration Steps for Consumers

### Frontend Dashboards (Merchant/Platform)
1. Update API base URL or path prefixes to include `/merchant` or `/platform`.
2. Update route name references if using a routing library that depends on them.

### Storefront Themes / SPAs
1. Change all calls to `/v1/stores/{store}/...` to `/v1/storefront/stores/{store}/...`.
2. Update customer account calls from `/v1/storefront/account/...` to `/v1/customer/...`.

## Examples

### Merchant Product List
- **Before**: `GET /api/v1/admin/stores/my-store/products`
- **After**: `GET /api/v1/merchant/stores/my-store/products`

### Storefront Product Show
- **Before**: `GET /api/v1/stores/my-store/products/my-product`
- **After**: `GET /api/v1/storefront/stores/my-product` (Note: simplified prefix)

### Customer Login
- **Before**: `POST /api/v1/storefront/account/login`
- **After**: `POST /api/v1/customer/auth/login`
