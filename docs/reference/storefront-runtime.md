# Storefront Runtime API Reference

## Purpose

This document publishes the current Laravel storefront runtime endpoints and example
responses for frontend consumption during the JustShop storefront runtime migration.

The approved contract authority remains the Phase 1 contract package in
`justshop-frontend`. This reference shows the backend's current Phase 2 implementation
shape and request requirements.

## Contract Version

- `X-Storefront-Version: 2026-05-28`
- Supported locales: `en`, `ar`
- Tenant authority: request `Host` header

## Required Headers

All runtime requests require:

- `Host`
- `X-Storefront-Version`
- `X-Storefront-Locale`
- `X-Request-Id`

Preview-authorized page requests also require:

- `X-Preview-Token`

## Endpoints

### `GET /api/v1/storefront/runtime/resolve`

Purpose:

- resolves tenant, locale, route family, resource identity, redirect behavior, and cache metadata

Example request:

```bash
curl --request GET \
  --url 'https://api.justshop.test/api/v1/storefront/runtime/resolve?path=%2Fabout-us&locale=en' \
  --header 'Host: demo.justshop.test' \
  --header 'X-Storefront-Version: 2026-05-28' \
  --header 'X-Storefront-Locale: en' \
  --header 'X-Request-Id: req_runtime_resolve_001'
```

Example matched response:

```json
{
  "requestContext": {
    "requestId": "req_runtime_resolve_001",
    "tenantId": "store_42",
    "tenantKey": "justshop-demo",
    "locale": "en",
    "path": "/about-us",
    "runtimeVersion": "2026-05-28",
    "preview": false
  },
  "data": {
    "status": "matched",
    "routeType": "marketing_page",
    "pageId": "mkt_42",
    "resourceType": "page",
    "resourceId": "mkt_42",
    "path": "/about-us",
    "locale": "en",
    "layout": "marketing",
    "redirectTo": null,
    "redirectStatus": null,
    "legacyPassthrough": false
  },
  "cache": {
    "key": "storefront_runtime:2026-05-28:tenant:justshop-demo:locale:en:artifact:route:path:/about-us",
    "artifact": "route",
    "ttlSeconds": 300,
    "tags": [
      "tenant:justshop-demo",
      "locale:en",
      "artifact:route",
      "path:/about-us"
    ],
    "bypassed": false
  }
}
```

Example redirect response:

```json
{
  "requestContext": {
    "requestId": "req_runtime_resolve_002",
    "tenantId": "store_42",
    "tenantKey": "justshop-demo",
    "locale": "ar",
    "path": "/ar/old-about",
    "runtimeVersion": "2026-05-28",
    "preview": false
  },
  "data": {
    "status": "redirect",
    "routeType": "redirect",
    "pageId": null,
    "resourceType": "none",
    "resourceId": null,
    "path": "/ar/old-about",
    "locale": "ar",
    "layout": null,
    "redirectTo": "/ar/about-us",
    "redirectStatus": 301,
    "legacyPassthrough": false
  },
  "cache": {
    "key": "storefront_runtime:2026-05-28:tenant:justshop-demo:locale:ar:artifact:route:path:/ar/old-about",
    "artifact": "route",
    "ttlSeconds": 300,
    "tags": [
      "tenant:justshop-demo",
      "locale:ar",
      "artifact:route",
      "path:/ar/old-about"
    ],
    "bypassed": false
  }
}
```

Example legacy passthrough response:

```json
{
  "requestContext": {
    "requestId": "req_runtime_resolve_003",
    "tenantId": "store_42",
    "tenantKey": "justshop-demo",
    "locale": "en",
    "path": "/checkout",
    "runtimeVersion": "2026-05-28",
    "preview": false
  },
  "data": {
    "status": "matched",
    "routeType": "marketing_page",
    "pageId": null,
    "resourceType": "none",
    "resourceId": null,
    "path": "/checkout",
    "locale": "en",
    "layout": null,
    "redirectTo": null,
    "redirectStatus": null,
    "legacyPassthrough": true
  },
  "cache": {
    "key": "storefront_runtime:2026-05-28:tenant:justshop-demo:locale:en:artifact:route:path:/checkout",
    "artifact": "route",
    "ttlSeconds": 300,
    "tags": [
      "tenant:justshop-demo",
      "locale:en",
      "artifact:route",
      "path:/checkout"
    ],
    "bypassed": false
  }
}
```

### `GET /api/v1/storefront/runtime/page/{id}`

Purpose:

- returns the normalized runtime page DTO for a resolved page id

Example request:

```bash
curl --request GET \
  --url 'https://api.justshop.test/api/v1/storefront/runtime/page/mkt_42?path=%2Fabout-us' \
  --header 'Host: demo.justshop.test' \
  --header 'X-Storefront-Version: 2026-05-28' \
  --header 'X-Storefront-Locale: en' \
  --header 'X-Request-Id: req_runtime_page_001'
```

