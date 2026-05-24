# CMS Architecture

## Overview

The CMS domain is a **platform-level content management system** for marketing, blog, and documentation content. It is intentionally separate from the tenant/store commerce architecture.

## CMS Subdomain Organization

The CMS domain is organized into **4 primary subdomains** under a unified umbrella:

```
Cms/
├── Marketing/      # Platform marketing pages (home, about, features, etc.)
├── Blog/           # Platform blog posts with categories and tags
├── Documentation/  # Platform documentation with hierarchical structure
└── Seo/            # Shared SEO infrastructure for all CMS content
```

### Subdomain Responsibilities

| Subdomain | Purpose | Ownership | Storage |
|:----------|:--------|:----------|:--------|
| **Marketing** | Landing pages, feature pages, pricing | Platform | JSON columns |
| **Blog** | Blog posts with categories, tags, authors | Platform | JSON columns |
| **Documentation** | Product docs with sections and hierarchy | Platform | JSON columns |
| **SEO** | Unified SEO metadata, sitemaps, structured data | Shared | Services |

---

## Platform vs Tenant Ownership

### Current Ownership Model

All CMS content is **platform-owned** and managed by super admins:

- ✅ `marketing_pages` - NO `store_id` (platform-level)
- ✅ `blog_posts` - NO `store_id` (platform-level)
- ✅ `cms_documents` - NO `store_id` (platform-level, store_id removed via migration)
- ✅ `cms_document_sections` - NO `store_id` (platform-level)

### Authorization Model

All CMS modules use **permission-based authorization** via Laravel Policies:

| Module | Policy | Permissions |
|:-------|:-------|:------------|
| Marketing Pages | `MarketingPagePolicy` | `cms.page.view`, `cms.page.create`, `cms.page.update`, `cms.page.delete`, `cms.page.publish` |
| Blog | `BlogPostPolicy` | `cms.blog.view`, `cms.blog.create`, `cms.blog.update`, `cms.blog.delete`, `cms.blog.publish` |
| Documentation | `CmsDocumentPolicy` | `cms.doc.view`, `cms.doc.create`, `cms.doc.update`, `cms.doc.delete`, `cms.doc.publish` |

**Authorization Flow:**
1. Route middleware: `auth:sanctum`, `verified`, `role:super_admin`
2. Controller: `$this->authorize('action', Model::class)`
3. Policy: `$user->can(PermissionEnum::CMS_*)`

This provides:
- Consistent authorization pattern across all CMS modules
- Granular permission control
- Policy telemetry for observability
- Future extensibility for role-based access

---

## Unified SEO Architecture

All CMS modules share the **same SEO contract** to ensure consistent frontend integration.

### SEO Layers

**1. Storage Layer (`SeoMetaDTO`)**
- Localized fields stored as JSON maps: `{"en": "...", "ar": "..."}`
- Fields: `meta_title`, `meta_description`, `og_image`, `og_title`, `og_description`
- Non-localized: `canonical_url`, `robots`, `structured_data`, `twitter_card`

**2. Resolution Layer (`SeoResolutionService`)**
- Resolves locale-specific values from JSON maps
- Generates hreflang alternates for all supported locales
- Applies environment-aware robots (staging = noindex)
- Applies draft content robots override (draft = noindex,nofollow)
- Generates default structured data (Article, TechArticle, Website)

**3. Response Layer (`SeoResource`)**
- Delivers flat JSON ready for Next.js `generateMetadata()`
- Contract includes: `meta_title`, `meta_description`, `canonical_url`, `og`, `twitter`, `robots`, `alternates`, `structured_data`

### SEO Services

| Service | Responsibility |
|:--------|:---------------|
| `SeoResolutionService` | Locale resolution, environment rules, draft handling |
| `CanonicalUrlService` | Canonical URL generation, hreflang alternates |
| `StructuredDataService` | JSON-LD generation (Article, TechArticle, Website, Organization) |
| `SitemapService` | Sitemap generation for all CMS modules |
| `IsrRevalidationService` | Next.js ISR cache invalidation |

### Benefits

- **Single source of truth** for SEO metadata structure
- **Consistent frontend contract** across all CMS content types
- **No duplication** of SEO transformation logic
- **Centralized** environment and draft handling
- **Type-safe** DTOs for all SEO operations

