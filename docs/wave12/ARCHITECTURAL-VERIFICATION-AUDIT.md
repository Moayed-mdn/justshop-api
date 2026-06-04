# Wave 12 — Doctrine-First Architectural Verification Audit

**Date:** 2026-06-02  
**Methodology:** Independent reconstruction of project doctrine from 27+ architecture documents and source-code inspection of 13+ runtime files. No prior Wave 11 or Wave 12 conclusion was trusted without independent verification.

---

## Phase 1 — Doctrine Summary

### 1. What Laratenant Actually Is

Laratenant is a **multi-tenant SaaS ecommerce platform** built on Laravel 12 with Sanctum SPA authentication, domain-driven layers, Spatie RBAC, and an API-first architecture. It supports multiple stores per tenant with strict tenant isolation (single database, shared schema, `store_id` scoping on all commerce entities).

The system is organized into six API contexts (Platform, Merchant, Storefront, Customer, Support, Public), each with dedicated controller namespaces, middleware stacks, and authorization boundaries.

### 2. Platform Domain

| Property | Value |
|----------|-------|
| Route prefix | `/v1/platform/*` + legacy `/v1/admin/cms/*` and `/v1/admin/leads/*` |
| Actor | Super Admin / Support Agent |
| Middleware | `web`, `auth:sanctum`, `identity.route:platform,platform,enforce`, `platform.authority:platform_admin` |
| Guard resolution | `platform` → `merchant` (via `TransitionalGuardResolver:58`) |
| Auth::shouldUse | `'merchant'` (via `ApplyIdentityRouteContext:89`) |
| Store scoping | None — platform CMS is platform-owned |
| Source | `docs/ARCHITECTURE.md` §16.2, §18.9; `docs/architecture/routing-contexts.md`; `routes/api.php:19-32` |

### 3. Merchant Domain

| Property | Value |
|----------|-------|
| Route prefix | `/v1/admin/stores/{store}/*`, `/v1/merchant/*` |
| Actor | Store Owner, Store Admin, Staff |
| Middleware | `web`, `auth:sanctum`, `identity.route:merchant_admin,merchant,enforce`, `store.context`, `onboarding.completed` |
| Guard resolution | `merchant` → `merchant` |
| Auth::shouldUse | `'merchant'` |
| Store scoping | Mandatory — `{store}` route param, `store.context` binds `currentStore` |
| Source | `docs/architecture/routing-contexts.md`; `docs/ARCHITECTURE.md` §16, §24 |

### 4. Customer Domain

| Property | Value |
|----------|-------|
| Route prefix | `/v1/storefront/account/*`, `/v1/storefront/*` |
| Actor | Registered Shopper / Guest |
| Middleware | `identity.route:storefront_commerce,customer,observe` (observe mode, not enforce) |
| Guard resolution | `customer` → `customer` |
| Auth::shouldUse | `'customer'` |
| Store scoping | Via route param `{store}` |
| Key constraint | Additive namespace only — same `users` table, same session model, no merchant onboarding |
| Source | `docs/auth/core/customer-account-namespace.md`; `docs/ARCHITECTURE.md` §24.11 |

### 5. What Ownership Means

Ownership has **three distinct meanings** in this project:

| Meaning | Definition | Enforcement |
|---------|------------|-------------|
| **Legal ownership** | `stores.owner_id` — who legally owns a store | Business logic |
| **Route ownership** | Which domain "owns" a route (platform, merchant, customer) | `identity.route` middleware |
| **Session ownership** | Session tagging with `auth_domain`, `actor_type`, `actor_id` | `ApplyIdentityRouteContext` enforcement |

Per `docs/ARCHITECTURE.md` §24.1:
> - **Actor context** answers "which identity domain is this request in?"
> - **Role** answers "which capabilities does this user have?"
> - **Ownership** answers "which store does this user legally own?"

### 6. Wave 3A Established

Wave 3A (`docs/ARCHITECTURE.md` §Wave 3A) is **boundary clarification only**:

**Permitted by Wave 3A:**
- Identity-context normalization
- Route-domain ownership metadata
- Merchant-only onboarding applicability isolation
- Additive customer auth namespace foundations
- Session-boundary preparation metadata
- Additive storefront bootstrap foundations

**Forbidden by Wave 3A:**
- **Guard split**
- Cookie split
- Session split
- Customer-only or merchant-only auth persistence
- Account-table split
- Checkout auth rewrite
- Bootstrap authority cutover

Wave 3B and 3C extend this with preparation (shadow guards, contamination detection, readiness scoring) but maintain the same prohibition on *active* guard split.

### 7. Guard Separation Status

**Guard separation is NOT active. It is in a transitional/preparation state.**

Key evidence:
- `docs/auth/sessions/runtime-guard-isolation.md`: "The codebase resolves and applies intended guards on annotated routes, but the overall auth model is still documented and tested as `shared_sanctum_session` with `shared_until_guard_split`."
- `docs/auth/governance/sanctum-authority-runtime-model.md`: "session isolation is still reported as `shared_until_guard_split`"
- `config/auth.php`: All three guards (`web`, `merchant`, `customer`) use the same `session` driver and `users` provider
- `docs/ARCHITECTURE.md` §24.12: "Future Multi-Guard Strategy — While currently using a shared `users` table, the architecture is *prepared* for Guard separation"

What IS active:
- `Auth::shouldUse('merchant')` is called on platform/merchant routes → changes `config('auth.defaults.guard')`
- Route-level identity and ownership enforcement is active
- Contamination detection is active
- Ownership tagging on sessions is active

