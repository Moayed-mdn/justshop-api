# Marketing Pages Architecture — Implementation Summary

**Date:** 2026-05-27  
**Task:** Stabilize and complete the marketing pages architecture  
**Status:** ✅ All four priorities complete

---

## What Was Done

### Priority 1 — Merchant Marketing CMS (Complete)

The merchant marketing pages system was architecturally correct and operationally complete. All components were verified present and production-grade:

**FormRequest validation** — both `CreateStoreMarketingPageRequest` and `UpdateStoreMarketingPageRequest` implement:
- Localized title/slug validation with regex enforcement
- Store-scoped slug uniqueness (per locale, per store, self-excluding on update)
- Valid status enum enforcement
- Store-only template enum enforcement (platform templates rejected)
- Full SEO structure validation
- Section array validation with `section_type`/`type` alias support
- Scheduled publishing validation (future date required)
- Nullable content support

**API Resources** — `AdminStoreMarketingPageResource` and `StoreMarketingSectionResource` provide a stable, explicit frontend contract matching the platform resource style.

**Publish/unpublish workflow** — dedicated routes, actions, and FormRequest:
- `POST /merchant/stores/{store}/cms/pages/{id}/publish`
- `POST /merchant/stores/{store}/cms/pages/{id}/unpublish`
- Status transition validation (rejects double-publish, rejects unpublish of draft)
- Authorization checks via policy + FormRequest
- DB transaction safety
- Future ISR/cache hook points documented in code

**Actions layer** — five dedicated actions with thin controller, business logic isolation, transaction safety:
- `CreateStoreMarketingPageAction`
- `UpdateStoreMarketingPageAction`
- `DeleteStoreMarketingPageAction`
- `PublishStoreMarketingPageAction`
- `UnpublishStoreMarketingPageAction`

**Tests** — 27 feature tests covering all required scenarios. Tests were failing due to a permission resolution mismatch between `givePermissionTo()` (direct user permissions) and `LegacyPermissionAuthority` (role-based resolution). Fixed by updating the test helper to create custom roles with exactly the requested permissions and updating the store pivot role accordingly.

**Test results:** 27/27 passing ✅

---

### Priority 2 — Unsafe Request Handling (Complete)

Full audit of the merchant CMS marketing domain. No unsafe patterns found in current code. All issues documented in the architecture doc had been resolved prior to this pass.

See: `docs/reports/MERCHANT_CMS_SECURITY_AUDIT.md`

---

### Priority 3 — Legacy Migration Stabilization (Complete)

**Route collision audit:** Verified safe. `GET /platform/cms/pages/platform` correctly resolves to the new controller's index, not the legacy show route. Route ordering is documented and must be preserved.

**Cache service decoupling:** `MarketingPageCacheService` already has a model-agnostic `invalidateForSlugMap()` path. Legacy `invalidateForPage()` preserved for backward compatibility.

**`MarketingPageResource` instanceof branching:** Already replaced with duck-typed attribute access. No concrete model imports remain.

**Migration documentation:** Full dependency inventory, removal prerequisites checklist, and phased removal strategy documented.

See: `docs/migrations/LEGACY_MARKETING_PAGES_MIGRATION.md`

---

### Priority 4 — Future Route Cleanup (Complete)

Route migration plan documented with:
- Full current route inventory
- Target route structure
- Alias strategy (additive, non-breaking)
- Deprecation strategy (response headers + telemetry)
- Frontend migration strategy
- Full impact analysis (policies, tests, Swagger, mobile, route cache, middleware)
- Rollback strategy

See: `docs/plans/ROUTE_MIGRATION_PLAN.md`

---

## Files Changed

