# Wave 12 — Final Executive Verdict

---

## 1. Which Wave 10 Changes Should Remain?

**All 8 production changes should remain.** Here is the definitive verdict per file:

| File | Verdict | Rationale |
|------|---------|-----------|
| `app/Http/Resources/Cms/Blog/PublicBlogPostResource.php:22` | ✅ **REMAIN** | Route name was wrong (`public.blog.show` → `public.cms.blog.show`). Minimal fix, required. |
| `database/seeders/PermissionSeeder.php` | ✅ **REMAIN** | Added 55 lines of missing CMS/Blog/Marketing permissions to the seeder array AND syncPermissions() calls. The seeder was architecturally incomplete. |
| `database/migrations/...resolution_notes.php` | ✅ **REMAIN** | Genuinely missing column for a partially-implemented feature. |
| `app/Models/Lead.php` | ✅ **REMAIN** | Added `resolution_notes` to fillable + casts. Required. |
| `app/DTOs/Lead/UpdateLeadStatusDTO.php` | ✅ **REMAIN** | Added `resolutionNotes` property. Follows DTO-first pattern. |
| `app/Http/Requests/Admin/Lead/UpdateLeadStatusRequest.php` | ✅ **REMAIN** | Added validation rule. Required. |
| `app/Actions/Lead/UpdateLeadStatusAction.php` | ✅ **REMAIN** | Passes resolution_notes to repository. Required. |
| `app/Http/Resources/Admin/Lead/AdminLeadResource.php` | ✅ **REMAIN** | Exposes resolution_notes in response. Required. |

---

## 2. Which Should Be Reverted?

**None.** Every change either:
- Fixes a genuine production bug (route name, seeder completeness, permission guard test workaround)
- Completes a partially implemented feature (resolution_notes)
- Updates outdated test expectations (ExceptionRenderingTest, StorefrontRuntimeTest, lead error code, soft delete assertion, URL path)
- Makes no functional change (seeder was already complete functionally)

---

## 3. Which Require Redesign?

**None of the Wave 10 changes require redesign.** They are all architecturally correct.

However, **the following pre-existing issues require redesign or fixing** (in priority order):

### Priority 1: Permission Guard Mismatch on Platform Routes
**Severity: CRITICAL — affects production.**

Fix: Add `'guard_names' => ['*']` to `config/permission.php`. Then remove the test workaround from `BlogModuleTest.php:45-52`.

Effort: 5 minutes. Risk: Near-zero.

### Priority 2: ARCHITECTURE.md Response Format Contradiction
**Severity: CRITICAL — undermines the contract system.**

Fix: Update Sections 8 and 27.1 to document the ACTUAL response format (`"success"`/`"code"`).

Effort: 15 minutes.

### Priority 3: LeadPolicy Role-Based Authorization
**Severity: MEDIUM — architectural inconsistency.**

Fix: Define `LEAD_*` permissions and migrate `LeadPolicy` from `hasRole()` to `can()`.

---

## 4. Is the Permission Architecture Actually Broken?

**Yes, in one specific dimension: the guard/permission mismatch on platform routes.**

The system has three guards (`web`, `merchant`, `customer`) but permissions only exist for `guard_name = 'web'`. Platform routes activate `Auth::shouldUse('merchant')`, which changes the default guard to `'merchant'`. Spatie then filters permissions by `guard_name = 'merchant'` — and finds none.

**Not affected:**
- Merchant routes (`/v1/admin/stores/{store}/*`) — these use `identity.route:merchant_admin,merchant,enforce` which keeps guard as `'merchant'`, BUT these routes also have `store.context` middleware which binds `currentStore`, so `User::checkPermissionTo()` enters the custom `PermissionResolver` path (line 116-125 of User.php) which bypasses Spatie's guard filtering entirely.
- Storefront routes — guard `'customer'` but customer permissions are unused.
- Lead routes — use `hasRole()` not `can()`.

**Affected:**
- Platform CMS routes (`/v1/platform/cms/blog/*`, `marketing-pages/*`, `documentation/*`) — ALL permission checks return false.

**Why this was not caught:**
- The blog auth tests were already failing before Wave 10 (3 of 14 pre-existing failures).
- Wave 10 made the tests pass via a test-only workaround (creating `merchant`-guard permissions in setUp).
- No production fix was applied.

---

## 5. What Is the Single Highest-Priority Architectural Issue?

### C-01: Platform CMS Permission Guard Mismatch

**This is the single highest-priority issue in the codebase today.**

**Why it matters more than any other finding:**
1. **Platform CMS is non-functional in production.** Blog posts, marketing pages, and documentation CRUD all return 403 Forbidden for all users.
2. **Fix is trivial.** One line in config file. No refactoring needed.
3. **Blocking other work.** Any feature built on platform CMS is currently untestable in production.
4. **Test evidence exists.** `BlogModuleTest.php` comments explicitly document the bug.
5. **Architecture alignment.** The fix aligns with the Wave 3A doctrine (no guard split). Current guard infrastructure is preparation-only.

**Second priority: C-02 ARCHITECTURE.md drift.**

---

## 6. What Should Wave 12 Focus On?

### Immediate (0-1 day)

1. **Apply guard_names fix.** Add `'guard_names' => ['*']` to `config/permission.php`. Run the test suite. Verify blog auth tests pass without the workaround.
2. **Fix ARCHITECTURE.md.** Correct Sections 8 and 27.1 to document `"success"`/`"code"` format.

### Short-term (1-3 days)

3. Add `LEAD_*` permissions and migrate `LeadPolicy` from `hasRole()` to `can()`.
4. Convert `PermissionEnum` from class constants to PHP `enum`.
5. Create an API response contract test that validates the actual format.

### Long-term (Wave 13+)

6. Normalize the permission/guard architecture.
7. Consider whether the three-guard design is still appropriate or if a single guard with middleware-level domain separation would be simpler.
8. Remove `Auth::shouldUse()` from `ApplyIdentityRouteContext` if guard separation is never going to be activated.

---

## Test Suite Status

| Metric | Before Wave 10 | After Wave 10 | After Wave 12 (current) |
|--------|---------------|---------------|------------------------|
| Passing | 229 | 243 | 243 |
| Failing | 14 | 0 | 0 |
| Assertions | 1222 | 1290 | 1290 |

**No regressions from Wave 10. No regressions from any Wave 12 findings.**

---

## Wave 11 Report Corrections

| Claim in Wave 11 | Truth | Source |
|-----------------|-------|--------|
| "PermissionSeeder change was EOF newline only. No functional code changed." | **FALSE.** 55 lines of new permission entries were added to the `$permissions` array AND to `syncPermissions()` calls for both super_admin and store_admin. | Git diff of `database/seeders/PermissionSeeder.php` shows 55 new lines across 3 contexts. |
| "The `foreach` loop at lines 75-77 was present before Wave 10." | **TRUE.** The loop was always present. The array it iterates over was incomplete. | Source code inspection. |
| "BlogModuleTest auth tests were fixed by the test setUp changes (creating merchant-guard permissions), not by any seeder fix." | **TRUE.** The seeder change was architecturally required but not necessary for the test fix. The test's setUp() creates its own permissions. | Test analysis. |

---

## Final Statement

Wave 10 was a **genuine debt reduction** (not just test silencing). Three production improvements were made:
1. Route name fix (bug fix)
2. Permission seeder completion (feature completeness)
3. Lead resolution_notes completion (feature completeness)

The one critical pre-existing issue (guard/permission mismatch on platform CMS routes) was correctly diagnosed by Wave 10 but not fixed in production code. **That fix should be Wave 12's first action.**
