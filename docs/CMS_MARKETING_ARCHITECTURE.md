# CMS Marketing Architecture

## Overview

The CMS domain is an ownership-aware content management system for marketing, blog, and documentation content.

Current runtime priority:
- Platform marketing pages
- Platform blog
- Platform documentation

Planned extension:
- Store marketing pages as a tenant-scoped CMS subdomain

The CMS remains intentionally separate from the tenant/store commerce architecture even when some CMS entities are tenant-owned.

## CMS Subdomain Organization

The CMS domain is organized into ownership-aware subdomains under a unified umbrella:

```
Cms/
├── Marketing/
│   ├── Platform/      # Platform marketing pages (home, about, features, etc.)
│   └── Store/         # Store marketing pages (tenant-scoped, rollout deferred)
├── Blog/              # Platform blog posts with categories and tags
├── Documentation/     # Platform documentation with hierarchical structure
└── Seo/               # Shared SEO infrastructure for all CMS content
```

### Subdomain Responsibilities

| Subdomain | Purpose | Ownership | Storage |
|:----------|:--------|:----------|:--------|
| **Marketing / Platform** | Landing pages, feature pages, pricing | Platform | JSON columns |
| **Marketing / Store** | Store landing pages, campaigns, promotions | Store | JSON columns |
| **Blog** | Blog posts with categories, tags, authors | Platform | JSON columns |
| **Documentation** | Product docs with sections and hierarchy | Platform | JSON columns |
| **SEO** | Unified SEO metadata, sitemaps, structured data | Shared | Services |

---

## Platform vs Tenant Ownership

### Current Runtime Model

Current runtime behavior is:

- ✅ public marketing reads are platform-facing
- ✅ blog is platform-owned
- ✅ documentation is platform-owned
- ✅ current frontend integration depends on platform marketing pages with minimal change tolerance

### Target Ownership Model

The ownership split for CMS is:

- ✅ `platform_marketing_pages` - NO `store_id` (platform-level)
- ✅ `store_marketing_pages` - MUST include `store_id` (tenant-level)
- ✅ `blog_posts` - NO `store_id` (platform-level)
- ✅ `cms_documents` - NO `store_id` (platform-level)
- ✅ `cms_document_sections` - NO `store_id` (platform-level)

### Delivery Rule

- Platform marketing pages are migrated first.
- Store marketing pages are backend-foundation only until the store frontend contract is ready.
- Backend compatibility is preferred over large frontend changes.

### Authorization Model

All CMS modules use **policy-based authorization** with ownership-aware permissions:

| Module | Policy | Permissions |
|:-------|:-------|:------------|
| Platform Marketing Pages | `PlatformMarketingPolicy` | `marketing.platform.view`, `marketing.platform.create`, `marketing.platform.update`, `marketing.platform.delete`, `marketing.platform.publish` |
| Store Marketing Pages | `StoreMarketingPolicy` | `marketing.store.view`, `marketing.store.create`, `marketing.store.update`, `marketing.store.delete`, `marketing.store.publish` |
| Blog | `BlogPostPolicy` | `cms.blog.view`, `cms.blog.create`, `cms.blog.update`, `cms.blog.delete`, `cms.blog.publish` |
| Documentation | `CmsDocumentPolicy` | `cms.doc.view`, `cms.doc.create`, `cms.doc.update`, `cms.doc.delete`, `cms.doc.publish` |

**Authorization Flow:**
1. Route middleware resolves the correct admin surface
2. Controller calls `$this->authorize(...)`
3. Policy enforces ownership-aware capabilities

This provides:
- Consistent authorization pattern across platform and store CMS
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

### Public API

#### Platform Public API (Current Runtime)

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

#### Store Public API (Deferred)

Potential store-public shape:

```http
GET /api/v1/stores/{store}/cms/pages/{slug}
GET /api/v1/stores/{store}/cms/seo/sitemap
```

Rules:
- Store public CMS is tenant-scoped.
- Store public CMS is not part of the active frontend contract yet.
- Store public CMS may remain disabled or feature-flagged until the frontend is ready.

### Admin API

**Authentication:**
- `auth:sanctum` + `verified` + `role:super_admin`
- Policy-based authorization in controllers

