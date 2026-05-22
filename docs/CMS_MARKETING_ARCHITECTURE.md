# Marketing CMS Architecture

## Purpose

This CMS is a lightweight, platform-level content domain for marketing pages.
It is intentionally separate from the tenant/store architecture used by the
commerce domain.

## Scope

- Platform-owned content only
- Marketing and public website content only
- Structured JSON payload delivery only
- Next.js owns rendering, layout, and component composition

## Why JSON Columns

The `marketing_pages` table stores `title`, `slug`, `sections`, and `seo`
inside JSON columns.

This is intentional because:

- sections are structured but flexible
- the frontend controls rendering
- page structures evolve slowly
- it avoids migration explosion
- it keeps the admin UI simple
- it produces stable SSR-friendly payloads

We do not normalize hero blocks, FAQs, testimonials, CTA blocks, or footer
content into separate tables because that would add unnecessary joins and
complexity without any meaningful querying benefit for this use case.

## Why Not A Page Builder

This domain is not a drag-and-drop page builder.

It does not support:

- arbitrary layouts
- HTML/WYSIWYG blobs
- dynamic component registries
- unsafe HTML rendering pipelines

Instead, the API stores validated structured content for a fixed set of
marketing pages:

- `home`
- `about`
- `contact`
- `features`
- `enterprise`
- `pricing`

## Storage Model

One row represents one marketing page type.

Key columns:

- `type`: stable enum-backed page identifier
- `slug`: localized slug map
- `title`: localized title map
- `sections`: validated structured content
- `seo`: normalized SEO payload
- `status`: draft, published, or scheduled
- `published_at`: supports immediate or scheduled publishing

## Public API Contract

The public API only returns pages that are:

- `published`
- `published_at <= now()`

Responses are locale-aware and resolve JSON-localized fields with fallback to
the default locale.

### Endpoints

- `GET /api/v1/public/cms/pages/{slug}`

Example response shape:

```json
{
  "type": "about",
  "slug": "about",
  "title": "About Us",
  "sections": {
    "hero": {
      "title": "Build with confidence"
    }
  },
  "seo": {
    "meta_title": "About Us",
    "meta_description": "Learn more about our platform",
    "canonical_url": "https://example.com/about",
    "alternates": {
      "ar": "https://example.com/ar/about"
    },
    "robots": {
      "index": true,
      "follow": true,
      "all": "index,follow"
    },
    "og": {
      "title": "About Us",
      "description": "Learn more...",
      "image": "..."
    }
  }
}
```

## Admin API Contract

The admin API is protected by `auth:sanctum` and platform admin role checks.
It is intentionally not store-scoped for this CMS domain.

### Endpoints

- `GET /api/v1/admin/cms/pages`
- `POST /api/v1/admin/cms/pages`
- `GET /api/v1/admin/cms/pages/{id}`
- `PUT /api/v1/admin/cms/pages/{id}`
- `DELETE /api/v1/admin/cms/pages/{id}`
- `POST /api/v1/admin/cms/pages/{id}/publish`

## Unified SEO Contract

All CMS modules (Blog, Docs, Marketing) share the same SEO response contract via `SeoResource`. This ensures the frontend `generateMetadata()` remains generic.

| Key | Description |
| :--- | :--- |
| `meta_title` | Localized meta title |
| `meta_description` | Localized meta description |
| `canonical_url` | Full absolute URL |
| `robots` | Object containing `index`, `follow`, and `all` string |
| `og` | Object containing `title`, `description`, and `image` |
| `twitter` | Object containing `card` type |
| `alternates` | Map of other locales and their URLs |
| `structured_data` | Optional JSON-LD payload |
