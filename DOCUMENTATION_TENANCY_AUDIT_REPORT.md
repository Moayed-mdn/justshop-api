# Documentation Domain Tenancy Verification Audit

**Date:** May 24, 2026  
**Type:** READ-ONLY Verification Audit  
**Scope:** Documentation Domain Tenancy Model  
**Auditor:** Architecture Verification System

---

## Executive Summary

### ⚠️ CRITICAL FINDING: INTENTIONAL ARCHITECTURE CHANGE

**The Documentation domain was INTENTIONALLY changed from tenant-scoped to platform-scoped BEFORE the stabilization pass.**

**This was NOT an accident caused by the stabilization pass.**

---

## Audit Questions & Answers

### 1. Is Documentation currently tenant-scoped or platform-scoped?

**Answer: PLATFORM-SCOPED**

**Evidence:**
- ✅ NO `store_id` column in `cms_documents` table
- ✅ NO `store_id` column in `cms_document_sections` table
- ✅ Migration `2026_05_21_045354_remove_store_scoping_from_documentation_cms.php` explicitly removed `store_id`
- ✅ All queries are platform-wide (no store filtering)
- ✅ Routes have NO store context (`/api/v1/admin/cms/docs/*` NOT `/api/v1/admin/stores/{store}/cms/docs/*`)
- ✅ Policy has NO store membership checks
- ✅ DTOs have NO `storeId` parameter

---

### 2. Did any stabilization changes accidentally alter tenancy behavior?

**Answer: NO**

**Evidence:**
- The stabilization pass (May 24, 2026) did NOT change tenancy behavior
- The tenancy change occurred on **May 21, 2026** via migration `2026_05_21_045354`
- The stabilization pass only:
  - Created `CmsDocumentPolicy` (platform-level, no store awareness)
  - Added authorization checks to controllers
  - Updated documentation
  - Created shared contracts

**Timeline:**
1. **May 20, 2026:** Documentation created WITH `store_id` (tenant-scoped)
2. **May 21, 2026:** Migration removed `store_id` (changed to platform-scoped)
3. **May 24, 2026:** Stabilization pass (NO tenancy changes)

---

### 3. Are any documentation queries missing store scoping?

**Answer: NO - Store scoping is intentionally absent (platform-level design)**

**Evidence from `CmsDocumentRepository`:**

```php
// ✅ CORRECT: No store_id filtering (platform-level)
public function findById(int $id): ?CmsDocument
{
    return CmsDocument::find($id);
}

public function getPublishedDocuments(): Collection
{
    return CmsDocument::published()
        ->orderBy('sort_order')
        ->orderBy('id')
        ->get();
}

public function getSidebarTree(): Collection
{
    return CmsDocument::whereNull('parent_id')
        ->published()
        ->with(['children' => fn($q) => $q->published()->orderBy('sort_order')->orderBy('id')])
        ->orderBy('sort_order')
        ->orderBy('id')
        ->get();
}
```

**All queries are intentionally platform-wide.**

---

### 4. Are docs routes properly tenant-aware?

**Answer: NO - Routes are intentionally platform-level (NOT tenant-aware)**

**Admin Routes:**
```php
// ✅ CORRECT: Platform-level (no store context)
Route::prefix('v1/admin/cms')->middleware(['auth:sanctum', 'verified', 'role:super_admin'])
    ->group(function () {
        Route::prefix('docs')->controller(AdminDocumentController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{id}', 'show');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
            Route::post('/{id}/publish', 'publish');
            Route::post('/reorder', 'reorder');
        });
    });
```

**Public Routes:**
```php
// ✅ CORRECT: Platform-level (no store context)
Route::prefix('v1/public/cms')->group(function () {
    Route::prefix('docs')->controller(PublicDocumentController::class)->group(function () {
        Route::get('/sidebar', 'sidebar');
        Route::get('/{slugPath}/navigation', 'navigation')->where('slugPath', '.*');
        Route::get('/{slugPath}', 'show')->where('slugPath', '.*');
    });
});
```

**Comparison with Tenant-Scoped Routes:**

For reference, tenant-scoped routes look like this:
```php
// ❌ NOT PRESENT: No tenant-scoped documentation routes
Route::prefix('v1/admin/stores/{store}/cms/docs') // DOES NOT EXIST
```

