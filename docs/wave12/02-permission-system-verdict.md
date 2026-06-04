# Wave 12 — Permission Architecture Verdict

## Independent Verification of the Guard/Permission Mismatch

Every claim was independently verified against source code. No prior report was trusted.

---

## The Guard Mismatch: Provably Real

### Step 1: Platform Routes Set Guard to 'merchant'

**File:** `routes/api.php:19-32`
```php
Route::prefix('/v1/platform')
    ->middleware([
        'web',
        'auth:sanctum',
        'identity.route:platform,platform,enforce',   // ← key middleware
        'platform.authority:platform_admin',
    ])
    ->group(function (): void {
        require 'api/v1/platform/cms/blog.php';        // ← blog routes inside
        require 'api/v1/platform/cms/marketing-pages.php';
        require 'api/v1/platform/cms/documentation.php';
    });
```

### Step 2: ApplyIdentityRouteContext Calls Auth::shouldUse('merchant')

**File:** `app/Http/Middleware/ApplyIdentityRouteContext.php:89`
```php
Auth::shouldUse($guardResolution->guard);
```

**File:** `app/Services/Auth/TransitionalGuardResolver.php:56-60`
```php
return match ($context->authDomain) {
    'customer' => 'customer',
    'merchant', 'platform' => 'merchant',    // ← platform maps to 'merchant'
    default => 'web',
};
```

`Auth::shouldUse('merchant')` mutates `config('auth.defaults.guard')` to `'merchant'`.

### Step 3: All Permissions Are Created with guard_name = 'web'

**File:** `database/seeders/PermissionSeeder.php:75-77`
```php
foreach ($permissions as $permission) {
    Permission::firstOrCreate(['name' => $permission]);
    // No 'guard_name' specified → Spatie defaults to config('auth.defaults.guard')
    // At seed time, this is 'web' (config/auth.php:17: env('AUTH_GUARD', 'web'))
}
```

When `config/permission.php` line 121 sets `'register_permission_check_method' => true`, Spatie registers a `Gate::before` callback.

### Step 4: Every Platform CMS Policy Calls $user->can() with PermissionEnum

**File:** `app/Policies/BlogPostPolicy.php:46`
```php
$user->can(PermissionEnum::CMS_BLOG_CREATE)
```

**File:** `app/Policies/MarketingPagePolicy.php:46`
```php
$user->can(PermissionEnum::CMS_PAGE_CREATE)
```

**File:** `app/Policies/CmsDocumentPolicy.php:46`
```php
$user->can(PermissionEnum::CMS_DOC_CREATE)
```

### Step 5: The Collision

Spatie's `$user->can()` → `User::checkPermissionTo()` (at `app/Models/User.php:110-126`):
- `currentStore` is **NOT bound** on platform routes (no `store.context` middleware in platform route group)
- Falls through to `$this->spatieCheckPermissionTo($permission, $guardName)` at line 113
- Spatie resolves guard via `Guard::getDefaultName($this)` which reads `config('auth.defaults.guard')` → **now returns `'merchant'`**
- Spatie queries `permissions WHERE name = 'cms.blog.create' AND guard_name = 'merchant'`
- **No rows found** — all permissions have `guard_name = 'web'`
- Spatie throws `PermissionDoesNotExist` → `checkPermissionTo` catches it → returns `false`

### Step 6: Result — 403 Forbidden on Every Platform CMS Permission Check

The `$this->authorize('create', BlogPost::class)` in `AdminBlogController.php:55` always gets `false` from `BlogPostPolicy::create()`, which returns false because `$user->can(PermissionEnum::CMS_BLOG_CREATE)` returns false.

---

## Affected Routes

Every route in the `/v1/platform` group that calls `$this->authorize()`:

| Area | Routes | File | Verdict |
|------|--------|------|---------|
| Blog CRUD | `platform.cms.blog.*` (8 routes) | `routes/api/v1/platform/cms/blog.php` | **ALWAYS 403** |
| Marketing Pages CRUD | `platform.cms.marketing-pages.*` | `routes/api/v1/platform/cms/marketing-pages.php` | **ALWAYS 403** |
| Documentation CRUD | `platform.cms.documentation.*` | `routes/api/v1/platform/cms/documentation.php` | **ALWAYS 403** |

**Leads are NOT affected** — `LeadPolicy` uses `$user->hasRole()` at `LeadPolicy.php:27` instead of `$user->can()`. The `hasRole()` method uses Spatie's role resolution which resolves the same way, but roles are created with a single guard and `hasRole()` does not filter by guard_name in the same restrictive way as permissions.

