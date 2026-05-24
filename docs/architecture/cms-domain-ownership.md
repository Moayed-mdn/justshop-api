# CMS Domain Ownership Model

## Overview

This document defines the **ownership boundaries** for all CMS content in the platform. Understanding ownership is critical for:

- Authorization decisions
- Data scoping
- Frontend routing
- Cache invalidation
- Multi-tenancy boundaries

---

## Ownership Classification

All CMS content is classified using `CmsOwnershipEnum`:

```php
enum CmsOwnershipEnum: string
{
    case PLATFORM = 'platform';  // Platform-level content
    case TENANT = 'tenant';      // Tenant-level content
    case SHARED = 'shared';      // Shared infrastructure
}
```

---

## Current Ownership Map

| Subdomain | Ownership | Store ID | Authorization | Frontend Consumer |
|:----------|:----------|:---------|:--------------|:------------------|
| **Marketing** | Platform | NO | `cms.page.*` permissions | Next.js Marketing Site |
| **Blog** | Platform | NO | `cms.blog.*` permissions | Next.js Marketing Site |
| **Documentation** | Platform | NO | `cms.doc.*` permissions | Next.js Marketing Site |
| **SEO Services** | Shared | N/A | N/A | All frontends |

---

## Platform-Owned Content

### Definition

Content managed by **super admins** at the platform level. NOT scoped to individual stores/tenants.

### Characteristics

- ❌ NO `store_id` foreign key
- ✅ Managed via `/api/v1/admin/cms/*` endpoints
- ✅ Requires `role:super_admin` middleware
- ✅ Uses permission-based authorization (`cms.*` permissions)
- ✅ Consumed by Next.js Marketing Site
- ✅ Shared across all tenants

### Current Platform Content

**1. Marketing Pages**
- Table: `marketing_pages`
- Model: `App\Models\Cms\MarketingPage`
- Policy: `MarketingPagePolicy`
- Examples: Home, About, Features, Pricing, Contact

**2. Blog Posts**
- Table: `blog_posts`
- Model: `App\Models\BlogPost`
- Policy: `BlogPostPolicy`
- Examples: Product announcements, tutorials, company news

**3. Documentation**
- Tables: `cms_documents`, `cms_document_sections`
- Models: `App\Models\Cms\CmsDocument`, `App\Models\Cms\CmsDocumentSection`
- Policy: `CmsDocumentPolicy`
- Examples: API docs, user guides, developer documentation

### Migration History

Documentation was **originally tenant-scoped** but was migrated to platform-level:

```php
// Migration: 2026_05_21_045354_remove_store_scoping_from_documentation_cms.php
Schema::table('cms_documents', function (Blueprint $table) {
    $table->dropForeign(['store_id']);
    $table->dropColumn('store_id');
});
```

**Rationale:** Documentation is product-level content, not store-specific.

---

## Tenant-Owned Content (Future)

### Definition

Content managed by **store owners/admins** within their tenant scope.

### Characteristics (When Implemented)

- ✅ MUST have `store_id` foreign key
- ✅ Managed via `/api/v1/admin/stores/{store}/cms/*` endpoints
- ✅ Requires store membership authorization
- ✅ Scoped queries: `->where('store_id', $storeId)`
- ✅ Consumed by Nuxt Storefront

### Potential Future Use Cases

**NOT CURRENTLY IMPLEMENTED:**

1. **Store-Specific Pages**
   - Custom landing pages per store
   - Store-specific terms & conditions
   - Store-specific FAQs

2. **Store-Specific Documentation**
   - Custom product guides per store
   - Store-specific help articles

**Decision:** Out of scope for current architecture. If needed, create separate subdomain under `Cms/Tenant/`.

---

## Shared Infrastructure

### Definition

Services and utilities used by **both platform and tenant content**.

### Current Shared Components

**1. SEO Services**
- `SeoResolutionService` - Locale resolution, robots handling
- `CanonicalUrlService` - URL generation
- `StructuredDataService` - JSON-LD generation
- `SitemapService` - Sitemap generation

**2. Localization Services**
- `LocalizedContentResolver` - JSON locale resolution

**3. Cache Services**
- `MarketingPageCacheService` - Cache invalidation
- `IsrRevalidationService` - Next.js ISR revalidation

**4. Contracts**
- `HasSeoMetadata` - SEO metadata interface
- `HasLocalizedContent` - Localized content interface

---

## Authorization Boundaries

### Platform Content Authorization

**Flow:**
1. Route middleware: `auth:sanctum`, `verified`, `role:super_admin`
2. Controller: `$this->authorize('action', Model::class)`
3. Policy: `$user->can(PermissionEnum::CMS_*)`

**Permissions:**
```php
// Marketing Pages
cms.page.view
cms.page.create
cms.page.update
cms.page.delete
cms.page.publish

// Blog
cms.blog.view
cms.blog.create
cms.blog.update
cms.blog.delete
cms.blog.publish

// Documentation
cms.doc.view
cms.doc.create
cms.doc.update
cms.doc.delete
cms.doc.publish
```

