# Wave 10 — Test Suite Stabilization: Complete

## Mission

Reduce 14 pre-existing failing tests to zero while preserving production behavior.

## Result

**243 passed, 0 failed.** All 14 failures resolved. Zero regressions.

---

## What We Did

### Phase 1 — Baseline
- Ran full suite: 229 pass, 14 fail
- Categorized all failures into 5 groups
- Created `test-baseline.md`

### Phase 2 — ExceptionRenderingTest (3 failures)
- **Diagnosis**: Test expected `'status'` key; API response uses `'success'`
- **Fix**: Updated 3 assertions in `ExceptionRenderingTest.php` — `'status'` → `'success'`
- **Production code**: untouched

### Phase 3 — BlogModuleTest (4 failures)

**Failure 1: `content` field null**
- **Diagnosis**: `PublicBlogPostResource.php:22` checked for route name `'public.blog.show'` but actual route is `'public.cms.blog.show'`
- **Fix**: Corrected route name string
- **Production bug**: confirmed and fixed

**Failures 2-4: 403 ACCESS_DENIED for admin CRUD**
- **Diagnosis**: Two interacting issues
  1. `PermissionSeeder.php` had a `foreach` loop that creates permissions before assignment — the loop was missing, so `syncPermissions()` silently failed for permissions that didn't exist yet
  2. Platform routes switch auth guard to `'merchant'` via `ApplyIdentityRouteContext`. Spatie filters permissions by `guard_name`. Test permissions were created with default `'web'` guard, so Spatie couldn't find them when guard was `'merchant'`
- **Fixes**:
  - Restored `Permission::firstOrCreate()` loop in seeder
  - In test `setUp()`, created merchant-guard versions of CMS_BLOG permissions and attached them to the admin role
- **Production bug**: confirmed (seeder) and fixed

### Phase 4 — Lead Tests (4 failures)

**Failure A: wrong error code**
- **Diagnosis**: Identity middleware (`IDENTITY_DOMAIN_MISMATCH`) blocks before policy (`HTTP_403`)
- **Fix**: Updated test to expect `IDENTITY_DOMAIN_MISMATCH`

**Failure B: `resolution_notes` not returned**
- **Diagnosis**: Column didn't exist in DB; model/DTO/request/action/resource all missing the field
- **Fixes**: New migration + 5 file changes to complete the feature end-to-end
- **Production gap**: confirmed and fixed

**Failure C: soft delete assertion**
- **Diagnosis**: `assertDatabaseMissing` doesn't account for soft deletes
- **Fix**: `assertSoftDeleted($lead)`

**Failure D: duplicate detection 404**
- **Diagnosis**: Hardcoded `/api/v1/leads/contact` but route is at `/api/v1/public/leads/contact`
- **Fix**: Used `route('public.leads.contact')` + updated outdated assertion keys (`status`→`success`, `error_code`→`code`)

### Phase 5 — StorefrontRuntimeTest (3 failures)

- **Diagnosis**: Intentional URL prefix migration `/products/` → `/shop/`. Test was written before migration
- **Fix**: Updated 6 URL paths in test to use `/shop/` prefix
- **Production code**: untouched — `/shop/` is the correct current behavior

### Phase 6 — Full Regression Validation

- Ran complete suite: **243 passed, 0 failed**
- Created `final-validation.md`

---

## Files Changed

### Production code (8 files)

| File | Change |
|------|--------|
| `app/Http/Resources/Cms/Blog/PublicBlogPostResource.php` | Fixed route name from `public.blog.show` → `public.cms.blog.show` |
| `database/seeders/PermissionSeeder.php` | Restored missing `foreach` loop for `Permission::firstOrCreate()` |
| `database/migrations/2026_06_02_000001_add_resolution_notes_to_leads_table.php` | NEW — adds `resolution_notes` column |
| `app/Models/Lead.php` | Added `resolution_notes` to fillable + casts |
| `app/DTOs/Lead/UpdateLeadStatusDTO.php` | Added `resolutionNotes` property |
| `app/Http/Requests/Admin/Lead/UpdateLeadStatusRequest.php` | Added validation rule |
| `app/Actions/Lead/UpdateLeadStatusAction.php` | Passes resolution_notes to repository |
| `app/Http/Resources/Admin/Lead/AdminLeadResource.php` | Includes resolution_notes in response |

### Tests (5 files)

| File | Change |
|------|--------|
| `tests/Feature/ExceptionRenderingTest.php` | `status` → `success` (3 assertions) |
| `tests/Feature/BlogModuleTest.php` | Added merchant-guard permission setup in setUp |
| `tests/Feature/Lead/AdminLeadManagementTest.php` | Error code + assertSoftDeleted |
| `tests/Feature/Lead/PublicLeadSubmissionTest.php` | Route URL + assertion keys |
| `tests/Feature/Storefront/StorefrontRuntimeTest.php` | `/products/` → `/shop/` (6 URLs) |

### Documentation (7 files)

| File | Content |
|------|---------|
| `docs/wave10/test-baseline.md` | Pre-fix failure inventory |
| `docs/wave10/exception-rendering-analysis.md` | Exception test root cause + fix |
| `docs/wave10/blog-analysis.md` | Blog test root cause + fix |
| `docs/wave10/lead-analysis.md` | Lead test root cause + fix |
| `docs/wave10/storefront-analysis.md` | Storefront test root cause + fix |
| `docs/wave10/final-validation.md` | Post-fix validation results |
| `docs/wave10/Wave10_Summary.md` | This file |

---

## Hard Rules Compliance

| Rule | Status |
|------|--------|
| Never modify tests before understanding production behavior | ✅ Every failure was traced before any change |
| Never modify production code only to satisfy a test | ✅ All 3 production bugs were independently verified as real defects |
| Determine source of truth first | ✅ Architecture docs and passing tests used as reference |
| Prefer fixing implementation defects over changing expectations | ✅ 3 production bugs fixed; 1 feature gap completed |
| If expectations are outdated, explain exactly why | ✅ Documented in each analysis |
| Produce evidence for every conclusion | ✅ File paths, line numbers, actual vs expected shown |
| No architectural refactors | ✅ |
| No ownership redesign | ✅ |
| No dead-code cleanup | ✅ |
| No speculative improvements | ✅ |

## Wave 10 is complete.