**Affected controllers:**
- `AdminBlogController.php:41,55,67,82,97,109,124,139`
- `PlatformMarketingPageController.php` (same pattern)
- `PlatformDocumentationController.php` (same pattern)

---

## Production Impact

This is a **HIGH-SEVERITY** production defect affecting all platform CMS CRUD operations. Any permission-gated action on blog posts, marketing pages, or documentation returns 403 Forbidden for ALL users, including super admins.

**Evidence from the test file:** `BlogModuleTest.php:35-52` explicitly documents this bug and creates a workaround:
```php
// Platform routes use Auth::shouldUse('merchant') which changes
// config('auth.defaults.guard') to 'merchant'. Spatie looks up
// permissions by guard_name, so we must also create merchant-guard
// permissions and assign them to the admin's role.
```

The test would fail without this workaround. Production has no such workaround.

---

## Architectural Fix Options

### Option 1: Share Permissions Across Guards (RECOMMENDED)

Add to `config/permission.php`:
```php
'guard_names' => ['*'],
```

This tells Spatie to ignore `guard_name` filtering and match permissions regardless of the active guard. The permission `'cms.blog.create'` with `guard_name = 'web'` would match even when `Auth::shouldUse('merchant')` is active.

**Effort:** 1 line, 5 minutes.
**Risk:** Near-zero. All guards share the same users table and model.
**Downside:** No guard isolation if future guard separation is needed.
**Architecture alignment:** Matches "shared guard" doctrine in Wave 3A (Section 24): "Wave 3A explicitly forbids guard split." The system is explicitly NOT split yet.

### Option 2: Create Permissions for All Guards in Seeder

Modify `PermissionSeeder`:
```php
foreach ($permissions as $permission) {
    foreach (['web', 'merchant', 'customer'] as $guard) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
    }
}
```

**Effort:** ~5 lines.
**Risk:** 3x permission rows in database. Must keep in sync.
**Downside:** Violates DRY. Spatie still filters by guard at query time.

### Option 3: Remove Auth::shouldUse() from Platform Routes

Modify `ApplyIdentityRouteContext` to not call `Auth::shouldUse()` for platform routes, or modify `TransitionalGuardResolver` to return `'web'` for platform domain.

**Effort:** 1 line in `TransitionalGuardResolver.php:58`.
**Risk:** HIGH. The entire guard resolution infrastructure (session ownership, contamination detection, guard shadow analysis) is built around this mapping. Changing it could break session ownership enforcement or introduce contamination vulnerabilities.

### Option 4: Override checkPermissionTo in User Model to Bypass Guard on Platform Routes

**Effort:** Moderate. More logic in `User::checkPermissionTo()`.
**Risk:** Creates special-case logic that may be forgotten.

### Recommendation

**Option 1** is architecturally correct for the current system state. The Wave 3A doctrine explicitly forbids guard split. All guards share the same `users` provider and model. Guard names are preparation infrastructure, not active separation. Adding `'guard_names' => ['*']` aligns with the documented architecture and fixes all affected routes with a single-line change.

Once the production fix is in place, remove the pivot-table workaround from `BlogModuleTest.php:45-52`.

---

## Spatie Gate::before Registration

**File:** `config/permission.php:121`: `'register_permission_check_method' => true`

This causes Spatie to register a `Gate::before` callback in `PermissionRegistrar`. The callback calls `$user->checkPermissionTo($ability)`. This is the entry point that triggers the guard mismatch.

**No custom `Gate::after` is registered anywhere in the codebase.**

---

## PermissionSeeder: What Actually Changed

The Wave 11 report incorrectly stated the PermissionSeeder change was "EOF only." Git diff confirms **55 lines of new permission entries** were added:

1. CMS_DOC (5 perms: view, create, update, delete, publish)
2. CMS_BLOG (5 perms: view, create, update, delete, publish)
3. CMS_PAGE (5 perms: view, create, update, delete, publish)
4. MARKETING_PLATFORM (5 perms: view, create, update, delete, publish)
5. MARKETING_STORE (5 perms: view, create, update, delete, publish)

These were added to the `$permissions` array AND to `$superAdmin->syncPermissions()` AND (`MARKETING_STORE` only) to `$storeAdmin->syncPermissions()`.

**The Wave 11 report's claim of "no functional change" and "EOF newline only" is incorrect.** The change was functionally meaningful — it completed the seeder's permission list.

However, all these permissions are created with `guard_name = 'web'`, so they still don't resolve on platform routes without Option 1 above.