**Conclusion:** Documentation routes are intentionally platform-level.

---

### 5. Are docs policies tenant-aware?

**Answer: NO - Policy is intentionally platform-level (NOT tenant-aware)**

**Evidence from `CmsDocumentPolicy`:**

```php
class CmsDocumentPolicy
{
    use InteractsWithPolicyTelemetry; // ✅ NOT HasStoreMembership

    public function viewAny(User $user): bool
    {
        return $this->decision(
            $user,
            'viewAny',
            $user->can(PermissionEnum::CMS_DOC_VIEW) // ✅ Permission-based, NOT store-based
        );
    }

    public function view(User $user, CmsDocument $document): bool
    {
        return $this->decision(
            $user,
            'view',
            $user->can(PermissionEnum::CMS_DOC_VIEW), // ✅ No store membership check
            $document
        );
    }
    
    // ... other methods follow same pattern
}
```

**Comparison with Tenant-Aware Policies:**

For reference, tenant-aware policies look like this:
```php
// Example: ProductPolicy (tenant-aware)
class ProductPolicy
{
    use HasStoreMembership; // ❌ NOT used in CmsDocumentPolicy

    public function viewAny(User $user, Store $store): bool
    {
        return $this->userBelongsToStore($user, $store->id)
            && $user->can('products.view');
    }
}
```

**Conclusion:** `CmsDocumentPolicy` is intentionally platform-level with NO store awareness.

---

### 6. Are storefront/public docs APIs still isolated correctly?

**Answer: YES - Public docs APIs are correctly isolated (platform-level, no cross-tenant exposure)**

**Public Documentation Controller:**
```php
class PublicDocumentController extends Controller
{
    public function sidebar(): JsonResponse
    {
        $tree = $this->repository->getSidebarTree(); // ✅ Platform-wide
        return $this->success(['items' => PublicSidebarResource::collection($tree)]);
    }

    public function show(string $slugPath): JsonResponse
    {
        $document = $this->resolveAction->execute($slugPath); // ✅ Platform-wide
        
        if (!$document) {
            return $this->error('Document not found', 404);
        }

        return $this->success(new PublicDocumentResource($document));
    }
}
```

**Characteristics:**
- ✅ NO authentication required
- ✅ NO store context
- ✅ Returns only published content
- ✅ Platform-wide documentation (shared across all tenants)

**This is CORRECT for platform-level documentation.**

---

### 7. Is there any accidental cross-tenant exposure risk?

**Answer: NO - There is NO cross-tenant exposure risk because documentation is intentionally platform-level**

**Rationale:**

Documentation is **product-level content**, not tenant-specific content. It describes:
- How to use the platform
- API documentation
- User guides
- Developer documentation

**This is shared across ALL tenants by design.**

**Evidence of Intentional Design:**

1. **Migration Name:** `remove_store_scoping_from_documentation_cms.php`
   - Explicitly states intent to remove store scoping

2. **Migration Date:** May 21, 2026 (3 days BEFORE stabilization pass)
   - This was a deliberate architectural decision

3. **Documentation:** Updated to reflect platform-level ownership
   - `CMS_MARKETING_ARCHITECTURE.md` states: "Documentation is platform-level"
   - `cms-domain-ownership.md` explains the rationale

4. **No Tenant-Scoped Alternatives:** No separate tenant documentation tables exist

---

## Database Schema Evolution

### Original Schema (May 20, 2026)

```php
// cms_documents table
Schema::create('cms_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('store_id')->constrained()->cascadeOnDelete(); // ❌ TENANT-SCOPED
    $table->foreignId('section_id')->nullable()->constrained('cms_document_sections')->nullOnDelete();
    // ... other columns
    $table->index(['store_id', 'is_published', 'published_at']);
});

// cms_document_sections table
Schema::create('cms_document_sections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('store_id')->constrained()->cascadeOnDelete(); // ❌ TENANT-SCOPED
    // ... other columns
    $table->index(['store_id', 'is_published', 'published_at']);
});
```

### Current Schema (After May 21, 2026)