---

## Frontend API Boundaries

### Public API (Next.js Marketing Site / Nuxt Storefront)

**Endpoints:**
```
GET /api/v1/public/cms/pages/{slug}              # Marketing page
GET /api/v1/public/cms/blog                      # Blog post list
GET /api/v1/public/cms/blog/{slug}               # Single blog post
GET /api/v1/public/cms/docs/sidebar              # Documentation sidebar
GET /api/v1/public/cms/docs/{slugPath}           # Document by path
GET /api/v1/public/cms/docs/{slugPath}/navigation # Document navigation
GET /api/v1/public/cms/seo/sitemap/marketing     # Marketing sitemap
GET /api/v1/public/cms/seo/sitemap/blog          # Blog sitemap
GET /api/v1/public/cms/seo/sitemap/docs          # Docs sitemap
GET /api/v1/public/cms/seo/robots.txt            # Robots.txt
```

**Characteristics:**
- NO authentication required
- Returns only published content (`published_at <= now()`)
- Locale-aware (resolves JSON localized fields)
- Includes unified SEO payload via `SeoResource`
- Cache-friendly responses for ISR

### Admin API (Next.js Dashboard)

**Authentication:**
- `auth:sanctum` + `verified` + `role:super_admin`
- Policy-based authorization in controllers

**Endpoints:**
```
# Marketing Pages
GET    /api/v1/admin/cms/pages
POST   /api/v1/admin/cms/pages
GET    /api/v1/admin/cms/pages/{id}
PUT    /api/v1/admin/cms/pages/{id}
DELETE /api/v1/admin/cms/pages/{id}
POST   /api/v1/admin/cms/pages/{id}/publish

# Blog
GET    /api/v1/admin/cms/blog
POST   /api/v1/admin/cms/blog
GET    /api/v1/admin/cms/blog/{blogPost}
PUT    /api/v1/admin/cms/blog/{blogPost}
DELETE /api/v1/admin/cms/blog/{blogPost}
POST   /api/v1/admin/cms/blog/{blogPost}/publish
POST   /api/v1/admin/cms/blog/{blogPost}/unpublish
POST   /api/v1/admin/cms/blog/{blogPost}/schedule

# Documentation
GET    /api/v1/admin/cms/docs
POST   /api/v1/admin/cms/docs
GET    /api/v1/admin/cms/docs/{id}
PUT    /api/v1/admin/cms/docs/{id}
DELETE /api/v1/admin/cms/docs/{id}
POST   /api/v1/admin/cms/docs/{id}/publish
POST   /api/v1/admin/cms/docs/reorder
```

---

## Controller Organization

All admin controllers follow a **consistent namespace pattern**:

```
App\Http\Controllers\Api\Admin\Cms\
├── MarketingPage\
│   └── AdminMarketingPageController
├── Blog\
│   └── AdminBlogController
└── Documentation\
    ├── AdminDocumentController
    └── AdminDocumentSectionController
```

All public controllers follow:

```
App\Http\Controllers\Api\Cms\
├── Marketing\
│   └── PublicMarketingController
├── Blog\
│   └── PublicBlogController
├── Documentation\
│   └── PublicDocumentController
└── Seo\
    └── PublicCmsSeoController
```

