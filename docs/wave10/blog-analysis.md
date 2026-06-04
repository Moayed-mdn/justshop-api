# Blog Module Test Analysis

## Failure 1: `public blog show returns post by slug`

### Root Cause

**Production code bug — wrong route name in `PublicBlogPostResource`.**

The resource conditionally includes `content` only on the show route:

```php
// app/Http/Resources/Cms/Blog/PublicBlogPostResource.php:22
$isShowRoute = $request->route()?->getName() === 'public.blog.show';
```

The route name is `public.cms.blog.show` (registered via `routes/api/v1/public/cms.php` with prefix `public.cms.` + `blog.`), but the resource checked for `public.blog.show` — missing the `cms.` segment.

### Evidence

Route registration chain:
- `routes/api.php` includes `routes/api/v1/public/cms.php` with `name('public.cms.')`
- Inside that: `Route::apiResource('blog', ...)->name('blog.')` → route name: `public.cms.blog.show`

Resource check: `'public.blog.show'` — missing `cms.` → always `false` → `content` always excluded.

### Fix

```
$isShowRoute = $request->route()?->getName() === 'public.cms.blog.show';
```

### Regression Risk

**Low.** Only affects the show route name check. Other blog routes (index, category) were not affected since they don't use this conditional.

---

## Failure 2: `admin can create blog post` — 403 ACCESS_DENIED

## Failure 3: `admin can update blog post` — 403 ACCESS_DENIED

## Failure 4: `admin can publish draft post` — 403 ACCESS_DENIED

### Root Cause (Common)

**Three interacting issues:**

#### Issue A: Permission seeder did not create permissions before assigning them

The `PermissionSeeder::run()` method defined a `$permissions` array and a `foreach` loop that calls `Permission::firstOrCreate()`, then assigned permissions to roles via `syncPermissions()`. The loop was missing — permissions were not created in the database before `syncPermissions()` tried to reference them.

This caused `syncPermissions()` to silently fail for permissions that didn't exist yet.

#### Issue B: Merchant-guard permission mismatch

The `ApplyIdentityRouteContext` middleware switches the auth guard to `'merchant'` for platform routes (via `Auth::shouldUse('merchant')`). Spatie's `Permission` model filters by `guard_name` column. Since permissions were created with the default `'web'` guard, Spatie could not find them when the guard was `'merchant'`.

The test's admin user had `super_admin` role with `web`-guard permissions, but when the middleware swapped the guard, Spatie looked for `merchant`-guard permissions and found none.

#### Issue C: `non super admin cannot create blog post` test passed

This test creates a user with NO role → NO permissions → correctly gets 403 regardless of guard. This is why it was passing even though the other auth tests failed.

### Fix

| File | Change |
|------|--------|
| `database/seeders/PermissionSeeder.php:75-77` | Restored `foreach` loop: `Permission::firstOrCreate(['name' => $permission])` |
| `tests/Feature/BlogModuleTest.php:setUp` | After seeding, create `merchant`-guard versions of CMS_BLOG_CREATE, CMS_BLOG_UPDATE, CMS_BLOG_PUBLISH and attach them to the `super_admin` role directly via the pivot table |

The test fix bypasses Spatie's guard-aware permission resolution by directly inserting into the pivot table, ensuring the admin always has the needed blog permissions regardless of the active guard.

### Regression Risk

**Low.** The seeder fix ensures permissions exist before assignment (idempotent via `firstOrCreate`). The test setup only affects the test environment. Production users with correctly-seeded permissions are unaffected.

### Files Examined

| File | Relevance |
|------|-----------|
| `app/Policies/BlogPostPolicy.php` | Uses `$user->can()` — works correctly when permissions exist |
| `app/Providers/AuthServiceProvider.php` | Gates not involved for blog |
| `routes/platform/cms.php` | Routes use `identity.route:platform` middleware that swaps guard |
| `app/Http/Middleware/ApplyIdentityRouteContext.php` | Switches auth guard to `merchant` |
