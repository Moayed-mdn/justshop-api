# Wave 12 — Technical Debt Reassessment

## Methodology

Independent audit of the current codebase. Previous Wave 11 conclusions were not trusted. Every finding was verified against source code.

---

## CRITICAL Findings

### C-01: Platform CMS Permission GuarD Mismatch (Severity: CRITICAL)

| Dimension | Score | Evidence |
|-----------|-------|----------|
| **Root Cause** | `Auth::shouldUse('merchant')` at `ApplyIdentityRouteContext.php:89` changes `config('auth.defaults.guard')` to `'merchant'`. Spatie resolves guard to `'merchant'` but all permissions are stored with `guard_name = 'web'`. | Source code confirmation: `TransitionalGuardResolver.php:58`, `PermissionSeeder.php:76`, `BlogPostPolicy.php:46` |
| **Impact** | All platform CMS CRUD operations (`/v1/platform/cms/blog/*`, `/v1/platform/cms/marketing-pages/*`, `/v1/platform/cms/documentation/*`) return 403 Forbidden for ALL users, including super admins. Platform CMS is non-functional. | `AdminBlogController.php`, `MarketingPagePolicy.php`, `CmsDocumentPolicy.php` — all use `$user->can()` |
| **Architectural Risk** | HIGH. Platform CMS is a core business feature (blog, marketing pages, documentation). Currently completely broken in production. | |
| **Recommended Fix** | Add `'guard_names' => ['*']` to `config/permission.php` (Spatie shared guards). This is architecture-consistent — Wave 3A explicitly forbids guard split. All guards share the same users table/model. | |
| **Estimated Effort** | 5 minutes (1 line config change + remove test workaround) | |

### C-02: ARCHITECTURE.md Response Format Contract Drift (Severity: CRITICAL)

| Dimension | Evidence |
|-----------|----------|
| **Root Cause** | `docs/ARCHITECTURE.md` Section 8 specifies `"status"` (bool) and `"error_code"` (string) in API responses. Actual code in `ApiResponserTrait.php`, `BaseApiException.php:25-30`, and `ExceptionRegistrar.php` (all 8+ error paths) consistently uses `"success"` and `"code"`. | |
| **Impact** | Document is the authoritative contract. New contributors will implement `status`/`error_code` and break the API. Frontend teams may be confused about which keys to use. Tests written against the doc will fail. | |
| **Architectural Risk** | HIGH. The ARCHITECTURE.md is supposed to be the absolute authority (per AGENTS.md: "ABSOLUTE AUTHORITY for implementation doctrine"). When it contradicts the code, both trust and correctness suffer. | |
| **Recommended Fix** | Update `docs/ARCHITECTURE.md` Section 8 to match the actual code: change `"status"` → `"success"` and `"error_code"` → `"code"`. Do NOT change the code — it is internally consistent and in production use. | |
| **Estimated Effort** | 15 minutes (documentation update) | |

### C-03: API Error Response Format Inconsistency in ARCHITECTURE.md (Severity: CRITICAL)

| Dimension | Evidence |
|-----------|----------|
| **Root Cause** | Section 8 specifies one format (`"status"`, `"error_code"`). Section 27.1 specifies a different format (`"code"`, `"status"` as int, `"errors"`). The code uses yet a third format (`"success"`, `"code"`). Three conflicting specifications of the same contract. | |
| **Impact** | Without a single canonical reference, any implementation is equally correct. | |
| **Recommended Fix** | Consolidate both Sections 8 and 27.1 into a single, authoritative section that documents the actual format used by the code. | |
| **Estimated Effort** | 30 minutes | |

---

## HIGH Findings

### H-01: Test Pivot Table Manipulation Workaround

| Dimension | Evidence |
|-----------|----------|
| **Root Cause** | `BlogModuleTest.php:45-52` uses `$role->permissions()->attach()` directly, bypassing Spatie's guard-aware `syncPermissions()`. | |
| **Impact** | LOW severity in isolation (test-only). But it masks a CRITICAL production defect (C-01). | |
| **Risk** | If the production guard fix is applied, this workaround becomes dead code (should be removed). | |
| **Fix** | Remove test workaround after applying C-01 fix. |

### H-02: Lead Policy Uses hasRole Instead of Permission