Example response:

```json
{
  "requestContext": {
    "requestId": "req_runtime_page_001",
    "tenantId": "store_42",
    "tenantKey": "justshop-demo",
    "locale": "en",
    "path": "/about-us",
    "runtimeVersion": "2026-05-28",
    "preview": false
  },
  "data": {
    "page": {
      "id": "mkt_42",
      "pageType": "marketing_page",
      "title": "About Us",
      "slug": "about-us",
      "locale": "en",
      "layout": "marketing",
      "status": "published",
      "sections": [
        {
          "id": "hero_about",
          "type": "hero_banner",
          "component": "HeroSection",
          "props": {
            "title": "Built for modern merchants",
            "subtitle": "Launch and scale a tenant storefront from Laravel-managed content.",
            "content": {
              "headline": "Built for modern merchants"
            },
            "settings": {
              "layout": "full"
            }
          },
          "version": "1",
          "dataState": "ready"
        }
      ],
      "seo": {
        "title": "About Us",
        "description": "Learn how JustShop delivers tenant-aware storefront experiences.",
        "canonicalUrl": "https://demo.justshop.test/about-us",
        "robots": "index,follow",
        "hreflang": [
          {
            "locale": "en",
            "url": "https://demo.justshop.test/about-us"
          },
          {
            "locale": "ar",
            "url": "https://demo.justshop.test/ar/about-us-ar"
          }
        ],
        "openGraph": {
          "title": "About Us",
          "description": "Learn how JustShop delivers tenant-aware storefront experiences.",
          "type": "website",
          "imageUrl": null
        },
        "twitter": {
          "card": "summary_large_image",
          "title": "About Us",
          "description": "Learn how JustShop delivers tenant-aware storefront experiences.",
          "imageUrl": null
        },
        "jsonLd": [
          {
            "@context": "https://schema.org",
            "@type": "WebPage"
          }
        ]
      },
      "publishedAt": "2026-05-12T10:30:00Z",
      "updatedAt": "2026-05-28T09:15:00Z"
    }
  },
  "cache": {
    "key": "storefront_runtime:2026-05-28:tenant:justshop-demo:locale:en:artifact:page:path:/about-us",
    "artifact": "page",
    "ttlSeconds": 3600,
    "tags": [
      "tenant:justshop-demo",
      "locale:en",
      "artifact:page",
      "page:mkt_42",
      "path:/about-us"
    ],
    "bypassed": false
  }
}
```

### `GET /api/v1/storefront/runtime/navigation`

Purpose:

- returns tenant-scoped, locale-aware header and footer navigation

Example request:

```bash
curl --request GET \
  --url 'https://api.justshop.test/api/v1/storefront/runtime/navigation?path=%2Fabout-us' \
  --header 'Host: demo.justshop.test' \
  --header 'X-Storefront-Version: 2026-05-28' \
  --header 'X-Storefront-Locale: en' \
  --header 'X-Request-Id: req_runtime_nav_001'
```

Example response:

```json
{
  "requestContext": {
    "requestId": "req_runtime_nav_001",
    "tenantId": "store_42",
    "tenantKey": "justshop-demo",
    "locale": "en",
    "path": "/about-us",
    "runtimeVersion": "2026-05-28",
    "preview": false
  },
  "data": {
    "header": [
      {
        "id": "nav_home",
        "label": "Home",
        "path": "/",
        "external": false,
        "children": []
      },
      {
        "id": "nav_shop",
        "label": "Shop",
        "path": "/products",
        "external": false,
        "children": [
          {
            "id": "nav_shop_7",
            "label": "Shoes",
            "path": "/products/category/shoes",
            "external": false,
            "children": []
          }
        ]
      }
    ],
    "footer": [
      {
        "id": "nav_footer_about",
        "label": "About",
        "path": "/about-us",
        "external": false,
        "children": []
      }
    ]
  },
  "cache": {
    "key": "storefront_runtime:2026-05-28:tenant:justshop-demo:locale:en:artifact:navigation:path:/about-us",
    "artifact": "navigation",
    "ttlSeconds": 1800,
    "tags": [
      "tenant:justshop-demo",
      "locale:en",
      "artifact:navigation",
      "path:/about-us"
    ],
    "bypassed": false
  }
}
```

### `GET /api/v1/storefront/runtime/theme`

Purpose:

- returns runtime-safe theme tokens, assets, and locale direction

Example request:

```bash
curl --request GET \
  --url 'https://api.justshop.test/api/v1/storefront/runtime/theme?path=%2Far%2Fabout-us-ar' \
  --header 'Host: demo.justshop.test' \
  --header 'X-Storefront-Version: 2026-05-28' \
  --header 'X-Storefront-Locale: ar' \
  --header 'X-Request-Id: req_runtime_theme_001'
```

