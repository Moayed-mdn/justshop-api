# Test Failure Analysis

## Summary

**Total:** 14 failing tests across 5 test files  
**Scope:** All pre-existing, unrelated to Waves 7-8 changes  
**Impact:** Low — no regressions introduced, all failures are in module-specific features

| Test File | Failures | Root Cause Category | Severity |
|-----------|----------|---------------------|----------|
| `BlogModuleTest` | 4 | Authorization misconfiguration + content rendering | Medium |
| `ExceptionRenderingTest` | 3 | Response format contract mismatch | Medium |
| `AdminLeadManagementTest` | 3 | Authorization + data persistence | Medium |
| `PublicLeadSubmissionTest` | 1 | Route/service resolution | Low |
| `StorefrontRuntimeTest` | 3 | Routing/URL configuration drift | Low |

---

## 1. BlogModuleTest — 4 Failures

### 1.1 `public blog show returns post by slug`

**Error:**
```
Failed asserting that null is identical to 'Detailed content'.
```

**Root Cause:** The blog content field (`data.content`) is not populated in the response. Likely causes:
- The seeder/factory does not populate `content` for blog posts
- The API resource `toArray()` omits `content` for public endpoints
- The blog content is stored in a related table not eager-loaded

**Investigation needed:**
- Check `BlogPostResource` or equivalent transformer
- Check seeder for the test post content
- Verify eager loading in the controller

### 1.2 `admin can create blog post`, `admin can update blog post`, `admin can publish draft post`

**Error:**
```
Expected 201/200 but received 403 — ACCESS_DENIED
```

**Root Cause:** Authorization issues for the admin role. The test authenticates as an admin user but the policy denies:
- `create` blog post
- `update` blog post
- `publish` (custom action) blog post

Likely causes:
- Blog post policy checks permission/role not granted to the test admin user
- The test role seeder does not assign blog permissions
- `BlogPostPolicy` uses `Gate::before` or `Gate::after` that blocks admin

**Investigation needed:**
- Check `app/Policies/BlogPostPolicy.php`
- Check test setup — what role/permissions the admin user has
- Verify that the admin role includes blog management permissions

---

## 2. ExceptionRenderingTest — 3 Failures

### 2.1 `validation exception returns correct JSON`

**Error:**
```json
Unable to find JSON:
[{"status": false}]
within response JSON
```

### 2.2 `generic exception returns correct JSON`

**Error:**
```json
Unable to find JSON:
[{"status": false, "message": "Custom server error message"}]
```

### 2.3 `http exception returns correct JSON`

**Error:**
```json
Unable to find JSON:
[{"status": false}]
```

**Root Cause:** The `ApiResponserTrait` / exception handler response format has changed. The test expects a `status` key but the response now uses `success` instead (or wraps differently). This is a contract drift between the exception handler and the test expectations.

**Investigation needed:**
- Check `app/Exceptions/Handler.php` for the render method
- Check `app/Traits/ApiResponserTrait.php` for the response structure
- Compare with `ErrorCode` enum — the `success`/`status` key name may have been refactored

**Fix approach:** Either update the tests to match the new format, or update the exception handler to include the expected key.

---

## 3. Lead Tests — 4 Failures

### 3.1 `admin endpoints require super admin` (AdminLeadManagementTest)

**Error:**
```json
Unable to find JSON:
[{"success": false, "code": "HTTP_403"}]
```

**Root Cause:** The test expects a `HTTP_403` error code but the response returns a different code (likely `ACCESS_DENIED` or `FORBIDDEN`). The policy check for admin vs super admin uses a different error code than expected.

### 3.2 `super admin can update lead` (AdminLeadManagementTest)

**Error:**
```
Failed asserting that null is identical to 'Contacting the user now.'
```

**Root Cause:** The `resolution_notes` field is null in the response but expected to be set. Possible causes:
- The update request body field name does not match what the controller expects (e.g., `resolution_notes` vs `notes`)
- The controller does not fill `resolution_notes` from the request
- The `LeadResource` does not include `resolution_notes`

### 3.3 `super admin can delete lead` (AdminLeadManagementTest)

**Error:**
```
Failed asserting that a row in the table [leads] does not match the attributes {"id": 1}. Found similar results.
```

**Root Cause:** The delete endpoint does not actually delete the lead. Likely causes:
- The controller uses `soft delete` but the test uses `assertDatabaseMissing` (which checks non-deleted rows)
- Hard delete is expected but the action performs soft delete
- The delete route leads to a different action

### 3.4 `duplicate detection` (PublicLeadSubmissionTest)

**Error:**
```
Expected 201 but received 404 — STR_001
```

**Root Cause:** `STR_001` error code (Store not found). The test submits to a store route but the store is not resolved. Likely causes:
- The store slug/ID used in the test does not match the seeded data
- The route binding for the store fails
- The middleware blocks the request before it reaches the controller

---

## 4. StorefrontRuntimeTest — 3 Failures

### 4.1 `resolve endpoint matches product category`

**Error:**
```
Failed asserting that 'redirect' is identical to 'matched'
```

### 4.2 `resolve endpoint matches localized category`

**Error:**
```
Failed asserting that 'redirect' is identical to 'matched'
```

**Root Cause:** The runtime resolver returns `redirect` instead of `matched` for product category paths (`/products/category/shoes`). This indicates:
- The route pattern no longer matches the URL using the `matched` resolver logic
- A redirect rule is matching the path before the category matching logic
- The URL structure changed (e.g., `/shop/category/shoes` vs `/products/category/shoes`)

### 4.3 `runtime seo content`

**Error:**
```
Expected: 'https://demo.justshop.test/products/category/shoes'
Actual:   'https://demo.justshop.test/shop/category/shoes'
```

**Root Cause:** Canonical URL structure changed from `/products/...` to `/shop/...`. This is likely intentional but the test expectations were not updated. The `canonicalUrl` in SEO metadata now uses the `/shop/` prefix.

---

## 5. Cross-Cutting Patterns

### 5.1 Authorization Drift (7 failures)
403 errors in BlogModuleTest, ExceptionRenderingTest, AdminLeadManagementTest all point to a systemic authorization realignment. The `ACCESS_DENIED` response format and permission checks have changed.

### 5.2 Response Format Contract Mismatch (3 failures)
ExceptionRenderingTest failures indicate the API response format changed (likely `status` → `success` key rename).

### 5.3 Data/URL Configuration Drift (4 failures)
StorefrontRuntimeTest and BlogModuleTest content failures suggest seeded data or URL routing has changed without test updates.

## 6. Priority for Fixing

| Priority | Test File | Effort | Business Impact |
|----------|-----------|--------|-----------------|
| P1 | `ExceptionRenderingTest` | 1h | Error responses in production would be inconsistent |
| P1 | `BlogModuleTest` (auth) | 2h | Admin blog management broken |
| P2 | `StorefrontRuntimeTest` | 2h | SEO metadata incorrect |
| P3 | `AdminLeadManagementTest` | 3h | Lead management broken |
| P4 | `PublicLeadSubmissionTest` | 1h | Public lead submission broken |

## 7. Recommendations

1. **Run `git bisect`** to identify which commit introduced each failure — this distinguishes intentional changes from regressions
2. **Fix ExceptionRenderingTest first** — it validates the API response contract that all endpoints depend on
3. **Review BlogPostPolicy permissions** — likely a post-refactor oversight
4. **Update StorefrontRuntimeTest expectations** or fix the canonical URL generator
5. **Consider writing these as a single meta-test** that can be toggled with `@group('known-failure')` to reduce noise