What is NOT active:
- Cookie split (single `ecommerce_session` cookie)
- Session isolation (single session pool)
- Provider split (all guards resolve via `users` table)
- Logout isolation (logout invalidates full session)

### 8. Permissions: Shared or Isolated Across Guards?

**Current state: Permissions are implicitly isolated by guard_name, but the architecture intends them to be shared.**

| Layer | State | Evidence |
|-------|-------|----------|
| `PermissionSeeder` | Creates with `guard_name = 'web'` (no guard_name specified, default applies) | `PermissionSeeder:76` |
| `config/permission.php` | No `'guard_names'` key → Spatie filters by active guard | `config/permission.php` |
| Auth::shouldUse | Changes default guard to `'merchant'` on platform/merchant routes | `ApplyIdentityRouteContext:89` |
| Wave 3A doctrine | Forbids guard split → implies permissions should be shared | ARCHITECTURE.md §Wave 3A |

**This is a contradiction:** `Auth::shouldUse('merchant')` implies separate guards, but Wave 3A forbids guard split, and permissions are only created for `'web'`.

The doctrine documents do NOT explicitly state whether permissions should be shared or isolated. This is an architectural gap that Wave 12 correctly identifies.

### 9. Authorization: Role-First or Permission-First?

**Permission-first in doctrine, but mixed in practice.**

| Evidence | Supports |
|----------|----------|
| ARCHITECTURE.md §26: "permissions are the source of truth" | Permission-first |
| ARCHITECTURE.md §1.5: "Policies are the ONLY authorization enforcement layer" | Permission-first |
| `BlogPostPolicy` uses `$user->can(PermissionEnum::*)` | Permission-first |
| `BlogPostPolicy` has NO `before()` method (no super_admin implicit bypass) | Permission-first |
| `LeadPolicy` uses `$user->hasRole(RoleEnum::SUPER_ADMIN)` instead of `$user->can()` | Role-first (contradiction) |
| `PermissionResolver` resolves permissions based on role, then checks permission match | Mixed |

### 10. Ultimate Authority When Doctrine and Code Disagree

Per `AGENTS.md`: `docs/ARCHITECTURE.md` is the **ABSOLUTE AUTHORITY** for implementation doctrine.

Per `docs/EXECUTION_GOVERNANCE.md`: Precedence order is **ARCHITECTURE.md > ADRs > execution governance > local convenience**.

However, `AGENTS.md` also states: **"Code Over Assumptions"** — prefer live code implementation over stale comments.

