# Legacy Marketing Pages — Migration Stabilization Plan

**Priority 3 Deliverable**  
**Date:** 2026-05-27  
**Status:** Stabilization phase — legacy coexistence preserved, removal deferred

---

## Overview

Three marketing page systems currently coexist in production. This document describes their dependencies, the current coexistence strategy, and the prerequisites for safe removal of the legacy system.

---

## System Inventory

| System | Model | Table | Status |
|:---|:---|:---|:---|
| Legacy Platform | `App\Models\Cms\MarketingPage` | `marketing_pages` | Active — fallback path only |
| New Platform | `App\Models\Cms\Marketing\Platform\PlatformMarketingPage` | `platform_marketing_pages` | Active — primary source of truth |
| Merchant/Store | `App\Models\Cms\Marketing\Store\StoreMarketingPage` | `store_marketing_pages` | Active — fully operational |

---

## Route Ordering Audit

### Collision Risk Assessment

The legacy and new platform routes share the same prefix group:

```
GET /api/v1/platform/cms/pages           → AdminMarketingPageController@index  (legacy)
GET /api/v1/platform/cms/pages/platform  → AdminPlatformMarketingPageController@index  (new)
GET /api/v1/platform/cms/pages/{id}      → AdminMarketingPageController@show  (legacy)
```

**Potential collision:** `GET /api/v1/platform/cms/pages/platform` could match the legacy `show` route with `id = "platform"` before reaching the new `index` route.

**Verdict: SAFE — no collision.**

Verified via `php artisan route:list --path=platform/cms`:

```
GET|HEAD  api/v1/platform/cms/pages/platform          platform.cms.pages.platform.index
GET|HEAD  api/v1/platform/cms/pages/platform/{id}     platform.cms.pages.platform.show
GET|HEAD  api/v1/platform/cms/pages/{id}              platform.cms.pages.show
```

Laravel registers the literal `/platform` prefix route **before** the `/{id}` wildcard route because the `/platform` prefix group is declared first in `routes/api/v1/platform/cms/marketing-pages.php`. The router matches literal segments before wildcard segments at the same depth.

**Why it is safe:**
1. The `/platform` prefix group is registered inside the same `cms/pages` group, before the `/{id}` wildcard routes.
2. Laravel's router evaluates routes in registration order. Literal path segments always win over wildcards at the same depth.
3. `GET /platform/cms/pages/platform` resolves to `platform.cms.pages.platform.index` — the new controller's index.
4. `GET /platform/cms/pages/platform/123` resolves to `platform.cms.pages.platform.show` — the new controller's show.
5. `GET /platform/cms/pages/123` resolves to `platform.cms.pages.show` — the legacy controller's show.

**Preservation instruction:** Do NOT reorder the route declarations in `marketing-pages.php`. The `/platform` prefix group MUST remain declared before the `/{id}` wildcard routes.

---

## Legacy Dependencies

### Active Consumers of `MarketingPage` (legacy model)

| Consumer | File | Usage | Migration Status |
|:---|:---|:---|:---|
| `AdminMarketingPageController` | `app/Http/Controllers/Api/Platform/AdminMarketingPageController.php` | Full CRUD + publish | Deprecated — new controller exists |
| `PublicMarketingController` | `app/Http/Controllers/Api/Public/PublicMarketingController.php` | Fallback read path | Bridge — falls back to legacy if platform page not found |
| `MarketingPageCacheService::remember()` | `app/Services/Cms/MarketingPageCacheService.php` | Cache read for legacy public controller | Backward-compat path preserved |
| `MarketingPageCacheService::invalidateForPage()` | `app/Services/Cms/MarketingPageCacheService.php` | Cache invalidation for legacy actions | Backward-compat path preserved |
| `MarketingPageRepository` | `app/Repositories/Cms/MarketingPage/MarketingPageRepository.php` | Data access for legacy controller | Deprecated — platform repository exists |
| `AdminMarketingPageResource` | `app/Http/Resources/Admin/Cms/MarketingPage/AdminMarketingPageResource.php` | Response shaping for legacy controller | Deprecated — platform resource exists |
| Legacy actions (`CreateMarketingPageAction`, etc.) | `app/Actions/Cms/MarketingPage/Admin/` | Business logic for legacy controller | Deprecated — platform actions exist |
| Legacy FormRequests | `app/Http/Requests/Cms/MarketingPage/Admin/` | Validation for legacy controller | Deprecated — platform requests exist |
| Legacy DTOs | `app/DTOs/Cms/MarketingPage/Admin/` | Data transfer for legacy controller | Deprecated — platform DTOs exist |
| `MarketingPageResource` (public) | `app/Http/Resources/Cms/MarketingPage/MarketingPageResource.php` | Public read resource | Shared — supports both legacy and new models via duck-typing |
| `MarketingPageTypeEnum` | `app/Enums/Cms/MarketingPage/MarketingPageTypeEnum.php` | Legacy page type classification | Deprecated — `MarketingPageTemplateEnum` is the new standard |
| `MarketingPageStatusEnum` (legacy) | `app/Enums/Cms/MarketingPage/MarketingPageStatusEnum.php` | Legacy status enum | Deprecated — `App\Enums\Cms\Marketing\MarketingPageStatusEnum` is the new standard |

### Deprecated Routes (still active)

```
GET    /api/v1/platform/cms/pages           → AdminMarketingPageController@index
POST   /api/v1/platform/cms/pages           → AdminMarketingPageController@store
GET    /api/v1/platform/cms/pages/{id}      → AdminMarketingPageController@show
PUT    /api/v1/platform/cms/pages/{id}      → AdminMarketingPageController@update
DELETE /api/v1/platform/cms/pages/{id}      → AdminMarketingPageController@destroy
POST   /api/v1/platform/cms/pages/{id}/publish → AdminMarketingPageController@publish
```