Example response:

```json
{
  "requestContext": {
    "requestId": "req_runtime_theme_001",
    "tenantId": "store_42",
    "tenantKey": "justshop-demo",
    "locale": "ar",
    "path": "/ar/about-us-ar",
    "runtimeVersion": "2026-05-28",
    "preview": false
  },
  "data": {
    "themeKey": "default-light",
    "tokens": {
      "colorPrimary": "#2563eb",
      "colorSurface": "#ffffff",
      "colorText": "#111827",
      "fontBody": "Inter, sans-serif",
      "fontHeading": "Inter, sans-serif"
    },
    "assets": {
      "logoUrl": null,
      "faviconUrl": null
    },
    "settings": {
      "radius": "md",
      "direction": "rtl"
    }
  },
  "cache": {
    "key": "storefront_runtime:2026-05-28:tenant:justshop-demo:locale:ar:artifact:theme:path:/ar/about-us-ar",
    "artifact": "theme",
    "ttlSeconds": 3600,
    "tags": [
      "tenant:justshop-demo",
      "locale:ar",
      "artifact:theme",
      "path:/ar/about-us-ar"
    ],
    "bypassed": false
  }
}
```

### `POST /api/v1/storefront/runtime/preview/validate`

Purpose:

- validates tenant-scoped, page-scoped, locale-scoped preview authorization before draft payload delivery

Example request:

```bash
curl --request POST \
  --url 'https://api.justshop.test/api/v1/storefront/runtime/preview/validate' \
  --header 'Host: demo.justshop.test' \
  --header 'Content-Type: application/json' \
  --header 'X-Storefront-Version: 2026-05-28' \
  --header 'X-Storefront-Locale: en' \
  --header 'X-Request-Id: req_runtime_preview_001' \
  --data '{
    "token": "signed-preview-token",
    "pageId": "mkt_42",
    "path": "/about-us",
    "locale": "en"
  }'
```

Example response:

```json
{
  "requestContext": {
    "requestId": "req_runtime_preview_001",
    "tenantId": "store_42",
    "tenantKey": "justshop-demo",
    "locale": "en",
    "path": "/about-us",
    "runtimeVersion": "2026-05-28",
    "preview": true
  },
  "data": {
    "valid": true,
    "previewState": "authorized",
    "pageId": "mkt_42",
    "expiresAt": "2026-05-28T10:15:00Z",
    "cacheBypass": true
  }
}
```

## Normalized Error Shape

All runtime failures return the normalized runtime error envelope.

Example error response:

```json
{
  "requestContext": {
    "requestId": "req_runtime_error_001",
    "tenantId": null,
    "tenantKey": null,
    "locale": "en",
    "path": "/about-us",
    "runtimeVersion": "2026-05-28",
    "preview": false
  },
  "error": {
    "code": "runtime.tenant_not_found",
    "message": "The requested tenant could not be resolved from the storefront domain.",
    "httpStatus": 404,
    "retryable": false,
    "details": {
      "reason": "domain_not_mapped"
    }
  }
}
```

Supported normalized runtime error codes:

- `runtime.tenant_not_found`
- `runtime.tenant_inactive`
- `runtime.invalid_locale`
- `runtime.page_not_found`
- `runtime.preview_invalid`
- `runtime.preview_expired`
- `runtime.validation_failed`
- `runtime.internal_error`

Contract-version failures are normalized as `runtime.validation_failed` and return:

```json
{
  "requestContext": {
    "requestId": "req_runtime_error_002",
    "tenantId": null,
    "tenantKey": null,
    "locale": "en",
    "path": "/about-us",
    "runtimeVersion": "2026-05-28",
    "preview": false
  },
  "error": {
    "code": "runtime.validation_failed",
    "message": "The storefront runtime request is invalid.",
    "httpStatus": 400,
    "retryable": false,
    "details": {
      "headers": {
        "X-Storefront-Version": [
          "The runtime contract version header is missing or unsupported."
        ]
      }
    }
  }
}
```

## Runtime Notes

- Cache keys always include tenant, locale, runtime version, artifact, and normalized path.
- Preview-authorized payloads bypass shared cache and emit `cache.bypassed = true`.
- Store marketing page create, update, publish, unpublish, and delete lifecycle events invalidate tenant-scoped runtime cache artifacts for `route`, `page`, `navigation`, `theme`, and `seo`.
- Runtime logs include `tenant_id`, `tenant_key`, `locale`, `path`, `request_id`, `runtime_version`, `artifact`, `event`, `status`, and `duration_ms`.
- Arabic-prefixed paths such as `/ar`, `/ar/about-us-ar`, `/ar/products/category/shoes-ar`, and `/ar/products/red-shoe-ar` are supported by the resolver.