```php
// cms_documents table
Schema::table('cms_documents', function (Blueprint $table) {
    $table->dropForeign(['store_id']);
    $table->dropIndex(['store_id', 'is_published', 'published_at']);
    $table->dropColumn('store_id'); // ✅ PLATFORM-SCOPED
    $table->index(['is_published', 'published_at']);
});

// cms_document_sections table
Schema::table('cms_document_sections', function (Blueprint $table) {
    $table->dropForeign(['store_id']);
    $table->dropIndex(['store_id', 'is_published', 'published_at']);
    $table->dropColumn('store_id'); // ✅ PLATFORM-SCOPED
    $table->index(['is_published', 'published_at']);
});
```

---

## Verification Checklist

### Database Layer
- [x] ✅ NO `store_id` in `cms_documents` table
- [x] ✅ NO `store_id` in `cms_document_sections` table
- [x] ✅ NO store-related foreign keys
- [x] ✅ NO store-related indexes

### Model Layer
- [x] ✅ `CmsDocument` model has NO `store_id` in fillable
- [x] ✅ `CmsDocument` model has NO store relationship
- [x] ✅ `CmsDocumentSection` model has NO `store_id` in fillable
- [x] ✅ `CmsDocumentSection` model has NO store relationship

### Repository Layer
- [x] ✅ `CmsDocumentRepository` has NO store scoping in queries
- [x] ✅ All queries are platform-wide
- [x] ✅ NO `where('store_id', ...)` clauses

### DTO Layer
- [x] ✅ `CreateDocumentDTO` has NO `storeId` parameter
- [x] ✅ `UpdateDocumentDTO` has NO `storeId` parameter
- [x] ✅ NO store context in any documentation DTOs

### Action Layer
- [x] ✅ `CreateDocumentAction` has NO store scoping
- [x] ✅ All actions operate platform-wide

### Controller Layer
- [x] ✅ `AdminDocumentController` has NO store parameter
- [x] ✅ `PublicDocumentController` has NO store parameter
- [x] ✅ NO store context injection

### Route Layer
- [x] ✅ Admin routes: `/api/v1/admin/cms/docs/*` (NO store context)
- [x] ✅ Public routes: `/api/v1/public/cms/docs/*` (NO store context)
- [x] ✅ NO routes under `/api/v1/admin/stores/{store}/cms/docs/*`
- [x] ✅ NO routes under `/api/v1/storefront/docs/*`

### Policy Layer
- [x] ✅ `CmsDocumentPolicy` uses `InteractsWithPolicyTelemetry` (NOT `HasStoreMembership`)
- [x] ✅ Policy checks permissions only (NO store membership)
- [x] ✅ NO store-aware authorization

### Middleware Layer
- [x] ✅ Admin routes use `role:super_admin` (platform-level)
- [x] ✅ NO store membership middleware
- [x] ✅ Public routes have NO authentication (platform-wide content)

---

## Architectural Rationale

### Why Platform-Level Documentation?

**Decision:** Documentation is platform-level, NOT tenant-scoped.

**Rationale:**

1. **Product Documentation:** Documentation describes the **platform/product**, not individual stores
   - API documentation
   - User guides
   - Developer documentation
   - Feature explanations

2. **Reduces Duplication:** Avoids duplicating identical documentation across thousands of tenants

3. **Simplifies Maintenance:** Single source of truth for platform documentation

4. **Consistent Experience:** All tenants see the same documentation

5. **Reduces Storage:** No need to replicate documentation per tenant

**Evidence:** Migration `2026_05_21_045354` explicitly removed store scoping with clear intent.

---

## Comparison: Platform vs Tenant Content

### Platform-Level CMS Content (Current)

| Content Type | Store ID | Authorization | Routes |
|:-------------|:---------|:--------------|:-------|
| Marketing Pages | NO | `cms.page.*` | `/api/v1/admin/cms/pages/*` |
| Blog Posts | NO | `cms.blog.*` | `/api/v1/admin/cms/blog/*` |
| **Documentation** | **NO** | **`cms.doc.*`** | **`/api/v1/admin/cms/docs/*`** |

### Tenant-Level Commerce Content (For Comparison)

| Content Type | Store ID | Authorization | Routes |
|:-------------|:---------|:--------------|:-------|
| Products | YES | Store membership + `products.*` | `/api/v1/admin/stores/{store}/products/*` |
| Orders | YES | Store membership + `orders.*` | `/api/v1/admin/stores/{store}/orders/*` |
| Categories | YES | Store membership + `categories.*` | `/api/v1/admin/stores/{store}/categories/*` |

