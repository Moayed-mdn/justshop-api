# Storefront Runtime Test Analysis

## Failure A: `resolve endpoint returns contract shape...` — `matched` → `redirect`

## Failure B: `resolve endpoint supports locale prefixed arabic paths` — `matched` → `redirect`

### Root Cause

**Test expectations outdated.** The catalog URL prefix was intentionally migrated from `/products/` to `/shop/`. The runtime service:

- Routes categories at `StorefrontRuntimeService.php:385` via pattern `#^/shop/category/(...)$#`
- Routes products at `StorefrontRuntimeService.php:407` via pattern `#^/shop/product/(...)$#`
- Generates category paths via `categoryPath()` at `StorefrontRuntimeService.php:1365-1371` → `/shop/category/...`
- Treats old `/products/...` paths as legacy redirects via `resolveRedirect()` at `StorefrontRuntimeService.php:1478-1499`

The old `/products/category/shoes` path no longer matches as a category route. Instead, the redirect resolver catches it and returns `status: 'redirect'` pointing to `/shop/category/shoes`.

### Evidence

The test at line 60-64 already demonstrates the pattern:
```php
// This test verifies the home page redirect - and it PASSES
$this->runtimeGet($store, ...)
    ->assertJsonPath('data.status', 'redirect')
    ->assertJsonPath('data.redirectTo', '/shop');
```

The `/products/` prefix is legacy and correctly redirects. The test was written before the migration and tested the `/products/` prefix directly.

## Failure C: `runtime seo contract is complete...` — canonical URL mismatch

### Root Cause

Same root cause. The canonical URL generator at `StorefrontRuntimeService.php` builds URLs using `/shop/category/...` and `/shop/product/...` patterns. The test expected the old `/products/` prefix.

## Fix

All three failures fixed by updating test URL paths to the current `/shop/` prefix:

| File | Line | Old Value | New Value |
|------|------|-----------|-----------|
| `StorefrontRuntimeTest.php` | 66 | `/products/category/shoes` | `/shop/category/shoes` |
| `StorefrontRuntimeTest.php` | 72 | `/products/red-shoe` | `/shop/product/red-shoe` |
| `StorefrontRuntimeTest.php` | 153 | `/ar/products/category/shoes-ar` | `/ar/shop/category/shoes-ar` |
| `StorefrontRuntimeTest.php` | 159 | `/ar/products/red-shoe-ar` | `/ar/shop/product/red-shoe-ar` |
| `StorefrontRuntimeTest.php` | 625-626 | `/products/category/shoes` | `/shop/category/shoes` |
| `StorefrontRuntimeTest.php` | 625-626 | `/products/red-shoe` | `/shop/product/red-shoe` |

## Regression Risk

**None.** The production code is correct and intentional. The `/products/` → `/shop/` migration is established behavior — the test at line 60-64 already relies on `/products/` paths returning `status: 'redirect'`. These fixes align test expectations with reality.

## Files Examined

| File | Verdict |
|------|---------|
| `app/Services/Storefront/StorefrontRuntimeService.php` | Routes use `/shop/` prefix — correct |
| `app/Services/Storefront/Seo/StorefrontSeoService.php` | Canonical URLs use `/shop/` — matches runtime |
| `routes/api/v1/storefront/runtime.php` | API endpoints correct |
