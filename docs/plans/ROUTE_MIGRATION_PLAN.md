# Marketing Pages Route Migration Plan

**Priority 4 Deliverable**  
**Date:** 2026-05-27  
**Status:** Planning only — no routes renamed yet

---

## ⚠️ Frontend Action Required

The following table is the **only thing the frontend needs to act on**. No routes were removed — these are the currently active URLs the frontend should be using right now, and the future URLs it will need to migrate to when aliases are registered.

### Platform Admin CMS (super_admin only)

| Action | ❌ Old / Deprecated URL | ✅ Current Correct URL | Future Target URL |
|:---|:---|:---|:---|
| List pages | `GET /api/v1/platform/cms/pages` | `GET /api/v1/platform/cms/pages/platform` | `GET /api/v1/platform/cms/marketing-pages` |
| Create page | `POST /api/v1/platform/cms/pages` | `POST /api/v1/platform/cms/pages/platform` | `POST /api/v1/platform/cms/marketing-pages` |
| Get page | `GET /api/v1/platform/cms/pages/{id}` | `GET /api/v1/platform/cms/pages/platform/{id}` | `GET /api/v1/platform/cms/marketing-pages/{id}` |
| Update page | `PUT /api/v1/platform/cms/pages/{id}` | `PUT /api/v1/platform/cms/pages/platform/{id}` | `PUT /api/v1/platform/cms/marketing-pages/{id}` |
| Delete page | `DELETE /api/v1/platform/cms/pages/{id}` | `DELETE /api/v1/platform/cms/pages/platform/{id}` | `DELETE /api/v1/platform/cms/marketing-pages/{id}` |
| Publish page | `POST /api/v1/platform/cms/pages/{id}/publish` | `POST /api/v1/platform/cms/pages/platform/{id}/publish` | `POST /api/v1/platform/cms/marketing-pages/{id}/publish` |

> **If the frontend is still hitting `/api/v1/platform/cms/pages` (without `/platform`), it is hitting the legacy controller backed by the old `marketing_pages` table. Switch to `/api/v1/platform/cms/pages/platform` immediately.**

### Merchant Admin CMS (store-scoped)

| Action | Current URL | Future Target URL | Notes |
|:---|:---|:---|:---|
| List pages | `GET /api/v1/merchant/stores/{store}/cms/pages` | `GET /api/v1/merchant/stores/{store}/cms/marketing-pages` | No change needed yet |
| Create page | `POST /api/v1/merchant/stores/{store}/cms/pages` | `POST /api/v1/merchant/stores/{store}/cms/marketing-pages` | No change needed yet |
| Get page | `GET /api/v1/merchant/stores/{store}/cms/pages/{id}` | `GET /api/v1/merchant/stores/{store}/cms/marketing-pages/{id}` | No change needed yet |
| Update page | `PUT /api/v1/merchant/stores/{store}/cms/pages/{id}` | `PUT /api/v1/merchant/stores/{store}/cms/marketing-pages/{id}` | No change needed yet |
| Delete page | `DELETE /api/v1/merchant/stores/{store}/cms/pages/{id}` | `DELETE /api/v1/merchant/stores/{store}/cms/marketing-pages/{id}` | No change needed yet |
| Publish page | `POST /api/v1/merchant/stores/{store}/cms/pages/{id}/publish` | `POST /api/v1/merchant/stores/{store}/cms/marketing-pages/{id}/publish` | **New endpoint — was missing before** |
| Unpublish page | `POST /api/v1/merchant/stores/{store}/cms/pages/{id}/unpublish` | `POST /api/v1/merchant/stores/{store}/cms/marketing-pages/{id}/unpublish` | **New endpoint — was missing before** |

> **The merchant publish and unpublish endpoints are new. The frontend must integrate them to trigger status transitions instead of sending `status` via the update endpoint.**

### Public CMS (unauthenticated)

| Action | URL | Notes |
|:---|:---|:---|
| Get page by slug | `GET /api/v1/public/cms/pages/{slug}` | Unchanged — no action needed |

---

## Constraint

> DO NOT rename routes yet. This document is a plan only.  
> No frontend usage is switched. No route names are changed.  
> All aliases are future-safe preparations, not active changes.

---

## Current Route Inventory

### Platform Admin Routes