**Rules:**
- Admin controllers MUST be under `Api\Admin\Cms\{Subdomain}\`
- Public controllers MUST be under `Api\Cms\{Subdomain}\`
- Controllers MUST use policy authorization via `$this->authorize()`
- Controllers MUST be thin (delegate to Actions)

---

## Localization Strategy

All CMS content uses **JSON-localized columns** for translatable fields.

### Storage Pattern

```php
protected $casts = [
    'title'   => 'array',
    'slug'    => 'array',
    'content' => 'array',
    'seo'     => 'array',
];
```

### Payload Shape

```json
{
  "title": {
    "en": "English Title",
    "ar": "العنوان العربي"
  },
  "slug": {
    "en": "english-slug",
    "ar": "العربية-slug"
  }
}
```

### Resolution

The `LocalizedContentResolver` service resolves locale-specific values:

```php
$resolver->resolveLocalizedField($title, 'ar', 'en')
// Returns: "العنوان العربي" or falls back to "English Title"
```

### Benefits

- Single row updates (no multi-table JOINs)
- Simplified Admin CMS Editor
- Perfect alignment with Next.js App Router metadata generation
- No relational translation tables
- Atomic updates across all locales

---

## Shared CMS Infrastructure

### Contracts

| Contract | Purpose |
|:---------|:--------|
| `HasSeoMetadata` | Entities that support SEO metadata |
| `HasLocalizedContent` | Entities with JSON-localized fields |

### Enums

| Enum | Purpose |
|:-----|:--------|
| `CmsOwnershipEnum` | Platform vs Tenant ownership classification |
| `RobotsDirectiveEnum` | SEO robots directives (index/noindex, follow/nofollow) |

### Services

| Service | Location | Purpose |
|:--------|:---------|:--------|
| `SeoResolutionService` | `Services/Cms/Seo/` | SEO metadata resolution |
| `CanonicalUrlService` | `Services/Cms/Seo/` | Canonical URL generation |
| `StructuredDataService` | `Services/Cms/Seo/` | JSON-LD structured data |
| `LocalizedContentResolver` | `Services/Cms/` | Locale resolution |
| `MarketingPageCacheService` | `Services/Cms/` | Cache management |

---

## Publishing Workflow

All CMS content supports a unified publishing workflow:

### States

- **Draft**: `is_published = false`, `published_at = null`
- **Scheduled**: `is_published = true`, `published_at > now()`
- **Published**: `is_published = true`, `published_at <= now()`

### SEO Implications

- **Draft content**: Always `noindex,nofollow` (enforced by `SeoResolutionService`)
- **Scheduled content**: `noindex,nofollow` until published
- **Published content**: Respects configured robots directive
- **Staging environment**: Always `noindex,nofollow` (environment override)

### Public API Behavior

Public endpoints ONLY return content where:
```php
->where('is_published', true)
->where(function ($q) {
    $q->whereNull('published_at')
      ->orWhere('published_at', '<=', now());
})
```

---

## Why JSON Columns

The CMS uses JSON columns for `title`, `slug`, `sections`, `content`, and `seo`.

**Rationale:**
- Sections are structured but flexible
- Frontend controls rendering
- Page structures evolve slowly
- Avoids migration explosion
- Keeps admin UI simple
- Produces stable SSR-friendly payloads
- No need for complex relational queries

**NOT a Page Builder:**
- No arbitrary layouts
- No HTML/WYSIWYG blobs
- No dynamic component registries
- No unsafe HTML rendering

---

## Architecture Strengths

✅ **Explicit subdomain boundaries** - Clear separation of Marketing, Blog, Documentation, SEO
✅ **Platform ownership enforced** - No store_id in any CMS table
✅ **Unified SEO contract** - Single `SeoResource` for all modules
✅ **Consistent authorization** - Policy-based with granular permissions
✅ **JSON localization strategy** - Consistent across all CMS entities
✅ **Action-based architecture** - Clear separation of concerns
✅ **DTO pattern** - Type-safe data transfer
✅ **Service layer** - Well-organized shared infrastructure
✅ **Controller consistency** - Normalized namespace organization

---

## Future Considerations

### Tenant-Scoped Documentation (Not Implemented)

If tenant-specific documentation is needed in the future:

1. Create new `TenantDocumentation` subdomain under `Cms/`
2. Add `store_id` foreign key to new tables
3. Use separate policies with store membership checks
4. Keep platform documentation separate
5. Update `CmsOwnershipEnum` usage

**Current State:** All documentation is platform-level.

### Multi-Tenant Blog (Not Planned)

Blog remains platform-level. Tenant-specific blogs would require:
- Separate subdomain
- Store-scoped queries
- Different authorization model

**Decision:** Out of scope for current architecture.

---

## Related Documentation

- [Main Architecture Rules](./ARCHITECTURE.md)
- [Authorization Doctrine](./ARCHITECTURE.md#authorization-doctrine)
- [Localization Strategy](./ARCHITECTURE.md#localization-strategy)
- [Admin API Structure](./ARCHITECTURE.md#admin-dashboard-architecture-rules)
