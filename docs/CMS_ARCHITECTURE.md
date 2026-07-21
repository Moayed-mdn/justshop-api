# CMS Architecture Documentation

## Overview

This document maps the complete CMS architecture for the multi-tenant SaaS platform.

---

## 1️⃣ PLATFORM-LEVEL CMS (Global Content)

**Purpose**: Content for the marketing website (not tenant-specific)  
**Access**: Super admins only (team_id = 0)  
**Isolation**: NO store_id column (shared across all tenants)

### A. Blog Posts

| Component | Value |
|-----------|-------|
| **Model** | `App\Models\BlogPost` |
| **Table** | `blog_posts` |
| **Has store_id?** | ❌ NO (Platform-level) |
| **Controller** | `App\Http\Controllers\Api\Platform\PlatformBlogController` |
| **Routes** | `/api/v1/platform/cms/blog/*` |
| **Permissions** | `CMS_BLOG_VIEW`, `CMS_BLOG_CREATE`, `CMS_BLOG_UPDATE`, `CMS_BLOG_DELETE`, `CMS_BLOG_PUBLISH` |
| **Authorization** | Permission checks (NOT policies) |
| **Middleware** | `web`, `auth:sanctum`, `identity.route:platform,platform,enforce`, `platform.context`, `platform.authority:platform_admin` |
| **Purpose** | Marketing site blog (e.g., company news, product updates) |

**API Endpoints:**
```
GET    /api/v1/platform/cms/blog          # List blog posts
POST   /api/v1/platform/cms/blog          # Create blog post
GET    /api/v1/platform/cms/blog/{id}     # Show blog post
PUT    /api/v1/platform/cms/blog/{id}     # Update blog post
DELETE /api/v1/platform/cms/blog/{id}     # Delete blog post
POST   /api/v1/platform/cms/blog/{id}/publish    # Publish blog post
POST   /api/v1/platform/cms/blog/{id}/unpublish  # Unpublish blog post
POST   /api/v1/platform/cms/blog/{id}/schedule   # Schedule blog post
GET    /api/v1/platform/cms/blog/meta/categories # List categories
GET    /api/v1/platform/cms/blog/meta/tags       # List tags
```

**Related Models:**
- `BlogCategory` (blog_categories)
- `BlogTag` (blog_tags)

---

### B. Documentation

| Component | Value |
|-----------|-------|
| **Model** | `App\Models\Cms\CmsDocument` |
| **Table** | `cms_documents` |
| **Has store_id?** | ❌ NO (Platform-level) |
| **Controller** | `App\Http\Controllers\Api\Platform\AdminDocumentController` |
| **Routes** | `/api/v1/platform/cms/docs/*` (alias: `/api/v1/platform/cms/documentation/*`) |
| **Permissions** | `CMS_DOC_VIEW`, `CMS_DOC_CREATE`, `CMS_DOC_UPDATE`, `CMS_DOC_DELETE`, `CMS_DOC_PUBLISH` |
| **Authorization** | Permission checks (NOT policies) |
| **Middleware** | `web`, `auth:sanctum`, `identity.route:platform,platform,enforce`, `platform.context`, `platform.authority:platform_admin` |
| **Purpose** | Help documentation for the marketing site (e.g., API docs, guides, FAQs) |

**API Endpoints:**
```
GET    /api/v1/platform/cms/docs             # List documents
POST   /api/v1/platform/cms/docs             # Create document
GET    /api/v1/platform/cms/docs/{id}        # Show document
PUT    /api/v1/platform/cms/docs/{id}        # Update document
DELETE /api/v1/platform/cms/docs/{id}        # Delete document
POST   /api/v1/platform/cms/docs/{id}/publish   # Publish document
POST   /api/v1/platform/cms/docs/{id}/unpublish # Unpublish document
POST   /api/v1/platform/cms/docs/reorder     # Reorder documents
```

**Related Models:**
- `CmsDocumentSection` (cms_document_sections)

---

### C. Marketing Pages

| Component | Value |
|-----------|-------|
| **Model** | `App\Models\Cms\Marketing\Platform\PlatformMarketingPage` |
| **Table** | `platform_marketing_pages` |
| **Has store_id?** | ❌ NO (Platform-level) |
| **Controller** | `App\Http\Controllers\Api\Platform\AdminPlatformMarketingPageController` |
| **Routes** | `/api/v1/platform/cms/pages/*` |
| **Permissions** | `MARKETING_PLATFORM_VIEW`, `MARKETING_PLATFORM_CREATE`, `MARKETING_PLATFORM_UPDATE`, `MARKETING_PLATFORM_DELETE`, `MARKETING_PLATFORM_PUBLISH` |
| **Authorization** | Permission checks (NOT policies) |
| **Middleware** | `web`, `auth:sanctum`, `identity.route:platform,platform,enforce`, `platform.context`, `platform.authority:platform_admin` |
| **Purpose** | Marketing pages (e.g., pricing, features, about, contact) |

