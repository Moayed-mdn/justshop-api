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
| **Marketing / Platform** | Platform | NO | `marketing.platform.*` permissions | Next.js Marketing Site |
| **Marketing / Store** | Store | YES | `marketing.store.*` permissions | Storefront frontend (deferred) |
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
- Legacy table/model: `marketing_pages`, `App\Models\Cms\MarketingPage`
- Target table/model: `platform_marketing_pages`, `App\Models\Cms\Marketing\Platform\PlatformMarketingPage`
- Current compatibility routes: `/api/v1/admin/cms/pages/*`, `/api/v1/public/cms/pages/{slug}`
- Target admin routes: `/api/v1/admin/cms/platform/pages/*`
- Policy direction: `PlatformMarketingPolicy`
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

## Tenant-Owned Content

### Definition

Content managed by **store owners/admins** within their tenant scope.

### Characteristics

- ✅ MUST have `store_id` foreign key
- ✅ Managed via `/api/v1/admin/stores/{store}/cms/*` endpoints
- ✅ Requires store membership authorization
- ✅ Scoped queries: `->where('store_id', $storeId)`
- ✅ Consumed by Nuxt Storefront

### Current Planned Scope

Store-owned CMS currently means:

1. **Store Marketing Pages**
   - Custom landing pages per store
   - Store-specific campaigns and promotions
   - Tenant-scoped public marketing content

### Delivery Status

- Backend foundation may be prepared before storefront rollout.
- Store frontend integration is deferred until its contract is finalized.
- Store documentation and store blog remain out of scope unless explicitly approved later.

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
// Platform Marketing Pages
marketing.platform.view
marketing.platform.create
marketing.platform.update
marketing.platform.delete
marketing.platform.publish

// Store Marketing Pages
marketing.store.view
marketing.store.create
marketing.store.update
marketing.store.delete
marketing.store.publish

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
- Platform Marketing Pages: `/api/v1/admin/cms/pages/*` or `/api/v1/admin/cms/platform/pages/*`
- Blog Posts: `/api/v1/admin/cms/blog/*`
- Documentation: `/api/v1/admin/cms/docs/*`

**Characteristics:**
- Requires `super_admin` role
- NO store context
- Full CRUD operations

### Storefront Frontend (Tenant Content)

**Will consume when activated:**
- Store Pages: `/api/v1/stores/{store}/cms/pages/*`

**Characteristics:**
- Store-scoped content
- Tenant-specific branding
- Store membership required for management
- Public rollout may remain feature-flagged until the frontend is ready

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

### Adding Store-Owned CMS

If store-owned CMS is expanded:

**1. Create Separate Subdomain**
```
Cms/
├── Marketing/      # Platform (existing)
├── Blog/           # Platform (existing)
├── Documentation/  # Platform (existing)
├── Seo/            # Shared (existing)
└── Marketing/
    └── Store/
```

**2. Create Separate Tables**
```php
Schema::create('store_marketing_pages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('store_id')->constrained()->cascadeOnDelete();
    // ... other columns
    $table->index(['store_id', 'is_published']);
});
```

**3. Create Separate Policies**
```php
class StoreMarketingPolicy
{
    use HasStoreMembership;
    
    public function view(User $user, Store $store): bool
    {
        return $this->isMember($user, $store)
            && $user->can('marketing.store.view');
    }
}
```

**4. Update Routes**
```php
Route::prefix('v1/admin/stores/{store}/cms')
    ->middleware(['auth:sanctum', 'store.member:{store}'])
    ->group(function () {
        // Store marketing CMS routes
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
