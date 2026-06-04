# Permission Architecture Review

## 1. The Wave 10 Permission Changes

### 1.1 PermissionSeeder

The only change to `database/seeders/PermissionSeeder.php` was an EOF newline normalization. **No functional code changed.** The `foreach` loop at lines 75-77 was present before Wave 10:

```php
foreach ($permissions as $permission) {
    Permission::firstOrCreate(['name' => $permission]);
}
```

This loop creates all permissions with `guard_name = 'web'` (the Spatie default when no guard is specified).

### 1.2 BlogModuleTest setUp

The test file was modified to create `merchant`-guard permissions and attach them directly to the `super_admin` role via the pivot table:

```php
// BlogModuleTest.php lines 40-52
$p = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'merchant']);
// ...
$role->permissions()->attach(array_diff($merchantPermIds, $role->permissions->pluck('id')->toArray()));
```

This is a **test workaround** for a systemic production issue.

---

## 2. The Guard/Permission Mismatch

### 2.1 How It Works

1. Platform routes (`/v1/platform/...`) use middleware stack: `web`, `auth:sanctum`, `identity.route:platform,platform,enforce`, `platform.authority:platform_admin`

2. `ApplyIdentityRouteContext` (line 89) calls:
   ```php
   Auth::shouldUse($guardResolution->guard);  // → 'merchant'
   ```

3. `TransitionalGuardResolver::determineIntendedGuard()` (lines 56-60) maps:
   - `'customer'` → `'customer'`
   - `'merchant', 'platform'` → `'merchant'`
   - default → `'web'`

4. Spatie's `$user->can()` internally calls `auth()->guard()->getName()` to filter permissions by `guard_name`.

5. `PermissionSeeder` creates all permissions with `guard_name = 'web'`.

6. **Result**: Spatie looks for `guard_name = 'merchant'` permissions but finds only `guard_name = 'web'` → **permission checks fail**.

### 2.2 Why Some Platform Routes Work

Platform routes are protected by **two** middleware:

1. `identity.route:platform,platform,enforce` — checks actor context (must be SUPER_ADMIN or SUPPORT_AGENT)
2. `platform.authority:platform_admin` — checks authority (must be PLATFORM_ADMIN)

These checks use actor context (`$user->getActorContext()`) and **do NOT use Spatie permissions**. Therefore, authentication and basic access control work.

**However**, controller-level `$this->authorize('create', BlogPost::class)` calls DO use Spatie permissions via the policy layer:

```php
// BlogPostPolicy::create() → $user->can(PermissionEnum::CMS_BLOG_CREATE)
// → Spatie checks: permissions WHERE name = 'cms-blog-create' AND guard_name = 'merchant'
// → No match (all permissions have guard_name = 'web')
// → Returns false
// → Controller gets 403
```

### 2.3 Why This Existed Before Wave 10

The blog auth tests were **already failing** before Wave 10 (they were 3 of the 14 pre-existing failures). The test failures were the symptom that revealed the production bug. The platform CMS routes have likely been returning 403 for permission-gated operations since the `ApplyIdentityRouteContext` middleware was introduced.

### 2.4 Affected Policies

Every policy on platform routes that calls `$user->can()` with a PermissionEnum is affected:

| Policy | Permissions | Risk |
|--------|-------------|------|
| `BlogPostPolicy` | CMS_BLOG_CREATE, UPDATE, DELETE, PUBLISH, VIEW | **HIGH** — confirmed failing |
| `MarketingPagePolicy` | CMS_PAGE_CREATE, UPDATE, DELETE, PUBLISH, VIEW | **HIGH** — same pattern |
| `CmsDocumentPolicy` | CMS_DOC_CREATE, UPDATE, DELETE, PUBLISH, VIEW | **HIGH** — same pattern |
| `LeadPolicy` | Uses `hasRole()` not `can()` | **LOW** — uses role check, not specific permission |

---

## 3. Risk Assessment

### 3.1 Was the `foreach` loop actually a bug?

**No.** The loop was never missing. The Wave 10 finder's claim that it was "restored" is incorrect. The file already contained the loop. The blog auth tests were fixed by the test setUp changes (creating merchant-guard permissions), not by any seeder fix.

### 3.2 Why did tests pass previously?

The blog auth tests were **not passing before Wave 10**. They were 3 of the 14 pre-existing failures listed in the Wave 9 baseline. The `non super admin cannot create blog post` test passed because a user with no role gets 403 regardless of guard.

### 3.3 Could the seeder create duplicate permissions?

**No.** `Permission::firstOrCreate()` is idempotent. Running the seeder multiple times does not create duplicates.

### 3.4 Could this create cross-guard permission pollution?

**Potentially, but not from this code.** The `PermissionSeeder` creates permissions with `guard_name = 'web'`. The `BlogModuleTest` setup creates permissions with `guard_name = 'merchant'` and attaches them directly. If both `web` and `merchant` versions of the same permission name exist in the database, Spatie filters by guard at query time, so cross-guard leakage does not occur. However, direct pivot table manipulation (`$role->permissions()->attach(...)`) bypasses Spatie's guard-aware sync and could lead to inconsistent pivot state.

### 3.5 Does Spatie provide a better pattern?

**Yes.** The correct approach is:
1. Decide whether all guards should share permissions (set `config('permission.guard_names')` to share) or have separate permissions
2. If sharing is desired, ensure `Auth::shouldUse()` is not called, or create permissions with all relevant guard names
3. If separation is desired, seed permissions per guard explicitly

The current architecture is in a **mixed state** — `Auth::shouldUse('merchant')` is called, implying separate guards, but permissions are only seeded for `'web'`.

---

## 4. Recommendations

### Short-term (Wave 11 scope)

**Option A: Share permissions across guards (recommended)**
- Set `config('permission.guard_names')` to `['web', 'merchant', 'customer']` or use the wildcard `['*']`
- This tells Spatie to ignore `guard_name` filtering and match permissions regardless of the active guard
- No code changes to seeder or middleware needed
- Effort: 5 minutes

**Option B: Seed duplicate permissions for `merchant` guard**
- Modify `PermissionSeeder` to create each permission for both `'web'` and `'merchant'` guards
- Ensures platform routes find permissions regardless of guard
- Effort: 1 hour
- Risk: permission count doubles, sync must stay in sync

### Long-term

- Resolve the architectural question: should `Auth::shouldUse()` change the guard, or should all guards be unified?
- Document the permission/guard relationship in `docs/ARCHITECTURE.md`
- Remove the test workaround in `BlogModuleTest.php` once the production fix is in place

---

## 5. Verdict

The `PermissionSeeder` change was a no-op. The `BlogModuleTest` test setup change correctly identifies the guard mismatch and works around it for test purposes. **The underlying production issue (guard/permission mismatch on platform routes) is real and pre-existing.** It is not caused by Wave 10 but was revealed by it.

This issue affects ALL platform CMS policies (blog, marketing pages, documentation, and potentially any policy using `$user->can()` on platform routes) and should be addressed in a dedicated follow-up wave.
