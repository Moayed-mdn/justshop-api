# CMS SEO Architecture

## Overview

The CMS SEO architecture provides a **unified, type-safe contract** for SEO metadata across all CMS content types (Marketing Pages, Blog Posts, Documentation).

This ensures:
- Consistent frontend integration
- No duplication of SEO logic
- Centralized environment handling
- Type-safe metadata transformation
- Locale-aware SEO resolution

---

## Architecture Layers

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend (Next.js)                    │
│              generateMetadata() consumes                 │
│                    SeoResource JSON                      │
└─────────────────────────────────────────────────────────┘
                            ▲
                            │
┌─────────────────────────────────────────────────────────┐
│                   Response Layer                         │
│                    SeoResource                           │
│   Transforms ResolvedSeoDTO → Frontend JSON Contract    │
└─────────────────────────────────────────────────────────┘
                            ▲
                            │
┌─────────────────────────────────────────────────────────┐
│                  Resolution Layer                        │
│                SeoResolutionService                      │
│  • Resolves locale-specific values                      │
│  • Generates hreflang alternates                        │
│  • Applies environment robots override                  │
│  • Applies draft content robots override                │
│  • Generates default structured data                    │
└─────────────────────────────────────────────────────────┘
                            ▲
                            │
┌─────────────────────────────────────────────────────────┐
│                   Storage Layer                          │
│                    SeoMetaDTO                            │
│   Localized fields stored as JSON maps in database      │
│   {"en": "English", "ar": "العربية"}                    │
└─────────────────────────────────────────────────────────┘
```

---

## Storage Layer: SeoMetaDTO

### Purpose

Type-safe representation of SEO metadata **as stored in the database**.

### Structure

```php
final class SeoMetaDTO
{
    public function __construct(
        // Localized fields (JSON maps)
        public readonly array $metaTitle,           // {"en": "...", "ar": "..."}
        public readonly array $metaDescription,     // {"en": "...", "ar": "..."}
        public readonly array|string|null $ogImage, // {"en": "...", "ar": "..."} or string
        public readonly array $ogTitle,             // {"en": "...", "ar": "..."}
        public readonly array $ogDescription,       // {"en": "...", "ar": "..."}
        
        // Non-localized fields (scalars)
        public readonly ?string $canonicalUrl,
        public readonly RobotsDirectiveEnum $robots,
        public readonly ?array $structuredData,
        public readonly string $twitterCard,
    ) {}
}
```

### Database Storage

All CMS tables store SEO metadata in a JSON column:

```php
// marketing_pages.seo
// blog_posts.seo
// cms_documents.seo

{
  "meta_title": {"en": "About Us", "ar": "من نحن"},
  "meta_description": {"en": "Learn more", "ar": "اعرف المزيد"},
  "canonical_url": "https://example.com/about",
  "og_image": {"en": "https://cdn.../en.jpg", "ar": "https://cdn.../ar.jpg"},
  "robots": "index,follow",
  "twitter_card": "summary_large_image",
  "structured_data": null
}
```

### Factory Methods

```php
// From JSON column
SeoMetaDTO::fromArray($page->seo);

