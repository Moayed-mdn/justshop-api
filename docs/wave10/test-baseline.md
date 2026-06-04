# Wave 10 — Test Baseline

## Pre-Fix State

**Full suite:** 229 passed, 14 failed (1222 assertions)  
**Duration:** 17.72s

---

## Failure Inventory

### 1. ExceptionRenderingTest — 3 Failures

| Test | Expected | Actual | Root Cause |
|------|----------|--------|------------|
| `validation exception returns custom json format` | `['status' => false]` | `{'success': false, ...}` | Response key is `success`, not `status` |
| `generic exception returns custom json format` | `['status' => false, 'message' => '...']` | `{'success': false, ...}` | Same key mismatch |
| `http exception returns correct status code` | `['status' => false]` | `{'success': false, ...}` | Same key mismatch |

### 2. BlogModuleTest — 4 Failures

| Test | Expected | Actual | Root Cause |
|------|----------|--------|------------|
| `public blog show returns post by slug` | `data.content = 'Detailed content'` | `data.content = null` | Route name mismatch in `PublicBlogPostResource` |
| `admin can create blog post` | 201 | 403 ACCESS_DENIED | Permission seeder missing + merchant-guard permissions not created |
| `admin can update blog post` | 200 | 403 ACCESS_DENIED | Same |
| `admin can publish draft post` | 200 | 403 ACCESS_DENIED | Same |

### 3. AdminLeadManagementTest — 3 Failures

| Test | Expected | Actual | Root Cause |
|------|----------|--------|------------|
| `admin endpoints require super admin role` | `code = 'HTTP_403'` | `code = 'IDENTITY_DOMAIN_MISMATCH'` | Identity middleware blocks before policy fires |
| `super admin can update status and resolution fields` | `data.resolution_notes = '...'` | `data.resolution_notes = null` | `resolution_notes` column/DTO/action/resource chain incomplete |
| `super admin can delete leads` | `assertDatabaseMissing` | row found | Soft delete — need `assertSoftDeleted` |

### 4. PublicLeadSubmissionTest — 1 Failure

| Test | Expected | Actual | Root Cause |
|------|----------|--------|------------|
| `duplicate detection blocks same submission` | 201 | 404 STR_001 | Hardcoded wrong URL path |

### 5. StorefrontRuntimeTest — 3 Failures

| Test | Expected | Actual | Root Cause |
|------|----------|--------|------------|
| `resolve endpoint returns contract shape...` | `status = 'matched'` | `status = 'redirect'` | Old `/products/` prefix → now `/shop/` |
| `resolve endpoint supports locale prefixed arabic paths` | `status = 'matched'` | `status = 'redirect'` | Same |
| `runtime seo contract is complete...` | `/products/category/shoes` | `/shop/category/shoes` | Canonical URL prefix changed |

---

## Fix Strategy Summary

| Category | Verdict | Fix Type |
|----------|---------|----------|
| ExceptionRenderingTest | Test outdated (response format uses `success`) | Update test |
| BlogModuleTest - content | Production bug (wrong route name in resource) | Fix resource |
| BlogModuleTest - auth | Production bugs (seeder missing loop, no merchant-guard perms) + test setup | Fix seeder + test |
| AdminLeadManagementTest - code | Test outdated (middleware returns different error code) | Update test |
| AdminLeadManagementTest - resolution_notes | Production bug (incomplete feature implementation) | New migration + DTO + action + resource |
| AdminLeadManagementTest - delete | Test assertion wrong (soft delete) | Update test |
| PublicLeadSubmissionTest | Test outdated (wrong URL, wrong assertion keys) | Update test |
| StorefrontRuntimeTest | Test outdated (`/products/` → `/shop/` migration) | Update test |