| File | Change | Reason |
|:---|:---|:---|
| `tests/Feature/Cms/Marketing/StoreMarketingPageTest.php` | Fixed `givePermissions()` helper | `LegacyPermissionAuthority` resolves from pivot role, not direct user permissions |
| `docs/reports/MERCHANT_CMS_SECURITY_AUDIT.md` | Created | Priority 2 deliverable |
| `docs/migrations/LEGACY_MARKETING_PAGES_MIGRATION.md` | Created | Priority 3 deliverable |
| `docs/plans/ROUTE_MIGRATION_PLAN.md` | Created | Priority 4 deliverable |
| `docs/MARKETING_PAGES_IMPLEMENTATION_SUMMARY.md` | Created | This file |

---

## Architectural Reasoning

### Why the test fix was necessary

The `LegacyPermissionAuthority` resolves permissions from the user's store pivot role, not from direct `givePermissionTo()` assignments. This is by design — the system uses role-based access control scoped to store membership. The test helper was using `givePermissionTo()` which bypasses this resolution path when `currentStore` is bound in the container.

The fix creates a custom role with exactly the requested permissions and updates the pivot role. This correctly exercises the production permission resolution path and preserves fine-grained permission testing (the `test_publish_requires_publish_permission` test still correctly denies publish when only create+update permissions are granted).

### Why no code was changed in the production domain

The merchant CMS marketing domain was already complete. The architecture document described the state of the system at an earlier point in time. All four priorities had been implemented in a prior pass. This task's work was:
1. Verifying the implementation is correct and complete
2. Fixing the test infrastructure to correctly exercise the production permission system
3. Producing the required documentation deliverables

---

## Unresolved Risks

| Risk | Severity | Owner |
|:---|:---|:---|
| Legacy `marketing_pages` data not yet migrated to `platform_marketing_pages` | Medium | Platform team |
| Legacy admin routes still active and accessible | Low | Deferred — documented in migration plan |
| No event dispatching on store page publish/unpublish | Low | Deferred — noted in action code |
| No cache invalidation on store page publish | Low | Deferred — public store CMS endpoint not yet active |
| `MarketingPageResource::remember()` still returns legacy `MarketingPage` type | Low | Deferred — only used by legacy fallback path |

---

## Deferred Improvements

1. **Store public CMS endpoint** — `GET /api/v1/public/stores/{store}/cms/pages/{slug}` is planned but not yet implemented. When activated, add ISR revalidation and cache invalidation to `PublishStoreMarketingPageAction`.

2. **Domain events** — Add `StoreMarketingPagePublished` and `StoreMarketingPageUnpublished` events for cache, webhooks, and analytics listeners.

3. **Partial section updates** — `syncSections()` is a full replace. Future improvement: support patch-style section updates to avoid re-sending unchanged sections.

4. **Route aliases** — Register new `/cms/marketing-pages` URIs as non-breaking aliases per the route migration plan.

5. **Legacy data migration** — Migrate all `marketing_pages` rows to `platform_marketing_pages` to enable legacy system removal.

---

## Recommended Next Phase

1. **Immediate:** Run `php artisan test tests/Feature/Cms/` in CI to confirm green baseline.
2. **Sprint 1:** Migrate legacy `marketing_pages` data to `platform_marketing_pages`. Verify `PublicMarketingController` resolves all pages from new table.
3. **Sprint 2:** Register new route aliases (`/cms/marketing-pages`). Add deprecation headers to old routes. Notify frontend team.
4. **Sprint 3:** Frontend migrates to new URIs. Monitor old URI usage.
5. **Sprint 4:** Remove legacy routes, controller, actions, model, and table after 30-day zero-usage confirmation.

---

## Production Rollout Checklist

Before deploying any changes from this task:

- [x] All 27 `StoreMarketingPageTest` tests pass
- [x] No regressions in `tests/Feature/Cms/` suite
- [x] No changes to production route names
- [x] No changes to production middleware
- [x] No changes to production models
- [x] No changes to production controllers
- [x] No changes to production policies
- [x] No breaking changes to API response contracts
- [x] Documentation deliverables created
- [ ] CI pipeline green (run full test suite in CI environment)
- [ ] Code review approved
- [ ] Staging deployment verified
