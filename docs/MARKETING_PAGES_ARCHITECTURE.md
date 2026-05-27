# Marketing Pages Architecture

## Platform Marketing Pages vs Merchant Marketing Pages

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Platform Marketing Pages](#1-platform-marketing-pages)
3. [Merchant Marketing Pages](#2-merchant-marketing-pages)
4. [Current Conflicts and Problems](#current-conflicts-and-problems)
5. [Recommended Architecture](#recommended-architecture)
6. [Refactoring Priorities](#refactoring-priorities)
7. [Comparison Table](#comparison-table)

---

## Architecture Overview

The codebase has **three** marketing page systems, not two. Understanding all three is necessary to understand the current state and where things are heading.

| System | Table | Model | Status |
|:---|:---|:---|:---|
| Legacy Platform | `marketing_pages` | `MarketingPage` | Active, being migrated away from |
| New Platform | `platform_marketing_pages` | `PlatformMarketingPage` | New source of truth |
| Merchant / Store | `store_marketing_pages` | `StoreMarketingPage` | Backend ready, frontend deferred |

The `PublicMarketingController` tries `PlatformMarketingPage` first, then falls back to the legacy `MarketingPage`. This bridge will be removed once the migration is complete.

---

## 1. Platform Marketing Pages

### What they are

Pages owned and managed by **the SaaS company itself** — the platform operator. These are the pages that describe and sell the platform to potential merchants. They live on the platform's own domain (e.g. `yourplatform.com`), not on any merchant's store.

### Concrete examples

The legacy `MarketingPageTypeEnum` makes this explicit:

| Type | Description |
|:---|:---|
| `home` | Platform homepage |
| `about` | About the company |
| `contact` | Contact page |
| `features` | Feature showcase |
| `enterprise` | Enterprise sales page |
| `pricing` | Subscription pricing |
| `blog` | Blog index (platform-level) |
| `documentation` | Docs index |
| `demo` | Demo / trial page |
| `templates` | Template gallery |

The new `MarketingPageTemplateEnum` platform templates: `home`, `pricing`, `features`, `about`, `generic`.

### Models

```
app/Models/Cms/MarketingPage.php                                      ← legacy
app/Models/Cms/Marketing/Platform/PlatformMarketingPage.php           ← new source of truth
app/Models/Cms/Marketing/Platform/PlatformMarketingSection.php
```

### Key schema characteristics

- **No `store_id`** — globally scoped, tenant-independent
- `type` column (legacy) or `template` column (new) identifies the page purpose
- Sections stored as monolithic JSON in `sections` column (legacy) or as separate rows in `platform_marketing_sections` (new)
- Globally unique slugs across the entire platform

### Controllers

```
App\Http\Controllers\Api\Platform\AdminMarketingPageController         ← legacy
App\Http\Controllers\Api\Platform\AdminPlatformMarketingPageController ← new
App\Http\Controllers\Api\Public\PublicMarketingController              ← public read
```

### Routes

```
# Admin (super_admin only, under /api/v1/platform/)
GET    /api/v1/platform/cms/pages                    ← legacy compatibility
POST   /api/v1/platform/cms/pages
GET    /api/v1/platform/cms/pages/{id}
PUT    /api/v1/platform/cms/pages/{id}
DELETE /api/v1/platform/cms/pages/{id}
POST   /api/v1/platform/cms/pages/{id}/publish

GET    /api/v1/platform/cms/pages/platform           ← new target
POST   /api/v1/platform/cms/pages/platform
GET    /api/v1/platform/cms/pages/platform/{id}
PUT    /api/v1/platform/cms/pages/platform/{id}
DELETE /api/v1/platform/cms/pages/platform/{id}
POST   /api/v1/platform/cms/pages/platform/{id}/publish

# Public (unauthenticated)
GET    /api/v1/public/cms/pages/{slug}
```

### Who manages them

- `super_admin` role only
- Permissions: `marketing.platform.view / create / update / delete / publish`
- Legacy permissions: `cms.page.view / create / update / delete / publish`
- Policy: `PlatformMarketingPagePolicy` (new), `MarketingPagePolicy` (legacy)

### Tenancy

Completely tenant-independent. No `store_id`. Slugs are globally unique. SEO canonical URLs point to the platform domain.

### CMS behavior

- Full publish workflow: `draft → scheduled → published`
- ISR revalidation triggers on publish (Next.js cache invalidation via `IsrRevalidationService`)
- Redis tag-based cache (`cms:marketing`, 1-hour TTL)
- Sitemap generation via `SitemapService`
- Draft content always gets `noindex,nofollow` robots

---

## 2. Merchant Marketing Pages

### What they are

Pages owned and managed by **individual merchants** for their own stores. These are tenant-scoped promotional pages that live on the merchant's storefront — not the platform's marketing site.

### Concrete examples

The `MarketingPageTemplateEnum` store templates:

| Template | Description |
|:---|:---|
| `landing` | Store landing pages, custom homepages |
| `campaign` | Seasonal campaigns (Summer Sale, Black Friday, etc.) |
| `promotion` | Product promotions, bundle deals |
| `generic` | Freeform custom pages |

Real-world equivalents: Shopify's "Pages" feature, custom landing pages for ad campaigns, seasonal sale pages, affiliate landing pages per store.

### Models

```
app/Models/Cms/Marketing/Store/StoreMarketingPage.php
app/Models/Cms/Marketing/Store/StoreMarketingSection.php
```

### Key schema characteristics

- **Mandatory `store_id`** — hard tenant isolation, FK to `stores` table with cascade delete
- Slug uniqueness is **scoped per store** — two stores can have the same slug
- Sections stored as separate rows in `store_marketing_sections` (also carries `store_id` for isolation)
- No globally unique constraint on slugs

### Controllers

```
App\Http\Controllers\Api\Merchant\AdminStoreMarketingPageController
```

### Routes

```
# Merchant Admin (auth:sanctum + store.context middleware)
GET    /api/v1/merchant/stores/{store}/cms/pages
POST   /api/v1/merchant/stores/{store}/cms/pages
GET    /api/v1/merchant/stores/{store}/cms/pages/{id}
PUT    /api/v1/merchant/stores/{store}/cms/pages/{id}
DELETE /api/v1/merchant/stores/{store}/cms/pages/{id}

# Public store CMS (DEFERRED — not yet implemented)
GET    /api/v1/public/stores/{store}/cms/pages/{slug}   ← planned, not active
```

### Who manages them

- Merchant (store owner) or store staff with appropriate permissions
- Must be a **member of the specific store** (`HasStoreMembership` trait enforced in policy)
- Permissions: `marketing.store.view / create / update / delete / publish`
- Policy: `StoreMarketingPagePolicy` — checks both store membership AND permission

### Tenancy

Fully tenant-scoped. Every query is filtered by `store_id`. A merchant can only see and manage pages belonging to their own store. Cascade delete ensures pages are removed when a store is deleted.

### CMS behavior

- Same publish workflow: `draft → scheduled → published`
- No ISR revalidation yet (public endpoint not active)
- No dedicated cache service yet (public endpoint not active)
- No dedicated `publish` action or route — status changes go through `update`
- No dedicated API resource class — returns raw Eloquent model

---

## Current Conflicts and Problems

### 1. Three systems, not two

The legacy `marketing_pages` table is still active and the public controller falls back to it. This means there are effectively two platform marketing systems running in parallel. Both `AdminMarketingPageController` (legacy) and `AdminPlatformMarketingPageController` (new) have active routes.

### 2. Route collision risk

Both legacy and new platform routes live under `/api/v1/platform/cms/pages`. The new one is nested at `/platform` but registered in the same prefix group:

```
/api/v1/platform/cms/pages            ← legacy controller
/api/v1/platform/cms/pages/platform   ← new controller
```

A `GET /api/v1/platform/cms/pages/platform` could match the legacy `show` route with `id = "platform"` before hitting the new index route, depending on route ordering.

### 3. Inconsistent permission naming

Three separate permission namespaces exist for what is conceptually the same domain:

| Namespace | Applies to |
|:---|:---|
| `cms.page.*` | Legacy platform pages |
| `marketing.platform.*` | New platform pages |
| `marketing.store.*` | Store pages |

Any role seeder or permission assignment has to know about all three.

### 4. Merchant controller has no validation

`AdminStoreMarketingPageController::store()` and `update()` use raw `$request->all()` / `$request->except()` with no `FormRequest` validation. The platform controllers have proper `CreatePlatformMarketingPageRequest` / `UpdatePlatformMarketingPageRequest` with full rules. The store controller has none.

### 5. No publish endpoint for store pages

The merchant route group has no `POST /{id}/publish` route. Status changes go through `update`, which means there is no dedicated publish action, no ISR trigger, and no cache invalidation on publish.

### 6. No API resource for store pages

The store controller returns raw Eloquent models. The platform controllers return typed `AdminPlatformMarketingPageResource`. The store API response shape is undefined and will drift.

### 7. `MarketingPageCacheService` is coupled to the legacy model

The cache service type-hints `MarketingPage` (legacy model). `UpdatePlatformMarketingPageAction` even has a comment acknowledging this — it calls `invalidateAll()` as a workaround instead of the more targeted `invalidateForPage()`.

### 8. `MarketingPageResource` uses `instanceof` branching

The public resource does `if ($page instanceof PlatformMarketingPage)` to decide how to resolve sections. This is a code smell — the resource is doing model-type detection instead of relying on polymorphism or separate resources.

---

## Recommended Architecture

### Naming conventions

| Concept | Recommended naming |
|:---|:---|
| Platform pages (new) | `PlatformMarketingPage`, `platform_marketing_pages` |
| Platform sections | `PlatformMarketingSection`, `platform_marketing_sections` |
| Store pages | `StoreMarketingPage`, `store_marketing_pages` |
| Store sections | `StoreMarketingSection`, `store_marketing_sections` |
| Legacy (to retire) | `MarketingPage`, `marketing_pages` — keep until migration complete, then drop |

### Recommended route structure

```
# Platform admin (super_admin)
GET/POST   /api/v1/platform/cms/marketing-pages
GET/PUT/DELETE /api/v1/platform/cms/marketing-pages/{id}
POST       /api/v1/platform/cms/marketing-pages/{id}/publish

# Merchant admin (store-scoped)
GET/POST   /api/v1/merchant/stores/{store}/cms/marketing-pages
GET/PUT/DELETE /api/v1/merchant/stores/{store}/cms/marketing-pages/{id}
POST       /api/v1/merchant/stores/{store}/cms/marketing-pages/{id}/publish

# Public platform
GET        /api/v1/public/cms/pages/{slug}

# Public store (when ready)
GET        /api/v1/public/stores/{store}/cms/pages/{slug}
```

The current `/cms/pages` and `/cms/pages/platform` nesting should be flattened. Legacy routes should be kept as aliases during migration, then removed.

### Recommended folder structure

```
app/
├── Models/Cms/
│   ├── Marketing/
│   │   ├── Platform/
│   │   │   ├── PlatformMarketingPage.php
│   │   │   └── PlatformMarketingSection.php
│   │   └── Store/
│   │       ├── StoreMarketingPage.php
│   │       └── StoreMarketingSection.php
│   └── MarketingPage.php                          ← legacy, retire after migration
│
├── Http/Controllers/Api/
│   ├── Platform/
│   │   └── AdminPlatformMarketingPageController.php
│   ├── Merchant/
│   │   └── AdminStoreMarketingPageController.php
│   └── Public/
│       └── PublicMarketingController.php
│
├── Actions/Cms/Marketing/
│   ├── Platform/Admin/
│   │   ├── CreatePlatformMarketingPageAction.php
│   │   ├── UpdatePlatformMarketingPageAction.php
│   │   ├── DeletePlatformMarketingPageAction.php
│   │   └── PublishPlatformMarketingPageAction.php
│   └── Store/Admin/                               ← currently missing
│       ├── CreateStoreMarketingPageAction.php
│       ├── UpdateStoreMarketingPageAction.php
│       ├── DeleteStoreMarketingPageAction.php
│       └── PublishStoreMarketingPageAction.php
│
├── Repositories/Cms/Marketing/
│   ├── Platform/PlatformMarketingPageRepository.php
│   └── Store/StoreMarketingPageRepository.php
│
├── Policies/Cms/Marketing/
│   ├── Platform/PlatformMarketingPagePolicy.php
│   └── Store/StoreMarketingPagePolicy.php
│
└── Http/Resources/Admin/Cms/Marketing/
    ├── Platform/AdminPlatformMarketingPageResource.php
    └── Store/AdminStoreMarketingPageResource.php  ← needs to be created
```

### Recommended API namespace separation

| Layer | Platform | Store |
|:---|:---|:---|
| Route prefix | `/api/v1/platform/cms/marketing-pages` | `/api/v1/merchant/stores/{store}/cms/marketing-pages` |
| Middleware | `platform.authority:platform_admin` | `identity.route:merchant_admin` + `store.context` |
| Controller namespace | `Api\Platform\` | `Api\Merchant\` |
| Permission prefix | `marketing.platform.*` | `marketing.store.*` |

### Recommended database / model separation

The current separation is already correct at the schema level. The remaining cleanup:

- Drop `marketing_pages` table after migrating all data to `platform_marketing_pages`
- Add a dedicated `publish` endpoint to store pages (currently missing)
- Add `content` nullable migration to `platform_marketing_pages` (same issue that was fixed on store pages)
- Decouple `MarketingPageCacheService` from the legacy `MarketingPage` model

---

## Refactoring Priorities

In order of impact:

| Priority | Task |
|:---|:---|
| 1 | Resolve the route collision — rename `/cms/pages` to `/cms/marketing-pages` for the new platform controller, keep `/cms/pages` as a deprecated alias |
| 2 | Add `FormRequest` validation to `AdminStoreMarketingPageController` — currently accepts anything |
| 3 | Add `AdminStoreMarketingPageResource` — define the response contract |
| 4 | Add publish route for store pages — `POST /stores/{store}/cms/marketing-pages/{id}/publish` |
| 5 | Decouple `MarketingPageCacheService` from the legacy model — accept an interface or use a generic cache key strategy |
| 6 | Split `MarketingPageResource` — separate `PlatformMarketingPageResource` and `StoreMarketingPageResource` for public reads, remove `instanceof` branching |
| 7 | Retire legacy `marketing_pages` system — migrate data, remove `AdminMarketingPageController`, remove legacy routes, remove `MarketingPage` model |

---

## Comparison Table

| Dimension | Platform Marketing Pages | Merchant Marketing Pages |
|:---|:---|:---|
| **Owner** | SaaS platform company | Individual merchant / store |
| **Purpose** | Sell the platform to potential merchants | Promote the merchant's products/store to customers |
| **Examples** | Homepage, pricing, features, about, enterprise, demo | Campaign pages, seasonal sales, product landing pages, store promotions |
| **Domain** | Platform domain (`yourplatform.com`) | Merchant storefront domain (`merchant.com`) |
| **Tenancy** | Global — no `store_id` | Tenant-scoped — mandatory `store_id` |
| **Slug uniqueness** | Globally unique across all platform pages | Scoped per store — two stores can share the same slug |
| **DB table** | `platform_marketing_pages` (+ legacy `marketing_pages`) | `store_marketing_pages` |
| **Sections table** | `platform_marketing_sections` | `store_marketing_sections` |
| **Who manages** | `super_admin` only | Merchant owner or store staff with permission |
| **Permission prefix** | `marketing.platform.*` | `marketing.store.*` |
| **Policy** | `PlatformMarketingPagePolicy` | `StoreMarketingPagePolicy` (also checks store membership) |
| **Admin route** | `/api/v1/platform/cms/pages/platform` | `/api/v1/merchant/stores/{store}/cms/pages` |
| **Public route** | `/api/v1/public/cms/pages/{slug}` | Not yet active (deferred) |
| **Publish action** | Dedicated `POST /{id}/publish` with ISR trigger | Via `update` only — no dedicated publish endpoint |
| **Cache** | Redis tag cache (`cms:marketing`), 1-hour TTL | No cache yet (public endpoint not active) |
| **ISR revalidation** | Yes — triggers Next.js ISR on publish | No — deferred with public endpoint |
| **SEO scope** | Platform-level SEO, global canonical URLs | Store-level SEO, tenant-scoped canonical URLs |
| **SEO contract** | `HasSeoMetadata` + `SeoResolutionService` | `HasSeoMetadata` + `SeoResolutionService` (same) |
| **Robots default** | `index,follow` (production) | `index,follow` (production) |
| **Draft robots** | Always `noindex,nofollow` | Always `noindex,nofollow` |
| **Localization** | JSON locale maps `{"en": ..., "ar": ...}` | JSON locale maps (same) |
| **Templates** | `home, pricing, features, about, generic` | `landing, campaign, promotion, generic` |
| **Section types** | `hero, features, pricing, testimonials, cta, faq, gallery, video, custom` | Same enum |
| **API resource** | `AdminPlatformMarketingPageResource` | None yet (raw model) |
| **Validation** | Full `FormRequest` classes | None yet (raw `$request->all()`) |
| **Soft deletes** | Yes | Yes |
| **Audit trail** | `created_by`, `updated_by` | `created_by`, `updated_by` |
| **Current state** | Production-ready | Backend foundation only |

---

## Related Documentation

- [CMS Marketing Architecture](./CMS_MARKETING_ARCHITECTURE.md)
- [Main Architecture Rules](./ARCHITECTURE.md)
- [Authorization Doctrine](./ARCHITECTURE.md#authorization-doctrine)