**Documentation follows the Platform-Level pattern, NOT the Tenant-Level pattern.**

---

## Stabilization Pass Impact Analysis

### What the Stabilization Pass Changed (May 24, 2026)

1. ✅ Created `CmsDocumentPolicy` (platform-level, no store awareness)
2. ✅ Added authorization checks to `AdminDocumentController`
3. ✅ Added permission constants (`CMS_DOC_*`)
4. ✅ Updated documentation to reflect platform-level ownership
5. ✅ Created shared contracts (`HasSeoMetadata`, `HasLocalizedContent`)

### What the Stabilization Pass Did NOT Change

1. ❌ Did NOT change database schema (already platform-level)
2. ❌ Did NOT remove `store_id` (already removed on May 21)
3. ❌ Did NOT change routes (already platform-level)
4. ❌ Did NOT change repository queries (already platform-wide)
5. ❌ Did NOT change DTOs (already had no `storeId`)
6. ❌ Did NOT change tenancy model (already platform-level)

**Conclusion:** The stabilization pass did NOT alter tenancy behavior.

---

## Risk Assessment

### Cross-Tenant Exposure Risk: NONE

**Reason:** Documentation is intentionally platform-level (shared across all tenants).

**This is NOT a security issue because:**
- Documentation describes the platform, not tenant-specific data
- All tenants should see the same documentation
- No tenant-specific sensitive information in documentation

### Data Isolation Risk: NONE

**Reason:** Platform-level documentation is the correct architectural pattern for product documentation.

### Authorization Risk: NONE

**Reason:**
- Platform-level authorization is correctly implemented
- Only super admins can manage documentation
- Public documentation is read-only and platform-wide

---

## Recommendations

### ✅ No Action Required

The current architecture is **correct and intentional**:

1. ✅ Documentation is platform-level by design
2. ✅ Migration history shows intentional change
3. ✅ All layers are consistent (no store scoping)
4. ✅ Authorization is correctly implemented
5. ✅ No cross-tenant exposure risk
6. ✅ Stabilization pass did NOT alter tenancy behavior

### 📝 Documentation Clarity

**Recommendation:** Ensure all team members understand that:
- Documentation is **platform-level** (describes the product)
- If tenant-specific documentation is needed in the future, create a separate subdomain (`Cms/Tenant/Documentation/`)
- Current documentation is shared across all tenants by design

---

## Conclusion

### Final Verdict

**✅ VERIFICATION PASSED**

1. **Documentation is currently PLATFORM-SCOPED** (intentional)
2. **Stabilization pass did NOT accidentally alter tenancy behavior**
3. **NO documentation queries are missing store scoping** (intentionally platform-wide)
4. **Routes are intentionally platform-level** (NOT tenant-aware)
5. **Policy is intentionally platform-level** (NOT tenant-aware)
6. **Public docs APIs are correctly isolated** (platform-wide, no cross-tenant exposure)
7. **NO accidental cross-tenant exposure risk** (platform-level by design)

### Key Finding

**The Documentation domain was INTENTIONALLY changed from tenant-scoped to platform-scoped on May 21, 2026 (3 days BEFORE the stabilization pass).**

**The stabilization pass (May 24, 2026) did NOT change tenancy behavior. It only added authorization, policies, and documentation.**

### Architecture Status

**✅ CORRECT AND CONSISTENT**

All layers are aligned:
- Database: NO `store_id`
- Models: NO store relationships
- Repositories: NO store scoping
- DTOs: NO `storeId` parameters
- Actions: NO store context
- Controllers: NO store parameters
- Routes: NO store context
- Policies: NO store membership checks
- Middleware: Platform-level authorization

**The architecture is stable, consistent, and intentional.**

---

## Audit Metadata

**Audit Date:** May 24, 2026  
**Audit Type:** READ-ONLY Verification  
**Files Examined:** 25+  
**Migrations Reviewed:** 3  
**Code Changes Made:** 0 (READ-ONLY)  
**Findings:** No accidental tenancy changes  
**Status:** ✅ PASSED

---

**End of Audit Report**