// From translation rows (legacy support)
SeoMetaDTO::fromTranslationRows($localeMap);
```

---

## Resolution Layer: SeoResolutionService

### Purpose

Transforms `SeoMetaDTO` (localized maps) into `ResolvedSeoDTO` (scalar values) for a specific locale.

### Responsibilities

1. **Locale Resolution**
   - Resolve `{"en": "...", "ar": "..."}` → scalar string
   - Apply fallback chain: requested locale → fallback locale → first available

2. **Canonical URL Generation**
   - Use explicit canonical if provided
   - Otherwise generate from primary locale slug

3. **Hreflang Alternates**
   - Generate alternate URLs for all supported locales
   - Include `x-default` pointing to primary locale

4. **Environment-Aware Robots**
   - Staging/preview: Force `noindex,nofollow`
   - Production: Use configured robots directive

5. **Draft Content Robots**
   - Unpublished content: Force `noindex,nofollow`
   - Scheduled content: Force `noindex,nofollow` until published

6. **Structured Data Generation**
   - Generate default JSON-LD based on content type
   - Blog → Article schema
   - Docs → TechArticle schema
   - Marketing → WebSite schema

### Usage

```php
$seoResolutionService->resolve(
    seo: $seoMetaDTO,
    locale: 'ar',
    fallback: 'en',
    slugMap: ['en' => 'about', 'ar' => 'من-نحن'],
    routePrefix: '',
    isPublished: true,
    entityData: ['title' => '...', 'excerpt' => '...'],
);
// Returns: ResolvedSeoDTO (all scalar values)
```

---

## Response Layer: ResolvedSeoDTO

### Purpose

Locale-resolved SEO payload ready for API response. **All fields are scalar** — no locale maps.

### Structure

```php
final class ResolvedSeoDTO
{
    public function __construct(
        public readonly ?string $metaTitle,
        public readonly ?string $metaDescription,
        public readonly ?string $canonicalUrl,
        public readonly ?string $ogImage,
        public readonly ?string $ogTitle,
        public readonly ?string $ogDescription,
        public readonly string  $robots,
        public readonly bool    $isIndexable,
        public readonly bool    $isFollowable,
        public readonly string  $twitterCard,
        public readonly array   $alternates,      // ["en" => "https://...", "ar" => "https://..."]
        public readonly ?array  $structuredData,  // JSON-LD payload
    ) {}
}
```

### Transformation to Frontend JSON

```php
class SeoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'meta_title'       => $seo->metaTitle,
            'meta_description' => $seo->metaDescription,
            'canonical_url'    => $seo->canonicalUrl,
            'alternates'       => (object) ($seo->alternates ?: []),
            'robots' => [
                'index'  => $seo->isIndexable,
                'follow' => $seo->isFollowable,
                'all'    => $seo->robots,
            ],
            'og' => [
                'title'       => $seo->ogTitle ?? $seo->metaTitle,
                'description' => $seo->ogDescription ?? $seo->metaDescription,
                'image'       => $seo->ogImage,
                'type'        => 'website',
            ],
            'twitter' => [
                'card'        => $seo->twitterCard,
                'title'       => $seo->ogTitle ?? $seo->metaTitle,
                'description' => $seo->ogDescription ?? $seo->metaDescription,
                'image'       => $seo->ogImage,
            ],
            'structured_data' => (object) ($seo->structuredData ?: []),
        ];
    }
}
```

---

## Supporting Services

### CanonicalUrlService

**Purpose:** Generate canonical URLs and hreflang alternates.

**Strategy:**
- Canonical always points to **primary locale** (en) URL
- Primary locale: NO locale prefix → `/about`
- Secondary locales: WITH locale prefix → `/ar/من-نحن`

**Example:**
```php
$canonicalUrlService->generateCanonical(
    slugMap: ['en' => 'about', 'ar' => 'من-نحن'],
    fallback: 'en',
    routePrefix: '',
);
// Returns: "https://example.com/about"

$canonicalUrlService->generateAlternates(
    slugMap: ['en' => 'about', 'ar' => 'من-نحن'],
    routePrefix: '',
);
// Returns: [
//   "en" => "https://example.com/about",
//   "ar" => "https://example.com/ar/من-نحن",
//   "x-default" => "https://example.com/about"
// ]
```

### StructuredDataService

**Purpose:** Generate JSON-LD structured data payloads.

**Supported Schemas:**
- `organization()` - Platform-wide organization schema
- `website()` - Home page schema with sitelinks searchbox
- `article($data)` - Blog post schema
- `techArticle($data)` - Documentation schema
- `breadcrumbs($items)` - Breadcrumb navigation

**Example:**
```php
$structuredDataService->article([
    'title' => 'Blog Post Title',
    'excerpt' => 'Post description',
    'cover_image' => 'https://...',
    'published_at' => '2026-05-24',
    'author_name' => 'John Doe',
    'url' => 'https://example.com/blog/post',
]);
// Returns: Article schema JSON-LD array
```

### LocalizedContentResolver

**Purpose:** Resolve localized fields from JSON maps.

**Logic:**
1. Check if value is a locale map: `{"en": "...", "ar": "..."}`
2. Return requested locale value
3. Fallback to fallback locale
4. Fallback to first available value

**Example:**
```php
$resolver->resolveLocalizedField(
    value: ['en' => 'English', 'ar' => 'العربية'],
    locale: 'ar',
    fallback: 'en',
);
// Returns: "العربية"
```

---

## Robots Directive Handling

### RobotsDirectiveEnum

```php
enum RobotsDirectiveEnum: string
{
    case INDEX_FOLLOW = 'index,follow';
    case NOINDEX_FOLLOW = 'noindex,follow';
    case INDEX_NOFOLLOW = 'index,nofollow';
    case NOINDEX_NOFOLLOW = 'noindex,nofollow';
    
    public static function default(): self
    {
        return self::INDEX_FOLLOW;
    }
    