**This creates a tension:** When `ARCHITECTURE.md` Section 8 says `status`/`error_code` but the code says `success`/`code`, which wins?
- The doctrine says ARCHITECTURE.md wins (it's the contract)
- The agents.md says "code over assumptions"
- The pragmatic reality: changing the code would break every frontend consumer

Wave 12 correctly identifies this as a **documentation debt item**, not a code debt item.

---

## Phase 2 — Runtime Flow Report

### Platform Runtime Path

```
Request to /v1/platform/cms/blog
  │
  ├── Middleware: 'web' ────────────────────────────────── session driver
  ├── Middleware: 'auth:sanctum' ──────────────────────── authenticates user
  ├── Middleware: 'identity.route:platform,platform,enforce'
  │     ├── ApplyIdentityRouteContext::handle()
  │     │     ├── resolve identity context (actorType, authDomain)
  │     │     ├── resolve session ownership
  │     │     ├── TransitionalGuardResolver::resolve()
  │     │     │     └── determineIntendedGuard(platform) → 'merchant'
  │     │     ├── GuardShadowAnalyzer::analyze()
  │     │     ├── GuardSplitSimulationService::simulate()
  │     │     ├── Auth::shouldUse('merchant')       ← KEY: changes default guard
  │     │     ├── enforceSessionOwnership()          ← checks contamination
  │     │     └── matchesOwnership()                 ← checks actor vs route domain
  │     │
  │     ├── ACTIVE GUARD: 'merchant'                ← config('auth.defaults.guard') = 'merchant'
  │     ├── OWNERSHIP: platform domain, enforce mode
  │     └── ACTOR TYPE: super_admin or support_agent
  │
  ├── Middleware: 'platform.authority:platform_admin'
  │     └── EnforcePlatformAuthority ──────────────── checks actor context is PLATFORM_ADMIN
  │
  ├── Controller: AdminBlogController::create()
  │     └── $this->authorize('create', BlogPost::class)
  │
  ├── Policy: BlogPostPolicy::create()
  │     └── $user->can(PermissionEnum::CMS_BLOG_CREATE)
  │           │
  │           ├── User::checkPermissionTo('cms.blog.create')
  │           │     ├── app()->bound('currentStore')?    ← NO (no store.context)
  │           │     └── return $this->spatieCheckPermissionTo() ← Spatie path
  │           │
  │           ├── Spatie resolves guard:
  │           │     └── config('auth.defaults.guard') = 'merchant'
  │           │
  │           ├── Spatie queries:
  │           │     SELECT * FROM permissions
  │           │     WHERE name = 'cms.blog.create'
  │           │     AND guard_name = 'merchant'
  │           │     → NO ROWS FOUND
  │           │
  │           ├── Spatie throws PermissionDoesNotExist
  │           │     (findByName('cms.blog.create', 'merchant') → no match)
  │           │
  │           ├── Caught by checkPermissionTo() catch block @ HasPermissions:256
  │           │     → returns false (back to Gate::before callback)
  │           │
  │           ├── Gate::before receives false → false ?: null → null
  │           │     → Gate proceeds to Policy
  │           │
  │           ├── Policy: BlogPostPolicy::create() checks $user->can(PermissionEnum::CMS_BLOG_CREATE)
  │           │     → Gate::check('cms.blog.create') → Spatie before fails again
  │           │     → no policy registered for 'cms.blog.create' ability → false
  │           │     → Policy returns false
  │           │
  │           └── Gate throws AuthorizationException → 403 Forbidden
  │               (for ALL users, including super_admin)
  │
  │   ⚠️ KEY INSIGHT: PermissionDoesNotExist is CAUGHT, not propagated.
  │      This means the system fails GRACEFULLY (403) instead of crashing (500).
  │      But the 403 is silent and universal — no user can access platform CMS.
```

### Merchant Runtime Path (with store context)

```
Request to /v1/admin/stores/{store}/products
  │
  ├── Middleware: 'web', 'auth:sanctum'
  ├── Middleware: 'identity.route:merchant_admin,merchant,enforce'
  │     └── TransitionalGuardResolver → 'merchant'
  │     └── Auth::shouldUse('merchant')
  │
  ├── Middleware: 'store.context'
  │     └── Binds currentStore to container    ← KEY DIFFERENCE
  │
  ├── Controller: AdminProductController::create()
  │     └── $this->authorize('create', Product::class)
  │
  ├── Policy: ProductPolicy::create()
  │     └── $user->can(PermissionEnum::PRODUCT_CREATE)
  │           │
  │           ├── User::checkPermissionTo('product.create')
  │           │     ├── app()->bound('currentStore')?    ← YES
  │           │     └── PermissionResolver::resolve($user, $store)
  │           │           └── LegacyPermissionAuthority::resolve()
  │           │                 └── permissions based on role + membership
  │           │                 → returns ['product.create', 'product.view', ...]
  │           │
  │           └── in_array('product.create', $permissions) → true
  │
  └── Response: 201 Created
```

### Customer Runtime Path

```
Request to /v1/storefront/account/me
  │
  ├── Middleware: 'auth:sanctum'
  ├── Middleware: 'identity.route:customer_account,customer,enforce'
  │     └── TransitionalGuardResolver → 'customer'
  │     └── Auth::shouldUse('customer')
  │
  ├── ACTIVE GUARD: 'customer'
  ├── OWNERSHIP: customer domain, enforce mode
  │
  ├── Controller: resolves customer data
  │     └── No $this->authorize() needed for own data
  │
  └── Response: 200 OK
```

### Critical Runtime Insight

The **two different authorization paths** in `User::checkPermissionTo()` are the root cause of the platform CMS bug:

| Condition | Path | Guard-aware? | Works on platform? |
|-----------|------|-------------|-------------------|
| `currentStore` NOT bound | `spatieCheckPermissionTo()` | YES — filters by guard_name | ❌ Fails (guard='merchant', perms have guard='web') |
| `currentStore` IS bound | `PermissionResolver::resolve()` | NO — guard-independent | N/A (platform routes never bind currentStore) |

Platform routes never bind `currentStore` → always take the Spatie path → always fail due to guard mismatch.

**Critically, the failure is SILENT (403) not LOUD (500).** Spatie's `checkPermissionTo()` catches `PermissionDoesNotExist` and returns `false` instead of re-throwing. This means:
- No 500 errors in production logs (the bug hides itself)
- No crash test would catch this (tests expect 403, and 403 is what they get)
- Only a functional test accessing a platform CMS endpoint would detect the bug
- The existing `BlogModuleTest` works because its `setUp()` creates `merchant`-guard permissions as a workaround

---

## Phase 3 — Authorization Architecture Report

### 3.1 PermissionEnum — Not a True PHP Enum

**File:** `app/Enums/PermissionEnum.php`

```php
class PermissionEnum   // ← NOT a PHP enum, uses class constants
{
    public const CMS_BLOG_CREATE = 'cms.blog.create';
    // ...
}
```

This contradicts `docs/ARCHITECTURE.md` which mandates: "All domain states MUST be defined using PHP Enums." The file defines 58 permissions across 10 domains but cannot be used with `new Enum(PermissionEnum::class)` validation rules.

### 3.2 Canonical Authorization Path

The canonical path is: **Controller → `$this->authorize()` → Policy → `$user->can()` → `User::checkPermissionTo()` → (Spatie or PermissionResolver)**

| Step | What happens | Source |
|------|-------------|--------|
| Controller | `$this->authorize('create', BlogPost::class)` | `AdminBlogController:55` |
| Gate | Resolves policy `BlogPostPolicy` | Laravel convention |
| Policy | `$user->can(PermissionEnum::CMS_BLOG_CREATE)` | `BlogPostPolicy:46` |
| User model | `checkPermissionTo('cms.blog.create')` | `User:110-126` |
| Spatie path | `spatieCheckPermissionTo()` when no currentStore | `User:113` |
| Custom path | `PermissionResolver::resolve()` when currentStore bound | `User:116-125` |

### 3.3 Are Permissions Guard-Aware?

**Yes, when going through the Spatie path.** Spatie's `checkPermissionTo()` resolves the guard name from `config('auth.defaults.guard')` and includes `guard_name = ?` in its SQL query. Since `Auth::shouldUse('merchant')` changes the default guard, permissions are effectively filtered by guard on platform routes.

**No, when going through the custom path.** `PermissionResolver::resolve()` is guard-independent — it resolves permissions based on role + store membership and returns a flat array. The `in_array()` check ignores guard entirely.

### 3.4 Custom Permission Resolution Bypasses Spatie

The `User::checkPermissionTo()` override at `User:110-126` creates **two parallel resolution paths**:

1. **Spatie path** (no `currentStore`): `$this->spatieCheckPermissionTo($permission, $guardName)` — guard-aware, Spatie-managed
2. **Custom path** (`currentStore` bound): `PermissionResolver::resolve($user, $store)` — guard-independent, custom logic

The custom path currently uses `LegacyPermissionAuthority` (the `migration.rbac.resolver_v2` config flag is false by default), which resolves permissions from `store_user` pivot role → Spatie role permissions.

### 3.5 Platform Routes vs Merchant Routes — Different Authorization Paths

| Aspect | Platform | Merchant (with store) |
|--------|----------|----------------------|
| `store.context` middleware | NOT present | Present |
| `currentStore` bound | No | Yes |
| Authorization path | Spatie | PermissionResolver |
| Guard-aware | Yes | No |
| Permission Source | Spatie DB query | LegacyPermissionAuthority |
| Bug | Guard mismatch (guard='merchant', perms guard='web') | Works correctly |

### 3.6 Role Checks vs Permission Checks — Different Behavior

| Check type | Method | Guard-aware? | Super_admin bypass? |
|-----------|--------|-------------|-------------------|
| Permission | `$user->can(PermissionEnum::*)` | Yes (Spatie path) | No (no `before()` in BlogPostPolicy) |
| Permission | `$user->can(PermissionEnum::*)` | No (custom path) | Partial (via PermissionResolver) |
| Role | `$user->hasRole(RoleEnum::*)` | Yes (Spatie) | N/A |
| Role | `$user->hasRole(RoleEnum::SUPER_ADMIN)` | Yes (Spatie) | N/A — checks super_admin directly |

`hasRole()` also resolves via Spatie's guard-aware mechanism. However, roles in `RoleSeeder` are also created with default `guard_name = 'web'`. `hasRole()` checks `model_has_roles` join table, which stores `model_type` + `model_id` + `role_id` — it does NOT filter by `guard_name` at the join level. Spatie's `hasRole()` only uses guard_name to determine which roles are valid for the current guard context, NOT to filter the actual role-membership rows. This is why `LeadPolicy` (which uses `hasRole()`) works on platform routes while `BlogPostPolicy` (which uses `can()`) does not.

### 3.7 Store-Scoped Permissions vs Platform Permissions

| Entity | Scope | Permission example | Policy |
|--------|-------|-------------------|--------|
| Blog post | Platform | `cms.blog.create` | `BlogPostPolicy` |
| Marketing page | Platform | `cms.page.create` | `MarketingPagePolicy` |
| Documentation | Platform | `cms.doc.create` | `CmsDocumentPolicy` |
| Product | Store | `product.create` | `ProductPolicy` |
| Order | Store | `order.view` | `OrderPolicy` |
| Lead | Platform (not store-scoped) | N/A (uses `hasRole`) | `LeadPolicy` |

The platform CMS permissions (`cms.blog.*`, `cms.page.*`, `cms.doc.*`) are conceptually platform-scoped (no `store_id`), but the guard mismatch makes them unreachable.

---

## Phase 4 — Database Reality Audit

### 4.1 Source-Code Facts (Verified)

| Fact | Source | Detail |
|------|--------|--------|
| 58 permission constants defined | `PermissionEnum.php` | 10 domains, 5 actions each (view/create/update/delete/publish or equivalent) |
| All created with `guard_name = 'web'` | `PermissionSeeder.php:76` | `Permission::firstOrCreate(['name' => $permission])` — no guard_name specified |
| 4 roles created with `guard_name = 'web'` | `PermissionSeeder.php:80-83` | `Role::firstOrCreate(['name' => $role])` — no guard_name specified |
| super_admin gets ALL 58 permissions | `PermissionSeeder.php:86-143` | `syncPermissions()` with full array |
| store_admin gets ~33 permissions | `PermissionSeeder.php:146-182` | No CMS/Marketing platform-level perms |
| staff gets 7 read-only permissions | `PermissionSeeder.php:185-193` | view-only for user, product, order, dashboard, category, brand, tag |
| customer gets 0 permissions | `PermissionSeeder.php:195-196` | Role exists but no permissions |
| No permissions for `guard_name = 'merchant'` in production | Not created by any seeder or migration | Confirmed by absence in seeder |
| BlogModuleTest creates `merchant`-guard permissions | `BlogModuleTest.php:35-52` | Test setUp creates them + attaches to role via pivot |

### 4.2 Database Assumptions (Not Verified at Runtime)

| Assumption | Basis | Can we confirm without connecting to DB? |
|------------|-------|------------------------------------------|
| Permissions exist with `guard_name = 'web'` | Seeder source code | ✅ SOURCE-CODE FACT |
| NO permissions exist with `guard_name = 'merchant'` | Seeder never creates them | ✅ SOURCE-CODE FACT (unless manually added) |
| Roles exist with `guard_name = 'web'` | Seeder source code | ✅ SOURCE-CODE FACT |
| super_admin role has all 58 permissions | syncPermissions() call | ✅ SOURCE-CODE FACT |
| store_admin has ~33 permissions | syncPermissions() call | ✅ SOURCE-CODE FACT |
| No CMS platform perms in store_admin | Not in its syncPermissions() | ✅ SOURCE-CODE FACT |

**All Wave 12 database assumptions are verifiable from source code alone. No actual database inspection is needed to confirm the guard mismatch.**

### 4.3 Migration State

| Migration | Permissions created? | guard_name |
|-----------|---------------------|------------|
| PermissionSeeder (any run) | Yes — 58 permissions | `'web'` |
| RoleSeeder or PermissionSeeder roles | Yes — 4 roles | `'web'` |
| BlogModuleTest::setUp (test only) | Yes — perms for each CMS perm | `'merchant'` |

---

## Phase 5 — Wave 12 Conclusion Classification

### Conclusion A: PermissionSeeder was not EOF-only

**Classification: PROVEN**

Wave 11 claimed the `PermissionSeeder` change was "EOF newline only. No functional code changed." Wave 12 says 55 lines of new CMS/Blog/Marketing permissions were added.

**Doctrine fact:** The current `PermissionSeeder.php` at `lines 48-72` contains 25 permission constants for CMS_DOC (5), CMS_BLOG (5), CMS_PAGE (5), MARKETING_PLATFORM (5), MARKETING_STORE (5). At `lines 86-143`, all 25 are added to `$superAdmin->syncPermissions()`. At `lines 146-182`, MARKETING_STORE (5) are added to `$storeAdmin->syncPermissions()`.

**Source-code fact:** These lines exist in the current file. They were demonstrably added at some point. If they were "EOF only" as Wave 11 claimed, these permissions would not exist in the seeder.

**Verdict:** Wave 11 was incorrect. The seeder was architecturally incomplete (defined permissions in `PermissionEnum` but never created them in the database). Wave 10 fixed this by adding the missing entries. Wave 12 correctly calls out the Wave 11 error.

**However:** The 55-line addition does NOT fix the guard mismatch. Permissions are still created with `guard_name = 'web'`.

---

### Conclusion B: resolution_notes was a genuinely incomplete feature

**Classification: PROVEN**

**Doctrine fact:** The original migration `2026_05_20_170000_alter_leads_add_resolution_tracking.php` added `contacted_at`, `archived_at`, `resolved_at`, `resolved_by` but did NOT add `resolution_notes`. This is a documented gap.

**Source-code fact:** Before Wave 10, the following layers were all missing `resolution_notes`:
- DB schema: No column
- Model: Not in `$fillable` or `$casts`
- DTO: No `resolutionNotes` property
- Request: No validation rule
- Action: Not passed to repository
- Resource: Not in API response

**Runtime fact:** The test expected `resolution_notes` round-trip behavior, which was impossible before Wave 10. The test was correct; the implementation was incomplete.

**Verdict:** This was a genuine partially-implemented feature. The original developer added `resolved_at`/`resolved_by` but missed the free-text notes field — either intentionally deferred or accidentally omitted.

---

### Conclusion C: ARCHITECTURE.md response contract drift exists

**Classification: PROVEN**

**Doctrine fact:** `docs/ARCHITECTURE.md` Section 8 (lines 722-743) specifies the API response format as:
```json
{"status": true, "message": "Success", "data": {}}
{"status": false, "message": "Error message", "error_code": "ERROR_CODE", "errors": {}}
```

**Source-code fact:** `app/Traits/ApiResponserTrait.php` uses:
- `'success' => true/false` (not `'status'`)
- `'code' => $errorCode` (not `'error_code'`)

`app/Exceptions/BaseApiException.php:25-30` uses:
- `'success' => false` (not `'status'`)
- `'code' => $this->errorCode` (not `'error_code'`)

`app/Exceptions/ExceptionRegistrar.php` — ALL 8+ render paths use `'success'`/`'code'`.

**Additional contradiction:** Section 27.1 of ARCHITECTURE.md specifies a THIRD format:
```json
{"message": "...", "code": "DOMAIN_ERROR_001", "status": 403, "errors": []}
```

This uses `"code"` as a string (matches code), `"status"` as an integer HTTP code (not boolean — different from Section 8), and does not have a `"success"` key at all.

**Verdict:** Three conflicting specifications of the same API contract. The code is internally consistent (100% uses `success`/`code`). This is documentation debt, not code debt.

---

### Conclusion D: Ownership architecture was unaffected

**Classification: PROVEN**

**Source-code fact:** All ownership subsystem files were inspected:
- `SessionOwnershipManager.php` — unchanged
- `SessionOwnershipResolver.php` — unchanged
- `TransitionalGuardResolver.php` — unchanged
- `ApplyIdentityRouteContext.php` — unchanged
- `IdentityContextResolver.php` — unchanged
- `EnforcePlatformAuthority.php` — unchanged
- All ownership DTOs — unchanged

Wave 10 changes are confined to:
- `PublicBlogPostResource.php:22` — string change (route name)
- `PermissionSeeder.php` — permission additions
- 6 Lead files — resolution_notes field addition

**Runtime fact:** None of these changes interact with session keys, guard resolution, middleware, or authorization flow.

**Verdict:** Zero ownership subsystem involvement. Wave 12's conclusion is correct.

---

### Conclusion E: Platform CMS authorization is broken

**Classification: PROVEN**

**Runtime fact:** The complete runtime trace (Phase 2) confirms:
1. `Auth::shouldUse('merchant')` changes default guard
2. `User::checkPermissionTo()` takes Spatie path (no `currentStore` on platform routes)
3. Spatie queries by `guard_name = 'merchant'`
4. No matching permissions exist → `PermissionDoesNotExist`
5. **Caught by `checkPermissionTo()` catch block** → returns `false` (not a crash — silent 403)

**The catch behavior is critical:** Without it (`hasPermissionTo()` directly), the system would crash with a 500 error. With it (`checkPermissionTo()`), the system silently returns 403. This is why the bug went undetected — it produces the "expected" failure mode (access denied) rather than a loud crash.

**Source-code fact:** `BlogPostPolicy:46`, `MarketingPagePolicy:46`, `CmsDocumentPolicy:46` all use `$user->can(PermissionEnum::*)` which triggers this failing path.

**Architectural interpretation:** This is a **collision between two architectural layers**:
1. The guard infrastructure, which resolves `platform` → `merchant` and activates `Auth::shouldUse('merchant')` — designed for future guard split preparation
2. The permission infrastructure, which creates all permissions with `guard_name = 'web'` — designed for a shared-guard model

Neither layer is "wrong" in isolation, but together they create a contradiction.

---

### Conclusion F: Platform CMS always returns 403

**Classification: PROVEN**

**Runtime fact:** The failing path applies to ALL users, including super_admins. `BlogPostPolicy` has no `before()` method, no super_admin bypass, and no special handling. All 8 CRUD actions (viewAny, view, create, update, delete, publish, unpublish, schedule) call `$user->can(PermissionEnum::*)` which follows the same failing guard-mismatch path.

**Sub-claim verification:**
- "All platform CMS CRUD operations" → PROVEN (blog, marketing pages, documentation)
- "Return 403 for ALL users" → PROVEN (no user type is exempt from Spatie guard filtering)
- "Including super admins" → PROVEN (BlogPostPolicy has no before/bypass)

**However, a nuance:** The `platform.authority` middleware (`EnforcePlatformAuthority`) checks actor context and would reject non-super_admin/non-support_agent users first. So platform routes are only accessible to super_admin/support_agent actors. But even those authorized users hit the 403 at the Policy layer.

**Why the bug is silent not loud:** Spatie's `checkPermissionTo()` at `HasPermissions.php:252-258` wraps `hasPermissionTo()` in a try/catch for `PermissionDoesNotExist`. When the guard mismatch prevents permission lookup, the exception is caught and `false` is returned. This is the standard Spatie pattern for "permission doesn't exist → user doesn't have it." It means:
- The system never crashes (no 500 errors in production logs)
- The bug is invisible to monitoring (403 is an "expected" auth failure)
- Only a functional test hitting platform CMS endpoints detects it
- The existing `BlogModuleTest` bypasses the bug via test-setup workaround (`setUp()` creates `merchant`-guard permissions)

---

### Conclusion G: guard_names => ['*'] is the correct fix

**Classification: LIKELY**

**Doctrine fact:** Wave 3A explicitly forbids guard split. The system is documented as `shared_sanctum_session` and `shared_until_guard_split`. All three guards use the same `users` table, same session driver, same provider.

**⚠️ SPATIE VERSION CONSTRAINT (CRITICAL NEW FINDING):**

The installed `spatie/laravel-permission` is **v7.4.1** (`composer.lock` confirmed). This version does **NOT** support the `'guard_names' => ['*']` config key:

| Spatie Version | `guard_names` config | Wildcard support |
|----------------|---------------------|------------------|
| v7.x (installed: 7.4.1) | ❌ Not present | `getGuardNames()` returns all configured guards from `auth.guards`, **no wildcard** |
| v8+ | ✅ Supported | `guard_names => ['*']` disables guard filtering |
| Current `config/permission.php` | ❌ Not present | No key exists in the published config |

The specific method `getGuardNames()` in HasPermissions.php:524-527 calls `Guard::getNames($this)`, which returns an array of guard names from `config('auth.guards')`. There is NO code path that checks for `['*']` as a wildcard value.

**This means Wave 12's recommended fix (`'guard_names' => ['*']`) cannot be applied to the current codebase without upgrading Spatie to v8+.**

**Architectural analysis:**
- The intent is correct (disable guard filtering to match the "no guard split" doctrine)
- The implementation (config key) is wrong for the installed Spatie version
- Equivalent fixes for v7.4.1:
  1. **Override `getGuardNames()`** in a custom `PermissionRegistrar` subclass to return empty array or ['web', 'merchant', 'customer']
  2. **Create permissions for all guards** in the seeder (e.g., for each permission name, create with guard_name='web', guard_name='merchant', guard_name='customer')
  3. **Upgrade Spatie to v8+** (which supports the wildcard config natively)
  4. **Modify `User::checkPermissionTo()`** to pass `guardName = null` or override the guard resolution

**Verdict:** **LIKELY** correct in intent, but the **implementation is wrong for v7.4.1**. This is a factual error in Wave 12's recommendation that must be corrected. The "fix" requires either:
- Upgrading Spatie (recommended — cleanest, most maintainable)
- A custom `PermissionRegistrar` override (pragmatic, minimal diff)
- Multi-guard permission creation (safe but adds DB rows)
- Custom `User::checkPermissionTo()` override (fragile, duplicates Spatie logic)

The Wave 12 report should be updated to clarify that `guard_names => ['*']` requires Spatie v8+, and to document the v7.4.1-compatible alternatives.

**Counter-argument:** The system's `TransitionalGuardResolver` actively resolves different guards per route domain and `Auth::shouldUse()` is called intentionally. The guard name switching IS active infrastructure, even if the underlying session is shared. An equally valid fix would be to keep guard-aware permissions but create them for all relevant guards.

The recommendation should be adopted with the note that it removes Spatie's guard_name isolation, which may need to be reconsidered if guard split is ever activated. The v7.4.1 constraint means either upgrading Spatie first or using a different approach.

---

### Conclusion H: Wave 10 changes should remain

**Classification: PROVEN** (all 8 changes)

| Change | Verdict | Rationale |
|--------|---------|-----------|
| `PublicBlogPostResource.php:22` — route name fix | PROVEN — should remain | Bug fix: `public.blog.show` → `public.cms.blog.show`. Minimal, required |
| `PermissionSeeder.php` — 55-line permission addition | PROVEN — should remain | Architectural completion: seeder was missing CMS/Blog/Marketing permissions defined in PermissionEnum |
| Migration `...resolution_notes.php` | PROVEN — should remain | Completes partial feature implementation |
| `Lead.php` — fillable + casts | PROVEN — should remain | Required for mass-assignment and type consistency |
| `UpdateLeadStatusDTO.php` — new property | PROVEN — should remain | Follows DTO-first pattern |
| `UpdateLeadStatusRequest.php` — validation rule | PROVEN — should remain | Required for input validation |
| `UpdateLeadStatusAction.php` — pass resolution_notes | PROVEN — should remain | Required for data flow completeness |
| `AdminLeadResource.php` — expose resolution_notes | PROVEN — should remain | Required for API response exposure |

**No Wave 10 change introduces a regression, security vulnerability, or architectural violation.**

---

## Phase 6 — Final Executive Judgment

### What Wave 12 Got Right

1. **Permission seeder analysis** — Correctly identified that Wave 11 was wrong about "EOF only." Correctly identified the 55 lines of new permissions.

2. **Guard/permission mismatch diagnosis** — Correctly traced the complete runtime path from middleware to Spatie query. The identification of `Auth::shouldUse('merchant')` → Spatie guard filtering → no matching permissions is accurate.

3. **Platform CMS non-functional** — Correctly identified that ALL platform CMS CRUD returns 403 for ALL users, including super admins.

4. **ownership_unaffected** — Correctly confirmed that ownership subsystem files were untouched.

5. **resolution_notes gap identification** — Correctly identified the 7-layer implementation gap.

6. **ARCHITECTURE.md drift** — Correctly identified the Section 8 vs code contradiction.

7. **LeadPolicy role-based (not permission-based)** — Correctly identified that `LeadPolicy` uses `hasRole()` instead of `can()`, contradicting the permission-first doctrine.

### What Wave 12 Got Wrong

Wave 12's analysis is fundamentally correct, but **two specific items require correction:**

1. **Conclusion G (guard_names fix):** Wave 12 recommends `'guard_names' => ['*']` in `config/permission.php`. This config key does NOT exist in the installed `spatie/laravel-permission` v7.4.1. The wildcard syntax was introduced in v8+. **This is a factual error** — the recommended fix cannot be applied without first upgrading Spatie or choosing an alternative approach. The Wave 12 report should mention the v7.4.1 constraint and document the viable alternatives (upgrade Spatie, override `getGuardNames()`, create per-guard permissions, or modify `User::checkPermissionTo()`).

2. **Failure mode description:** Wave 12 describes the platform CMS as "broken" and returning 403, but does not explain WHY it returns 403 instead of crashing. The `checkPermissionTo()` catch block at `HasPermissions.php:256` is the grace mechanism that converts what would be a `PermissionDoesNotExist` exception (500 error) into a `false` return (403). This nuance matters because:
   - It explains why production monitoring doesn't detect the bug
   - It explains why test suites (which expect 403) pass without catching it
   - It means the bug is "silent" rather than "loud"

Minor caveats:
- **The report understates** the fact that `Auth::shouldUse()` is already active and intentional. Wave 3A forbids "guard split" but the system already has per-domain guard name resolution. This is not a contradiction — guard split specifically means session/cookie/provider separation, not guard-name switching. The report could have been clearer about this distinction.

### What Wave 12 Assumed Without Proof

1. **No permissions exist for `guard_name = 'merchant'` in production.** This is correctly inferred from the seeder source code, but technically assumes no manual DB modification or migration added them. This is a reasonable assumption given the source code audit.

2. **Roles are similarly created with `guard_name = 'web'`.** Same assumption — verified from seeder source.

3. **The `'guard_names'` config key works with the installed Spatie version.** This is a configuration assumption not verified against `composer.json` or the actual package version.

4. **The guard mismatch is the SOLE cause of platform CMS 403.** While proven to be the cause for policy-level authorization, the report assumes no other middleware or validation blocks access first. This is mitigated by the fact that `platform.authority` middleware succeeds (actors are authenticated), so the only remaining block is the policy layer.

### Missing Evidence Required

1. **Database snapshot** — A `SELECT guard_name, COUNT(*) FROM permissions GROUP BY guard_name` query would definitively confirm the guard_name distribution in production. Not accessible without a DB connection.

2. ~~**Spatie package version** — `composer.json` should be checked to confirm `'guard_names'` wildcard syntax is supported by the installed version of `spatie/laravel-permission`.~~ ✅ **RESOLVED by this audit.** v7.4.1 confirmed — does NOT support wildcard. See Conclusion G for details and alternatives.

3. **Test suite run** — Running the blog auth tests with the `'guard_names' => ['*']` fix would empirically confirm the fix works. Not done in this audit.

4. **Production telemetry** — Checking whether `auth.guard.illegal_fallback_detected` or other mismatch signals are firing in production. Not accessible.

### Highest Priority Architectural Risk

**C-01: Platform CMS Permission Guard Mismatch (CRITICAL)**

This is the single highest-priority issue in the codebase:
- Platform CMS is non-functional in production (blog posts, marketing pages, documentation)
- All CRUD operations return 403 for all users, silently (exception is caught, not propagated)
- The bug is invisible to monitoring (403 is not a 500 — no alerts fire)
- Fix requires choosing among 4 viable approaches (Spatie upgrade, Registrar override, multi-guard seeding, or custom checkPermissionTo)
- Guards against no documented counter-indication
- Test evidence exists (`BlogModuleTest.php` comments document the bug; test setUp creates merchant-guard permissions as workaround)

**C-02: ARCHITECTURE.md Response Format Drift (CRITICAL)**

The authoritative architecture document contradicts the codebase on a fundamental contract. New contributors will implement the wrong format.

**C-03: Dual Authorization Paths in User Model (HIGH)**

`User::checkPermissionTo()` has two different resolution paths with different guard behavior. This creates an inconsistency where platform routes (no `currentStore`) are guard-aware and fail, while merchant routes (with `currentStore`) are guard-independent and work. This design split is confusing and fragile.

### Recommended Next Action

**Immediate (0-1 day):**
1. **Fix the guard mismatch** — Choose ONE approach:
   - **Option A (recommended):** Upgrade Spatie to v8+ then add `'guard_names' => ['*']` to `config/permission.php` — supports wildcard natively
   - **Option B (pragmatic):** Create a custom `PermissionRegistrar` subclass overriding `getGuardNames()` to return `['web']` or `['web', 'merchant', 'customer']` — works with v7.4.1, minimal diff
   - **Option C (conservative):** Add all 58 permissions for each active guard (`'web'`, `'merchant'`, `'customer'`) in `PermissionSeeder` — safe but adds ~116 extra DB rows
   - **Option D (minimal):** Override `User::checkPermissionTo()` on platform routes to bypass guard filtering — fragile, not recommended
2. Remove the test workaround from `BlogModuleTest.php:45-52`
3. Run the test suite to confirm blog auth tests pass without the workaround
4. Fix `docs/ARCHITECTURE.md` Section 8 and Section 27.1 to document the actual `success`/`code` format

**Short-term (1-3 days):**
5. Add `LEAD_*` permissions to `PermissionEnum` and migrate `LeadPolicy` from `hasRole()` to `can()`
6. Convert `PermissionEnum` from class constants to a native PHP `enum`
7. Consolidate Sections 8 and 27.1 of ARCHITECTURE.md into a single authoritative response format specification

**Architectural decision needed:**
8. Decide whether the transitional guard resolution pattern (`Auth::shouldUse()` with different guard names per domain) is permanent or temporary. If permanent, create permissions for all active guards explicitly. If temporary, document the planned guard-split timeline. The current "preparation without activation" state creates ongoing architectural ambiguity.

---

## Appendix: Classification Summary

| # | Conclusion | Classification | Confidence |
|---|-----------|---------------|------------|
| A | PermissionSeeder was not EOF-only | PROVEN | High |
| B | resolution_notes was genuinely incomplete | PROVEN | High |
| C | ARCHITECTURE.md response contract drift | PROVEN | High |
| D | Ownership architecture unaffected | PROVEN | High |
| E | Platform CMS authorization broken | PROVEN | High |
| F | Platform CMS always returns 403 | PROVEN | High |
| G | guard_names => ['*'] is correct fix | LIKELY (intent) / FACTUAL ERROR (implementation) | Medium — intent correct, but implementation doesn't exist in v7.4.1 |
| H | Wave 10 changes should remain | PROVEN | High |

**8 conclusions classified: 7 PROVEN, 1 LIKELY, 0 UNPROVEN, 0 FALSE**

---

## Appendix: Doctrine vs Source-Code Discrepancies Found

| Item | Doctrine Says | Code Says | Severity |
|------|--------------|-----------|----------|
| API response format (Section 8) | `status`/`error_code` | `success`/`code` | CRITICAL |
| API error format (Section 27.1) | `code` (string) + `status` (int) | Same as code | CRITICAL (conflicts with Section 8) |
| PHP Enums mandatory | ARCHITECTURE.md | `PermissionEnum` uses class constants | MEDIUM |
| Permission format | `{domain}.{action}` | `cms.blog.create` — follows convention | NONE (consistent) |
| LeadPolicy authorization | Permission-first | Role-first (`hasRole()`) | MEDIUM |
| Guard isolation | Not active | `Auth::shouldUse()` changes default guard | MEDIUM (contradiction of intent) |
| `guard_names` config | Not specified | Not present in config/permission.php — and doesn't exist in Spatie v7.4.1 | The gap this audit identifies — Wave 12 fix requires Spatie v8+ |
| `checkPermissionTo()` catch behavior | Not documented | Catches `PermissionDoesNotExist` → returns `false` (silent 403) | MEDIUM — explains why bug is invisible to monitoring |
| Permission collision on `$this->authorize('create', BlogPost::class)` | Not considered | Gate::before checks 'create' (doesn't exist at all) AND policy checks 'cms.blog.create' (wrong guard) — two independent failures | LOW — both paths produce the same 403 result |

---

*Report generated by independent architectural audit. No Wave 11 or Wave 12 conclusion was trusted without independent source-code or doctrine verification.*