| Method | Current URI | Route Name | Controller | Status |
|:---|:---|:---|:---|:---|
| GET | `/api/v1/platform/cms/pages` | `platform.cms.pages.index` | `AdminMarketingPageController` | Deprecated |
| POST | `/api/v1/platform/cms/pages` | `platform.cms.pages.store` | `AdminMarketingPageController` | Deprecated |
| GET | `/api/v1/platform/cms/pages/{id}` | `platform.cms.pages.show` | `AdminMarketingPageController` | Deprecated |
| PUT | `/api/v1/platform/cms/pages/{id}` | `platform.cms.pages.update` | `AdminMarketingPageController` | Deprecated |
| DELETE | `/api/v1/platform/cms/pages/{id}` | `platform.cms.pages.destroy` | `AdminMarketingPageController` | Deprecated |
| POST | `/api/v1/platform/cms/pages/{id}/publish` | `platform.cms.pages.publish` | `AdminMarketingPageController` | Deprecated |
| GET | `/api/v1/platform/cms/pages/platform` | `platform.cms.pages.platform.index` | `AdminPlatformMarketingPageController` | Active |
| POST | `/api/v1/platform/cms/pages/platform` | `platform.cms.pages.platform.store` | `AdminPlatformMarketingPageController` | Active |
| GET | `/api/v1/platform/cms/pages/platform/{id}` | `platform.cms.pages.platform.show` | `AdminPlatformMarketingPageController` | Active |
| PUT | `/api/v1/platform/cms/pages/platform/{id}` | `platform.cms.pages.platform.update` | `AdminPlatformMarketingPageController` | Active |
| DELETE | `/api/v1/platform/cms/pages/platform/{id}` | `platform.cms.pages.platform.destroy` | `AdminPlatformMarketingPageController` | Active |
| POST | `/api/v1/platform/cms/pages/platform/{id}/publish` | `platform.cms.pages.platform.publish` | `AdminPlatformMarketingPageController` | Active |

### Merchant Admin Routes

| Method | Current URI | Route Name | Controller | Status |
|:---|:---|:---|:---|:---|
| GET | `/api/v1/merchant/stores/{store}/cms/pages` | `merchant.cms.pages.index` | `AdminStoreMarketingPageController` | Active |
| POST | `/api/v1/merchant/stores/{store}/cms/pages` | `merchant.cms.pages.store` | `AdminStoreMarketingPageController` | Active |
| GET | `/api/v1/merchant/stores/{store}/cms/pages/{id}` | `merchant.cms.pages.show` | `AdminStoreMarketingPageController` | Active |
| PUT | `/api/v1/merchant/stores/{store}/cms/pages/{id}` | `merchant.cms.pages.update` | `AdminStoreMarketingPageController` | Active |
| DELETE | `/api/v1/merchant/stores/{store}/cms/pages/{id}` | `merchant.cms.pages.destroy` | `AdminStoreMarketingPageController` | Active |
| POST | `/api/v1/merchant/stores/{store}/cms/pages/{id}/publish` | `merchant.cms.pages.publish` | `AdminStoreMarketingPageController` | Active |
| POST | `/api/v1/merchant/stores/{store}/cms/pages/{id}/unpublish` | `merchant.cms.pages.unpublish` | `AdminStoreMarketingPageController` | Active |

### Public Routes

| Method | Current URI | Route Name | Controller | Status |
|:---|:---|:---|:---|:---|
| GET | `/api/v1/public/cms/pages/{slug}` | `public.cms.pages.show` | `PublicMarketingController` | Active |

---

## Target Route Structure

The architecture document recommends flattening the nested `/cms/pages/platform` to `/cms/marketing-pages`. This is the future target — not yet implemented.

### Platform Admin Target

| Method | Target URI | Target Route Name |
|:---|:---|:---|
| GET | `/api/v1/platform/cms/marketing-pages` | `platform.cms.marketing-pages.index` |
| POST | `/api/v1/platform/cms/marketing-pages` | `platform.cms.marketing-pages.store` |
| GET | `/api/v1/platform/cms/marketing-pages/{id}` | `platform.cms.marketing-pages.show` |
| PUT | `/api/v1/platform/cms/marketing-pages/{id}` | `platform.cms.marketing-pages.update` |
| DELETE | `/api/v1/platform/cms/marketing-pages/{id}` | `platform.cms.marketing-pages.destroy` |
| POST | `/api/v1/platform/cms/marketing-pages/{id}/publish` | `platform.cms.marketing-pages.publish` |

### Merchant Admin Target

| Method | Target URI | Target Route Name |
|:---|:---|:---|
| GET | `/api/v1/merchant/stores/{store}/cms/marketing-pages` | `merchant.cms.marketing-pages.index` |
| POST | `/api/v1/merchant/stores/{store}/cms/marketing-pages` | `merchant.cms.marketing-pages.store` |
| GET | `/api/v1/merchant/stores/{store}/cms/marketing-pages/{id}` | `merchant.cms.marketing-pages.show` |
| PUT | `/api/v1/merchant/stores/{store}/cms/marketing-pages/{id}` | `merchant.cms.marketing-pages.update` |
| DELETE | `/api/v1/merchant/stores/{store}/cms/marketing-pages/{id}` | `merchant.cms.marketing-pages.destroy` |
| POST | `/api/v1/merchant/stores/{store}/cms/marketing-pages/{id}/publish` | `merchant.cms.marketing-pages.publish` |
| POST | `/api/v1/merchant/stores/{store}/cms/marketing-pages/{id}/unpublish` | `merchant.cms.marketing-pages.unpublish` |

---

## Alias Strategy

When the time comes to rename routes, the migration should use a **parallel alias** approach:

1. Register the new URI alongside the old URI, pointing to the same controller.
2. Mark the old URI as deprecated (via response header or documentation).
3. Monitor old URI usage until it drops to zero.
4. Remove the old URI.

Example implementation (future, not yet applied):

```php
// In marketing-pages.php — FUTURE ONLY, do not apply yet

// New canonical routes
Route::prefix('cms/marketing-pages')
    ->name('platform.cms.marketing-pages.')
    ->controller(AdminPlatformMarketingPageController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::post('/{id}/publish', 'publish')->name('publish');
    });

// Deprecated aliases — kept until frontend migrates
Route::prefix('cms/pages/platform')
    ->name('platform.cms.pages.platform.')  // preserve existing route names
    ->controller(AdminPlatformMarketingPageController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        // ... same routes, same controller, deprecated header middleware
    });
```

---

## Deprecation Strategy

### Step 1 — Add deprecation middleware (future)

Create a `DeprecatedRoute` middleware that adds a `Deprecation` response header:

```php
// Future middleware — not yet created
class DeprecatedRoute
{
    public function handle(Request $request, Closure $next, string $replacement = ''): Response
    {
        $response = $next($request);
        $response->headers->set('Deprecation', 'true');
        if ($replacement) {
            $response->headers->set('Link', "<{$replacement}>; rel=\"successor-version\"");
        }
        return $response;
    }
}
```

### Step 2 — Monitor usage (future)

Add telemetry to deprecated routes to track usage before removal:

```php
Route::get('/{id}', 'show')
    ->name('show')
    ->middleware('deprecated.route:platform.cms.pages.platform.show');
```

### Step 3 — Remove (future, after usage = 0)

Remove deprecated route declarations from `marketing-pages.php`.

---

## Frontend Migration Strategy

### Current frontend usage (assumed)

The frontend currently uses:
- `/api/v1/platform/cms/pages/platform` — platform CMS admin
- `/api/v1/merchant/stores/{store}/cms/pages` — merchant CMS admin

### Migration steps (future)

1. Frontend adds support for new URIs alongside old URIs (feature flag).
2. New URIs are tested in staging.
3. Feature flag enabled in production — new URIs used by default.
4. Old URI usage monitored for 30 days.
5. Old URI support removed from frontend.
6. Old routes removed from backend.

### Do NOT switch frontend yet

The frontend should continue using current URIs until:
- New URIs are registered as aliases (Step 1 above)
- Staging validation is complete
- A coordinated deployment window is scheduled

---

## Impact Analysis

### Impact on Policies

Route renaming has **zero impact** on policies. Policies are bound to model classes, not route names. `StoreMarketingPagePolicy` and `PlatformMarketingPagePolicy` will continue to work unchanged.

### Impact on Tests

Tests use hardcoded URL strings (e.g., `"/api/v1/merchant/stores/{$store->id}/cms/pages"`). When routes are renamed, tests must be updated to use the new URIs. The `baseUrl()` helper in `StoreMarketingPageTest` makes this a single-line change per test file.

### Impact on Swagger/Scramble Docs

Scramble auto-generates docs from route definitions. Adding new routes will add new entries. Removing old routes will remove their entries. During the alias period, both old and new routes will appear in docs — mark deprecated routes with `@deprecated` in controller docblocks.

### Impact on Route Caching

`php artisan route:cache` must be re-run after any route changes. During the alias period, the cache will contain both old and new routes — this is fine and expected.

### Impact on Middleware Resolution

All new routes will inherit the same middleware stack as the current routes (they are in the same prefix group). No middleware changes are needed.

### Impact on Mobile Clients

If mobile clients consume the platform admin API (unlikely — platform admin is super_admin only), they must be updated before old routes are removed. Audit mobile client API usage before proceeding.

### Impact on Route Names

Route names are used in `route()` helper calls and in test assertions. A search for `platform.cms.pages.platform` and `merchant.cms.pages` across the codebase will identify all usages that need updating.

```bash
# Find all route name usages
grep -r "platform\.cms\.pages" app/ routes/ tests/ --include="*.php"
grep -r "merchant\.cms\.pages" app/ routes/ tests/ --include="*.php"
```

---

## Rollback Strategy

If a route migration causes issues:

1. **Immediate rollback:** Re-add the old route declaration. Since aliases are additive, removing the new route restores the old behavior instantly.
2. **Route cache:** Run `php artisan route:cache` after rollback.
3. **No data migration needed:** Route changes do not affect database state.
4. **Zero downtime:** Route changes take effect on next request after cache clear.

---

## Recommended Next Phase

Before executing this route migration plan:

1. Complete the legacy data migration (Priority 3 prerequisites).
2. Confirm frontend is ready to support parallel URIs.
3. Add the `DeprecatedRoute` middleware.
4. Register new URIs as aliases (additive — no breaking change).
5. Monitor for 30 days.
6. Remove old URIs.

**Estimated effort:** 1 sprint (2 weeks) for alias registration + monitoring. 1 additional sprint for removal after monitoring period.
