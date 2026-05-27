# Routing Contexts

The LaraTenant API is organized around distinct application contexts. Each context represents a specific actor's interaction with the platform, ensuring clear boundaries for authorization, auditing, and scalability.

## Overview

Instead of a monolithic API structure, the backend is partitioned into several independent contexts. This prevents "identity leakage" where a merchant might accidentally access platform-level features or a storefront user might access merchant-level data.

## Application Contexts

### 1. Platform Context (`/api/v1/platform`)
- **Actor**: SaaS Owner / Super Admin
- **Purpose**: Internal tooling for managing the entire SaaS platform.
- **Key Responsibilities**:
    - Tenant management (creation, suspension, activation)
    - Platform-wide analytics
    - Feature flag management
    - Audit log oversight
    - CMS management for marketing/docs
- **Auth Guard**: `merchant` (Platform users share the merchant user table but have `SUPER_ADMIN` roles).
- **Middleware**: `platform.authority:platform_admin`, `identity.route:platform,platform,enforce`.

### 2. Merchant Context (`/api/v1/merchant`)
- **Actor**: Store Owner / Staff
- **Purpose**: Administration dashboard for individual tenants.
- **Key Responsibilities**:
    - Product and inventory management
    - Order processing
    - Category and brand management
    - Store settings and profile management
    - Staff user management
- **Auth Guard**: `merchant`.
- **Middleware**: `identity.route:merchant_users,merchant,enforce`.

### 3. Storefront Context (`/api/v1/storefront`)
- **Actor**: Public Shopper / Guest
- **Purpose**: Public-facing ecommerce API for the store.
- **Key Responsibilities**:
    - Product browsing and search
    - Cart management
    - Checkout processing
    - Homepage and CMS content retrieval
- **Auth Guard**: `customer` (when logged in).
- **Middleware**: `identity.route:storefront_commerce,customer,observe`, `store.context`.

### 4. Customer Context (`/api/v1/customer`)
- **Actor**: Registered Customer
- **Purpose**: Identity and account management for store shoppers.
- **Key Responsibilities**:
    - Customer authentication (login/register)
    - Profile management
    - Order history
    - Address book
- **Auth Guard**: `customer`.
- **Middleware**: `identity.route:customer_account,customer,enforce`.

### 5. Support Context (`/api/v1/support`)
- **Actor**: Internal Support Agent / Super Admin
- **Purpose**: Tools for assisting merchants and investigating issues.
- **Key Responsibilities**:
    - Ticket management
    - Merchant impersonation (audited)
    - User/Store lookup
- **Auth Guard**: `merchant`.
- **Middleware**: `support.authority`, `identity.route:support,platform,enforce`.

### 6. Public Context (`/api/v1/public`)
- **Actor**: Anonymous Public / Search Engines
- **Purpose**: Marketing site, SEO, and documentation.
- **Key Responsibilities**:
    - Marketing page CMS
    - Public blog
    - Public documentation
    - SEO sitemaps and robots.txt
- **Auth Guard**: None (Public).
- **Middleware**: `identity.route:public,customer,observe`.

## Naming Conventions

All routes follow a consistent naming pattern matching their context:
- `platform.*`
- `merchant.*`
- `storefront.*`
- `customer.*`
- `support.*`
- `public.*`

## Controller Ownership

Each context has its own dedicated controller namespace to prevent logic leakage and ensure clear authorization boundaries:

- **Platform**: `App\Http\Controllers\Api\Platform\*`
- **Merchant**: `App\Http\Controllers\Api\Merchant\*`
- **Storefront**: `App\Http\Controllers\Api\Storefront\*`
- **Customer**: `App\Http\Controllers\Api\Customer\*`
- **Support**: `App\Http\Controllers\Api\Support\*`
- **Public**: `App\Http\Controllers\Api\Public\*`

Controllers must NOT be shared across contexts. If common logic is needed, it should be extracted into **Actions**, **Services**, or **Traits**.