These routes are still active and must not be removed until all consumers have migrated to the new `/platform` sub-routes.

---

## Cache Service Decoupling — Current State

`MarketingPageCacheService` has been decoupled from the legacy model. Current state:

```php
// Legacy path — still type-hints MarketingPage for backward compat
public function remember(string $locale, string $slug, Closure $callback): MarketingPage

// Legacy invalidation — accepts legacy MarketingPage model
public function invalidateForPage(MarketingPage $page, array $additionalSlugs = []): void

// Model-agnostic path — used by platform + store actions
public function invalidateForSlugMap(array $slugMap, array $additionalSlugs = []): void

// Full flush — used when targeted invalidation is not possible
public function invalidateAll(): void
```

**What was done:** The `invalidateForSlugMap()` method was added as a model-agnostic invalidation path. Platform and store actions use this method. The legacy `invalidateForPage()` method is preserved for backward compatibility with legacy actions.

**What remains:** The `remember()` method still returns `MarketingPage` (legacy type). This is intentional — it is only called by the legacy `PublicMarketingController` fallback path. When the legacy system is retired, this method can be removed or generalized.

---

## `MarketingPageResource` — instanceof Branching Status

The public `MarketingPageResource` previously used `instanceof PlatformMarketingPage` branching to resolve sections. This has been replaced with duck-typed attribute access:

```php
// Before (instanceof branching — removed)
if ($page instanceof PlatformMarketingPage) { ... }

// After (duck-typed — current state)
// Priority 1: content column (new pages)
if (!empty($page->content) && is_array($page->content)) { ... }

// Priority 2: sections relation (new platform pages with separate rows)
if ($page->relationLoaded('sections') && $page->sections->isNotEmpty()) { ... }

// Priority 3: legacy sections JSON column
if (isset($page->sections) && is_array($page->sections)) { ... }
```

The resource no longer imports or references `PlatformMarketingPage` directly. It works with any model that has `slug`, `title`, `seo`, `status`, `updated_at`, and either `content`, `sections` relation, or `sections` column.

**Remaining branching:** The `resolveType()` and `resolvePageType()` methods use attribute presence checks (`isset($page->template)` vs `isset($page->type)`) to distinguish new vs legacy models. This is acceptable duck-typing — it does not import concrete model classes.

---

## Migration Strategy

### Phase 1 — Data Migration (prerequisite for removal)

Before removing the legacy system:

1. **Audit `marketing_pages` table** — identify all rows that do NOT have a corresponding row in `platform_marketing_pages` with the same slug.
2. **Migrate data** — for each unmigrated legacy page, create a corresponding `PlatformMarketingPage` with equivalent content, preserving slugs, status, and published_at.
3. **Verify public resolution** — confirm that `PublicMarketingController` resolves all previously-legacy pages from the new platform table.
4. **Remove legacy fallback** — once all pages are migrated, remove the fallback path in `PublicMarketingController`.

### Phase 2 — Route Deprecation

1. Add deprecation headers to legacy routes:
   ```php
   Route::get('/{id}', 'show')->name('show')
       ->withoutMiddleware([])
       ->middleware(['deprecated:platform.cms.pages.platform.show']);
   ```
2. Notify API consumers (internal frontend, mobile clients) of the deprecation timeline.
3. Monitor legacy route usage via access logs / telemetry.

### Phase 3 — Legacy Removal (when usage drops to zero)

Remove in this order:
1. Legacy routes from `marketing-pages.php`
2. `AdminMarketingPageController`
3. Legacy actions (`app/Actions/Cms/MarketingPage/`)
4. Legacy FormRequests (`app/Http/Requests/Cms/MarketingPage/`)
5. Legacy DTOs (`app/DTOs/Cms/MarketingPage/`)
6. `AdminMarketingPageResource`
7. `MarketingPageRepository`
8. `MarketingPage` model
9. `MarketingPageTypeEnum` (legacy)
10. `MarketingPageStatusEnum` (legacy namespace)
11. Legacy `remember()` and `invalidateForPage()` from `MarketingPageCacheService`
12. Drop `marketing_pages` table (migration)

---

## Removal Prerequisites Checklist

Before removing the legacy system, ALL of the following must be true:

- [ ] All rows in `marketing_pages` have been migrated to `platform_marketing_pages`
- [ ] `PublicMarketingController` fallback path has zero hits in production logs for 30 days
- [ ] Legacy admin routes (`/cms/pages` without `/platform`) have zero hits in production logs for 30 days
- [ ] Frontend has been updated to use `/cms/pages/platform` routes exclusively
- [ ] Mobile clients (if any) have been updated
- [ ] Swagger/Scramble docs have been updated
- [ ] All tests referencing `MarketingPage` (legacy) have been updated or removed
- [ ] `MarketingPageResource` has been split into `PlatformMarketingPageResource` and `StoreMarketingPageResource` (or generalized without legacy branching)
- [ ] A rollback plan exists (point-in-time DB backup before table drop)

---

## Do NOT Remove Yet

The following MUST remain until all prerequisites above are met:

- `app/Models/Cms/MarketingPage.php`
- `database/migrations/*_create_marketing_pages_table.php`
- `app/Http/Controllers/Api/Platform/AdminMarketingPageController.php`
- All routes under `Route::controller(AdminMarketingPageController::class)`
- `app/Repositories/Cms/MarketingPage/MarketingPageRepository.php`
- `MarketingPageCacheService::remember()` and `invalidateForPage()`
- The fallback path in `PublicMarketingController`