**API Endpoints:**
```
GET    /api/v1/platform/cms/pages          # List marketing pages
POST   /api/v1/platform/cms/pages          # Create marketing page
GET    /api/v1/platform/cms/pages/{id}     # Show marketing page
PUT    /api/v1/platform/cms/pages/{id}     # Update marketing page
DELETE /api/v1/platform/cms/pages/{id}     # Delete marketing page
POST   /api/v1/platform/cms/pages/{id}/publish  # Publish marketing page
```

**Related Models:**
- `PlatformMarketingSection` (platform_marketing_sections)

---

## 2️⃣ STORE-LEVEL CMS (Tenant Content)

**Purpose**: Content for individual merchant stores (tenant-specific)  
**Access**: Store admins and staff (per store, team_id = store_id)  
**Isolation**: HAS store_id column (scoped to specific stores)

### A. Store Marketing Pages

| Component | Value |
|-----------|-------|
| **Model** | `App\Models\Cms\Marketing\Store\StoreMarketingPage` |
| **Table** | `store_marketing_pages` |
| **Has store_id?** | ✅ YES (Store-scoped) |
| **Controller** | `App\Http\Controllers\Api\Merchant\AdminStoreMarketingPageController` |
| **Routes** | `/api/v1/merchant/stores/{store}/cms/pages/*` (assumed, need to verify) |
| **Permissions** | `MARKETING_STORE_VIEW`, `MARKETING_STORE_CREATE`, `MARKETING_STORE_UPDATE`, `MARKETING_STORE_DELETE`, `MARKETING_STORE_PUBLISH` |
| **Authorization** | Store-scoped policies |
| **Middleware** | `web`, `auth:sanctum`, `identity.route:merchant_admin,merchant,enforce`, `store.context` |
| **Purpose** | Store-specific pages (e.g., store policies, custom pages, store about) |

**API Endpoints (Expected):**
```
GET    /api/v1/merchant/stores/{store}/cms/pages          # List store pages
POST   /api/v1/merchant/stores/{store}/cms/pages          # Create store page
GET    /api/v1/merchant/stores/{store}/cms/pages/{id}     # Show store page
PUT    /api/v1/merchant/stores/{store}/cms/pages/{id}     # Update store page
DELETE /api/v1/merchant/stores/{store}/cms/pages/{id}     # Delete store page
POST   /api/v1/merchant/stores/{store}/cms/pages/{id}/publish  # Publish store page
```

**Related Models:**
- `StoreMarketingSection` (store_marketing_sections)

---

## 3️⃣ LEGACY SYSTEM (To Be Deprecated)

### marketing_pages Table

| Component | Value |
|-----------|-------|
| **Model** | `App\Models\Cms\MarketingPage` |
| **Table** | `marketing_pages` |
| **Has store_id?** | ❌ NO |
| **Controller** | `App\Http\Controllers\Api\Platform\AdminMarketingPageController` |
| **Routes** | ⚠️ NOT CURRENTLY ROUTED |
| **Permissions** | `CMS_PAGE_VIEW`, `CMS_PAGE_CREATE`, `CMS_PAGE_UPDATE`, `CMS_PAGE_DELETE`, `CMS_PAGE_PUBLISH` |
| **Authorization** | Store-scoped policies (WRONG for platform content!) |
| **Status** | **LEGACY - DO NOT USE** |
| **Purpose** | Old marketing pages system before platform_marketing_pages was created |
| **Migration Path** | Data should be migrated to `platform_marketing_pages` or deleted |
| **Current Data** | 10 records (contains old homepage, pricing, features data) |

**Why It's Deprecated:**
1. Uses store-scoped policies for platform-level content (architecture violation)
2. Has different schema than `platform_marketing_pages`
3. Not currently connected to any routes (orphaned)
4. Replaced by `platform_marketing_pages` system

**What To Do:**
- Option 1: Manually migrate the 10 records to `platform_marketing_pages` format
- Option 2: Keep as historical backup and build new content in `platform_marketing_pages`
- Option 3: Drop the table after confirming no dependencies

---