**Endpoints:**
```
# Platform Marketing Pages
GET    /api/v1/admin/cms/pages                    # compatibility route
POST   /api/v1/admin/cms/pages                    # compatibility route
GET    /api/v1/admin/cms/pages/{id}               # compatibility route
PUT    /api/v1/admin/cms/pages/{id}               # compatibility route
DELETE /api/v1/admin/cms/pages/{id}               # compatibility route
POST   /api/v1/admin/cms/pages/{id}/publish       # compatibility route

GET    /api/v1/admin/cms/platform/pages           # target route
POST   /api/v1/admin/cms/platform/pages           # target route
GET    /api/v1/admin/cms/platform/pages/{id}      # target route
PUT    /api/v1/admin/cms/platform/pages/{id}      # target route
DELETE /api/v1/admin/cms/platform/pages/{id}      # target route
POST   /api/v1/admin/cms/platform/pages/{id}/publish

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

#### Store Marketing Admin API (Backend Foundation)

```http
GET    /api/v1/admin/stores/{store}/cms/pages
POST   /api/v1/admin/stores/{store}/cms/pages
GET    /api/v1/admin/stores/{store}/cms/pages/{id}
PUT    /api/v1/admin/stores/{store}/cms/pages/{id}
DELETE /api/v1/admin/stores/{store}/cms/pages/{id}
POST   /api/v1/admin/stores/{store}/cms/pages/{id}/publish
```

Rules:
- Platform admin CMS routes are super-admin global routes.
- Store admin CMS routes are store-scoped.
flowchart LR
    A[Merchant actor<br/>store owner or store admin] --> B[merchant_admin identity domain]
    B --> C[/api/v1/admin/stores/{store}/...]
    C --> D[store.context]
    D --> E[store-aware permission resolution]
    E --> F[policy + action + repository]

    G[super_admin actor] --> H[platform identity domain]
    H --> I[/api/v1/admin/cms/* or /api/v1/platform/*]

    style B fill:#bbdefb,color:#0d47a1
    style C fill:#c8e6c9,color:#1a5e20
    style E fill:#fff3e0,color:#e65100
    style H fill:#f3e5f5,color:#7b1fa2
    style I fill:#f3e5f5,color:#7b1fa2flowchart LR
    A[Merchant actor<br/>store owner or store admin] --> B[merchant_admin identity domain]
    B --> C[/api/v1/admin/stores/{store}/...]
    C --> D[store.context]
    D --> E[store-aware permission resolution]
    E --> F[policy + action + repository]

    G[super_admin actor] --> H[platform identity domain]
    H --> I[/api/v1/admin/cms/* or /api/v1/platform/*]

    style B fill:#bbdefb,color:#0d47a1
    style C fill:#c8e6c9,color:#1a5e20
    style E fill:#fff3e0,color:#e65100
    style H fill:#f3e5f5,color:#7b1fa2
    style I fill:#f3e5f5,color:#7b1fa2- Platform admin CMS routes belong to the platform identity domain.
- Store admin CMS routes belong to the merchant-admin identity domain and MUST include `{store}`.
- Current frontend should require only minimal route changes if target platform routes are adopted.

---

## Controller Organization

All admin controllers follow a consistent namespace pattern:

```
App\Http\Controllers\Api\Admin\Cms\
├── MarketingPage\
│   └── AdminMarketingPageController
├── Marketing\
│   ├── Platform\
│   └── Store\
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

Rules:
- Admin controllers MUST be under `Api\Admin\Cms\...`
- Public platform controllers MUST be under `Api\Cms\...`
- Store-scoped public controllers MAY use store-aware namespaces later
- Controllers MUST use policy authorization via `$this->authorize()`
- Controllers MUST be thin and delegate to Actions

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

- **Draft**: content is editable and not publicly visible
- **Scheduled**: content becomes public only when publish time is reached
- **Published**: content is publicly visible

### SEO Implications

- **Draft content**: Always `noindex,nofollow` (enforced by `SeoResolutionService`)
- **Scheduled content**: `noindex,nofollow` until published
- **Published content**: Respects configured robots directive
- **Staging environment**: Always `noindex,nofollow` (environment override)

### Public API Behavior

Public endpoints ONLY return content where publication visibility has been satisfied:
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

## Migration And Rollout Rules

### Platform Marketing Migration

Rules:
- Migrate platform marketing storage and runtime first.
- Keep the current frontend contract stable as much as possible.
- Prefer backend compatibility adapters over frontend rewrites.
- Temporary legacy fallback reads are allowed during migration only.

### Store Marketing Rollout

Rules:
- Store marketing backend may be prepared before store frontend rollout.
- Store marketing public contract is not final yet.
- Do not force store marketing frontend implementation until requirements are defined.

### Blog And Documentation

Rules:
- Blog remains platform-owned.
- Documentation remains platform-owned.
- Tenant-scoped blog or documentation remains out of scope unless explicitly approved later.

---

## Related Documentation

- [Main Architecture Rules](./ARCHITECTURE.md)
- [Authorization Doctrine](./ARCHITECTURE.md#authorization-doctrine)
- [Localization Strategy](./ARCHITECTURE.md#localization-strategy)
- [Admin API Structure](./ARCHITECTURE.md#admin-dashboard-architecture-rules)