    public static function forDraft(): self
    {
        return self::NOINDEX_NOFOLLOW;
    }
}
```

### Override Rules

**Priority (highest to lowest):**

1. **Draft Content Override**
   ```php
   if (!$isPublished) {
       $robots = RobotsDirectiveEnum::forDraft(); // noindex,nofollow
   }
   ```

2. **Environment Override**
   ```php
   if (app()->environment(['staging', 'preview'])) {
       $robots = RobotsDirectiveEnum::forDraft(); // noindex,nofollow
   }
   ```

3. **Configured Robots**
   ```php
   $robots = $seoMetaDTO->robots; // Use stored value
   ```

**Rationale:**
- Draft content must NEVER be indexed
- Staging environments must NEVER leak to search engines
- Production published content respects configured robots

---

## Frontend Integration

### Next.js generateMetadata()

```typescript
export async function generateMetadata({ params }): Promise<Metadata> {
  const response = await fetch(`/api/v1/public/cms/pages/${params.slug}`);
  const { data } = await response.json();
  const seo = data.seo;

  return {
    title: seo.meta_title,
    description: seo.meta_description,
    robots: seo.robots.all,
    alternates: {
      canonical: seo.canonical_url,
      languages: seo.alternates,
    },
    openGraph: {
      title: seo.og.title,
      description: seo.og.description,
      images: [seo.og.image],
      type: seo.og.type,
    },
    twitter: {
      card: seo.twitter.card,
      title: seo.twitter.title,
      description: seo.twitter.description,
      images: [seo.twitter.image],
    },
  };
}
```

### Structured Data Rendering

```typescript
export default function Page({ data }) {
  return (
    <>
      {data.seo.structured_data && (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{
            __html: JSON.stringify(data.seo.structured_data),
          }}
        />
      )}
      {/* Page content */}
    </>
  );
}
```

---

## Content Type SEO Mapping

| Content Type | Route Prefix | Structured Data | Canonical Pattern |
|:-------------|:-------------|:----------------|:------------------|
| Marketing Pages | `` (root) | `website()` | `https://example.com/{slug}` |
| Blog Posts | `blog` | `article()` | `https://example.com/blog/{slug}` |
| Documentation | `docs` | `techArticle()` | `https://example.com/docs/{slugPath}` |

---

## Sitemap Generation

### SitemapService

Generates XML sitemaps for all CMS content types.

**Endpoints:**
- `/api/v1/public/cms/seo/sitemap/marketing`
- `/api/v1/public/cms/seo/sitemap/blog`
- `/api/v1/public/cms/seo/sitemap/docs`

**Rules:**
- Only published content (`is_published = true`, `published_at <= now()`)
- Include all locale alternates via `<xhtml:link rel="alternate">`
- Set `<lastmod>` from `updated_at`
- Set `<changefreq>` based on content type
- Set `<priority>` based on content importance

**Example Output:**
```xml
<url>
  <loc>https://example.com/about</loc>
  <lastmod>2026-05-24</lastmod>
  <changefreq>monthly</changefreq>
  <priority>0.8</priority>
  <xhtml:link rel="alternate" hreflang="en" href="https://example.com/about"/>
  <xhtml:link rel="alternate" hreflang="ar" href="https://example.com/ar/من-نحن"/>
  <xhtml:link rel="alternate" hreflang="x-default" href="https://example.com/about"/>
</url>
```

---

## Benefits of Unified SEO Architecture

✅ **Single Source of Truth**
- One DTO structure for all CMS content
- One resolution service for all transformations
- One resource for all API responses

✅ **Type Safety**
- No raw arrays in business logic
- Compile-time validation of SEO fields
- IDE autocomplete for all SEO properties

✅ **Consistent Frontend Contract**
- Next.js `generateMetadata()` works identically for all content types
- No content-type-specific SEO handling in frontend

✅ **Centralized Environment Handling**
- Staging = noindex enforced in one place
- Draft = noindex enforced in one place
- No risk of accidental indexing

✅ **Locale-Aware by Design**
- Automatic locale resolution with fallback
- Automatic hreflang generation
- Automatic alternate URL generation

✅ **No Duplication**
- SEO transformation logic exists once
- Canonical URL generation logic exists once
- Structured data generation logic exists once

---

## Testing Strategy

### Unit Tests

```php
// SeoMetaDTO
test('creates from array with localized fields');
test('creates from translation rows');
test('applies draft robots override');

// SeoResolutionService
test('resolves locale-specific values');
test('generates canonical URL from slug map');
test('generates hreflang alternates');
test('applies environment robots override on staging');
test('applies draft robots override for unpublished content');
test('generates default structured data by content type');

// CanonicalUrlService
test('generates canonical URL for primary locale');
test('generates alternates for all locales');
test('includes x-default alternate');

// StructuredDataService
test('generates article schema for blog posts');
test('generates tech article schema for documentation');
test('generates website schema for marketing pages');
```

### Integration Tests

```php
test('marketing page API returns unified SEO contract');
test('blog post API returns unified SEO contract');
test('documentation API returns unified SEO contract');
test('draft content returns noindex robots');
test('staging environment returns noindex robots');
test('published content returns configured robots');
```

---

## Related Documentation

- [CMS Architecture](../CMS_MARKETING_ARCHITECTURE.md)
- [CMS Domain Ownership](./cms-domain-ownership.md)
- [Localization Strategy](../ARCHITECTURE.md#localization-strategy)