| Dimension | Evidence |
|-----------|----------|
| **Root Cause** | `LeadPolicy.php:27` uses `$user->hasRole(RoleEnum::SUPER_ADMIN->value)` instead of `$user->can(PermissionEnum::LEAD_*)`. | |
| **Impact** | Leads bypass the permission system entirely. Any user with `super_admin` role gets full lead access regardless of specific permission assignments. This works currently but is architecturally inconsistent with the policy doctrine which says "Policies are the ONLY authorization enforcement layer" using permissions, not roles. | |
| **Risk** | MEDIUM. If the role-based check ever needs to be granular (e.g., support agents with limited lead access), this pattern requires a rewrite. | |
| **Recommended Fix** | Define lead-specific permissions (e.g., `lead.view`, `lead.update`, `lead.delete`) in `PermissionEnum` and update `LeadPolicy` to use them. | |
| **Estimated Effort** | 2 hours | |

### H-03: Permission Seeder Lacks Idempotent Guard Configuration

| Dimension | Evidence |
|-----------|----------|
| **Root Cause** | `PermissionSeeder.php:76` does not specify `guard_name`. When Spatie's default guard config is changed (e.g., to `'merchant'` via `Auth::shouldUse()`), future seeders could create permissions under the wrong guard. | |
| **Impact** | Currently all permissions are `guard_name = 'web'`. If a deployment script calls `Auth::shouldUse()` before the seeder, permissions would be created with wrong guard. | |
| **Fix** | Explicitly specify `'guard_name' => 'web'` in `firstOrCreate` calls. | |
| **Effort** | 10 minutes | |

---

## MEDIUM Findings

### M-01: PermissionEnum Not a True Enum

| Dimension | Evidence |
|-----------|----------|
| **Root Cause** | `PermissionEnum.php` uses class constants, not PHP `enum`. Cannot be used with `new Enum(PermissionEnum::class)` validation. | |
| **Impact** | Inconsistent with ARCHITECTURE.md which mandates PHP Enums. Cannot benefit from native enum features. | |
| **Risk** | LOW. Works fine as constants. Minor inconsistency. | |

### M-02: ExceptionRenderingTest Correctness Guarantee Missing

| Dimension | Evidence |
|-----------|----------|
| **Root Cause** | `ExceptionRenderingTest.php` was fixed by updating assertions, but there is **no test that verifies the production code matches ARCHITECTURE.md Section 8.** If the architecture doc is wrong (which it is, per C-02), a test that checked against the doc would fail. | |
| **Impact** | No single authoritative source of truth for API response format exists. | |
| **Fix** | Create a contract test that documents the ACTUAL response format (matching code) and fails if either code or contract deviates. | |
| **Effort** | 1 hour | |

### M-03: CheckPermissionTo Override Mixes Concerns

| Dimension | Evidence |
|-----------|----------|
| **Root Cause** | `User.php:110-126` mixes store-scoped permission resolution with Spatie's default guard-aware resolution. The method delegates to `spatieCheckPermissionTo` when `currentStore` is not bound, but Spatie's resolution is guard-dependent while the custom `PermissionResolver` path is guard-independent. | |
| **Impact** | Two different permission resolution paths with different guard handling. The store-scoped path (when `currentStore` IS bound) bypasses guard filtering; the platform path (when `currentStore` is NOT bound) uses guard filtering. This inconsistency is confusing and caused the guard mismatch bug. | |
| **Fix** | After applying C-01 fix (shared guards), the guard filtering is removed, making both paths consistent. | |

---

## LOW Findings

### L-01: PermissionEnum Missing Lead Permissions
No `LEAD_*` permissions exist. Leads use `hasRole()` instead. Inconsistent with permission-first architecture.

### L-02: Seeder SyncPermissions() Duplicates the Permission Array
The same 55+ permission strings are hardcoded in three places: the `$permissions` array, `syncPermissions()` for super_admin, and `syncPermissions()` for store_admin. Any new permission must be added to all three places. Risk of omission.

### L-03: BlogModuleTest Setup Duplicates Role Lookup
`BlogModuleTest.php:39` and `:56` both call `Role::firstOrCreate(['name' => RoleEnum::SUPER_ADMIN->value])`. The second call re-fetches the same role. Minor inefficiency.

---

## Summary

| Severity | Count | Items |
|----------|-------|-------|
| **CRITICAL** | 3 | C-01 (guard mismatch breaks platform CMS), C-02 (ARCHITECTURE.md Section 8 wrong), C-03 (ARCHITECTURE.md Sections 8/27.1 conflict) |
| **HIGH** | 3 | H-01 (test workaround), H-02 (Lead uses role not perm), H-03 (seeder guard_config) |
| **MEDIUM** | 3 | M-01 (PermissionEnum not enum), M-02 (no contract test), M-03 (checkPermissionTo mixing) |
| **LOW** | 3 | L-01 (no lead perms), L-02 (seeder duplication), L-03 (test setup redundancy) |

**Total: 12 items**