### Tenant Content Authorization (Future)

**Flow (when implemented):**
1. Route middleware: `auth:sanctum`, `verified`, `store.member:{store}`
2. Controller: `$this->authorize('action', [$model, $storeId])`
3. Policy: Check store membership + permissions

---

## Frontend Ownership Boundaries

### Next.js Marketing Site (Platform Content)

**Consumes:**
- Marketing Pages: `/api/v1/public/cms/pages/{slug}`
- Blog Posts: `/api/v1/public/cms/blog/*`
- Documentation: `/api/v1/public/cms/docs/*`
- SEO: `/api/v1/public/cms/seo/*`

**Characteristics:**
- NO authentication required
- NO store context
- Global platform content
- ISR caching strategy

### Next.js Dashboard (Platform Admin)

**Manages:**
- Marketing Pages: `/api/v1/admin/cms/pages/*`
- Blog Posts: `/api/v1/admin/cms/blog/*`
- Documentation: `/api/v1/admin/cms/docs/*`

**Characteristics:**
- Requires `super_admin` role
- NO store context
- Full CRUD operations

### Nuxt Storefront (Tenant Content - Future)

**Would consume (if implemented):**
- Store Pages: `/api/v1/stores/{store}/cms/pages/*`
- Store Docs: `/api/v1/stores/{store}/cms/docs/*`

**Characteristics:**
- Store-scoped content
- Tenant-specific branding
- Store membership required for management

---

## Database Scoping Rules

### Platform Content Queries

**✅ Correct:**
```php
// No store_id filtering needed
MarketingPage::where('is_published', true)->get();
BlogPost::where('slug->en', $slug)->first();
CmsDocument::where('is_published', true)->get();
```

**❌ Forbidden:**
```php
// NEVER add store_id to platform content
MarketingPage::where('store_id', $storeId)->get(); // WRONG
```

### Tenant Content Queries (Future)

**✅ Correct (when implemented):**
```php
// MUST scope by store_id
TenantPage::where('store_id', $storeId)->get();
```

**❌ Forbidden:**
```php
// NEVER query across stores
TenantPage::all(); // WRONG - leaks cross-tenant data
```

---

## Migration Strategy

### Adding Tenant-Scoped CMS (Future)

If tenant-scoped CMS is needed:

**1. Create Separate Subdomain**
```
Cms/
├── Marketing/      # Platform (existing)
├── Blog/           # Platform (existing)
├── Documentation/  # Platform (existing)
├── Seo/            # Shared (existing)
└── Tenant/         # NEW
    ├── Pages/
    └── Documentation/
```

**2. Create Separate Tables**
```php
Schema::create('tenant_cms_pages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('store_id')->constrained()->cascadeOnDelete();
    // ... other columns
    $table->index(['store_id', 'is_published']);
});
```

**3. Create Separate Policies**
```php
class TenantPagePolicy
{
    use HasStoreMembership;
    
    public function view(User $user, TenantPage $page): bool
    {
        return $this->userBelongsToStore($user, $page->store_id)
            && $user->can('tenant.cms.page.view');
    }
}
```

**4. Update Routes**
```php
Route::prefix('v1/admin/stores/{store}/cms')
    ->middleware(['auth:sanctum', 'store.member:{store}'])
    ->group(function () {
        // Tenant CMS routes
    });
```

---

## Key Architectural Decisions

### ✅ Decision: Platform-Level Documentation

**Rationale:**
- Documentation describes the **product**, not individual stores
- Reduces duplication across tenants
- Simplifies maintenance
- Consistent documentation experience

**Evidence:** Migration `2026_05_21_045354` removed `store_id` from documentation tables.

### ✅ Decision: Platform-Level Blog

**Rationale:**
- Blog represents **platform announcements and content marketing**
- NOT store-specific news
- Shared across all tenants
- Managed by platform marketing team

### ✅ Decision: Unified SEO Infrastructure

**Rationale:**
- SEO rules are consistent across content types
- Avoids duplication
- Centralized environment handling (staging = noindex)
- Single contract for frontend integration

---

## Ownership Verification Checklist

When adding new CMS content types, verify:

- [ ] Is this platform-level or tenant-level content?
- [ ] Does the table have/need `store_id`?
- [ ] Which frontend consumes this content?
- [ ] What authorization model applies?
- [ ] Are queries properly scoped?
- [ ] Is the ownership documented?
- [ ] Are policies correctly implemented?

---

## Related Documentation

- [CMS Architecture](../CMS_MARKETING_ARCHITECTURE.md)
- [Authorization Doctrine](../ARCHITECTURE.md#authorization-doctrine)
- [Multi-Tenancy Rules](../ARCHITECTURE.md#multi-store-dtos)
