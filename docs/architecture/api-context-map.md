# API Context Map

This map provides a quick reference for current route prefixes and their corresponding application contexts.

| Prefix | Context | Actor | Primary File(s) |
| :--- | :--- | :--- | :--- |
| `/v1/platform` | Platform | Super Admin | `api/v1/platform/*` |
| `/v1/merchant` | Merchant | Store Owner/Staff | `api/v1/merchant/*` |
| `/v1/storefront`| Storefront | Shopper/Guest | `api/v1/storefront/*` |
| `/v1/customer` | Customer | Registered Shopper | `api/v1/customer/*` |
| `/v1/support` | Support | Support Agent | `api/v1/support/*` |
| `/v1/public` | Public | Anonymous/Search | `api/v1/public/*` |

## Context Controllers

| Context | Controller Directory |
| :--- | :--- |
| **Platform** | `app/Http/Controllers/Api/Platform` |
| **Merchant** | `app/Http/Controllers/Api/Merchant` |
| **Storefront** | `app/Http/Controllers/Api/Storefront` |
| **Customer** | `app/Http/Controllers/Api/Customer` |
| **Support** | `app/Http/Controllers/Api/Support` |
| **Public** | `app/Http/Controllers/Api/Public` |

## Detailed Mapping

### Platform Context
- `/v1/platform/dashboard`
- `/v1/platform/analytics`
- `/v1/platform/users`
- `/v1/platform/stores`
- `/v1/platform/leads`
- `/v1/platform/cms/*`

### Merchant Context
- `/v1/merchant/me`
- `/v1/merchant/auth/*`
- `/v1/merchant/profile/*`
- `/v1/merchant/stores/*` (Store settings & management)
- `/v1/merchant/stores/{store}/products` (Admin product management)
- `/v1/merchant/stores/{store}/orders` (Admin order management)

### Storefront Context
- `/v1/storefront/stores/{store}/products`
- `/v1/storefront/stores/{store}/cart`
- `/v1/storefront/stores/{store}/checkout`
- `/v1/storefront/stores/{store}/search`

### Customer Context
- `/v1/customer/auth/*`
- `/v1/customer/me`
- `/v1/customer/bootstrap`

### Support Context
- `/v1/support/tickets`
- `/v1/support/impersonation`
- `/v1/support/users/search`

### Public Context
- `/v1/public/cms/pages`
- `/v1/public/cms/blog`
- `/v1/public/cms/docs`
- `/v1/public/seo/*`