## 4️⃣ PUBLIC CMS ROUTES (Read-Only)

**Purpose**: Public-facing endpoints for marketing site visitors (unauthenticated)

| Endpoint | Controller | Purpose |
|----------|------------|---------|
| `GET /api/v1/public/cms/blog` | `PublicBlogController` | List published blog posts |
| `GET /api/v1/public/cms/blog/{slug}` | `PublicBlogController` | Show single blog post |
| `GET /api/v1/public/cms/docs/{path}` | `PublicDocumentController` | Show documentation |
| `GET /api/v1/public/cms/pages/{slug}` | `PublicMarketingController` | Show marketing page |
| `GET /api/v1/public/cms/seo/sitemap/*` | `PublicCmsSeoController` | Sitemap generation |

**Middleware**: `web` (no authentication required)  
**Data Source**: Same tables as platform CMS, but filtered to `is_published = true` and `published_at <= now()`

---

## 5️⃣ MIDDLEWARE ARCHITECTURE

### Platform CMS Middleware Stack
```php
'web',
'auth:sanctum',                                      // Require authentication
'identity.route:platform,platform,enforce',          // Ensure platform actor
'platform.context',                                  // Set team_id = 0 for permissions
'platform.authority:platform_admin',                 // Verify platform authority
```

**Critical**: `platform.context` middleware sets `setPermissionsTeamId(0)` so Spatie Permission checks work at the global level.

### Store CMS Middleware Stack
```php
'web',
'auth:sanctum',                                      // Require authentication
'identity.route:merchant_admin,merchant,enforce',    // Ensure merchant actor
'store.context',                                     // Set team_id = store_id for permissions
```

**Critical**: `store.context` middleware sets `setPermissionsTeamId($storeId)` so Spatie Permission checks work at the store level.

---

## 6️⃣ AUTHORIZATION PATTERNS

### Platform CMS (Permission-Based)
```php
// CORRECT ✅
if (!auth()->user()?->can(PermissionEnum::CMS_BLOG_VIEW)) {
    abort(403, 'This action is unauthorized.');
}
```

### Store CMS (Policy-Based)
```php
// CORRECT ✅
$this->authorize('viewAny', StoreMarketingPage::class);
// Policy checks: user has permission AND page belongs to user's store
```

### Legacy System (WRONG ❌)
```php
// WRONG - Uses policies for platform content
$this->authorize('viewAny', MarketingPage::class);
// This fails because MarketingPage has no store_id but policy expects store scoping
```

---

## 7️⃣ CURRENT STATUS

| System | Status | Record Count | Routes Connected |
|--------|--------|--------------|------------------|
| Platform Blog | ✅ Working | 8 | ✅ Yes |
| Platform Docs | ✅ Working | 12 | ✅ Yes |
| Platform Marketing Pages | ✅ Working | 4 | ✅ Yes |
| Store Marketing Pages | ✅ Working | 5 | ⚠️ Unknown (need to verify merchant routes) |
| Legacy marketing_pages | ❌ Deprecated | 10 | ❌ No (orphaned) |

---

## 8️⃣ FRONTEND DASHBOARD MAPPING

**Platform Dashboard** (http://localhost:3001):
- `/en/cms` - Manages Platform-Level CMS
  - Blog posts (`/api/v1/platform/cms/blog`)
  - Documentation (`/api/v1/platform/cms/docs`)
  - Marketing pages (`/api/v1/platform/cms/pages`)

**Merchant Dashboard** (assumed to exist):
- `/stores/{id}/cms` - Should manage Store-Level CMS
  - Store pages (`/api/v1/merchant/stores/{store}/cms/pages`)

---

## 9️⃣ REAL-WORLD ANALOGY (Shopify Model)

### Platform-Level CMS (What We Built)
**Example**: Shopify.com (the marketing site)
- Blog: https://shopify.com/blog
- Docs: https://shopify.dev
- Pages: https://shopify.com/pricing, https://shopify.com/features

### Store-Level CMS (What Merchants Use)
**Example**: Individual merchant stores (e.g., gymshark.com)
- Each merchant creates their own pages (About Us, Shipping Policy, etc.)
- Completely isolated from other merchants
- Not visible on Shopify's marketing site

---

## 🔟 RECOMMENDATIONS

1. **Immediate**: Verify store marketing pages routes exist in merchant context
2. **Short-term**: Decide fate of legacy `marketing_pages` table (migrate or drop)
3. **Medium-term**: Build merchant UI for store-level CMS management
4. **Long-term**: Consider adding versioning/drafts to all CMS content types
