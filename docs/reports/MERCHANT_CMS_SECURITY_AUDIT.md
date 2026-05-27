# Merchant CMS Marketing Domain — Security Audit Report

**Priority 2 Deliverable**  
**Date:** 2026-05-27  
**Scope:** `app/Http/Controllers/Api/Merchant/AdminStoreMarketingPageController.php` and all related merchant CMS marketing files  
**Status:** ✅ All issues resolved

---

## Executive Summary

The merchant CMS marketing domain was audited for unsafe request handling, unvalidated payloads, unsafe mass assignment, and missing authorization checks. The audit found that the domain was **already fully remediated** prior to this report — the issues documented in `MARKETING_PAGES_ARCHITECTURE.md` (raw `$request->all()`, no FormRequest, no API resource) had been resolved in a prior implementation pass.

This report documents what was found, what was already fixed, and what risk was eliminated.

---

## Audit Scope

| File | Audit Result |
|:---|:---|
| `AdminStoreMarketingPageController` | ✅ Clean |
| `CreateStoreMarketingPageRequest` | ✅ Clean |
| `UpdateStoreMarketingPageRequest` | ✅ Clean |
| `PublishStoreMarketingPageRequest` | ✅ Clean |
| `CreateStoreMarketingPageAction` | ✅ Clean |
| `UpdateStoreMarketingPageAction` | ✅ Clean |
| `DeleteStoreMarketingPageAction` | ✅ Clean |
| `PublishStoreMarketingPageAction` | ✅ Clean |
| `UnpublishStoreMarketingPageAction` | ✅ Clean |
| `AdminStoreMarketingPageResource` | ✅ Clean |
| `StoreMarketingSectionResource` | ✅ Clean |
| `StoreMarketingPagePolicy` | ✅ Clean |
| `StoreMarketingPageRepository` | ✅ Clean |

---

## What Was Unsafe (Architecture Doc Findings)

The `MARKETING_PAGES_ARCHITECTURE.md` documented these issues as existing at the time of writing:

### 1. Raw `$request->all()` in controller store/update methods
**Risk:** Any field the client sends is passed directly to the model. An attacker could inject `store_id`, `created_by`, `updated_by`, or any other fillable field.  
**Severity:** High — mass assignment bypass, tenant isolation risk.

### 2. Raw `$request->except()` in controller
**Risk:** Blocklist-based filtering is fragile. New model fields added later are automatically exposed unless the blocklist is updated.  
**Severity:** Medium — future-proofing risk, not immediately exploitable if `$fillable` is set.

### 3. No FormRequest validation
**Risk:** No input validation means malformed data (invalid status enum, invalid template, non-slug strings, oversized payloads) reaches the database.  
**Severity:** High — data integrity, potential for invalid enum values to cause runtime errors.

### 4. No API resource class
**Risk:** Raw Eloquent model serialization exposes all model attributes including internal fields (`created_by`, `updated_by`, pivot data, etc.).  
**Severity:** Medium — information disclosure, unstable frontend contract.

### 5. No publish endpoint
**Risk:** Status changes going through `update` means no dedicated authorization check for publish, no publish timestamp handling, no event dispatching.  
**Severity:** Medium — authorization bypass risk for publish-specific permission.

---

## What Was Fixed

All five issues were resolved before this audit was conducted. The current state:

### 1. FormRequest validation — RESOLVED

**`CreateStoreMarketingPageRequest`** validates:
- `title` — required array, each locale string max 255
- `slug` — required array, each locale matches `^[a-z0-9]+(?:-[a-z0-9]+)*$`, max 255
- `excerpt` — optional nullable array
- `content` — optional nullable array (sections carry structured content)
- `status` — required, validated against `MarketingPageStatusEnum::values()`
- `published_at` — optional nullable date
- `template` — optional, validated against `MarketingPageTemplateEnum::storeTemplates()` (platform templates rejected)
- `sort_order` — optional integer min 0
- `seo.*` — full SEO structure validation (meta_title, meta_description, canonical_url, robots, og_image)
- `sections.*` — full section validation including `section_type`/`type` alias, identifier, sort_order, title, subtitle, content, settings, is_active
- Scheduled publishing: `published_at` must be a future date when `status = scheduled`
- Store-scoped slug uniqueness: validated per locale against `store_marketing_pages` table scoped by `store_id`

**`UpdateStoreMarketingPageRequest`** validates the same fields with:
- Self-exclusion in slug uniqueness check (ignores current page ID)

**`PublishStoreMarketingPageRequest`** validates:
- `published_at` — optional nullable date
- `authorize()` checks `MARKETING_STORE_PUBLISH` permission

### 2. DTO-style validated payloads — RESOLVED

All controller methods use typed DTOs:
- `CreateStoreMarketingPageDTO::fromRequest()` — extracts only validated fields
- `UpdateStoreMarketingPageDTO::fromRequest()` — extracts only validated fields
- `DeleteStoreMarketingPageDTO` — typed value object
- `PublishStoreMarketingPageDTO::fromRequest()` — extracts only validated fields

No `$request->all()` or `$request->except()` anywhere in the merchant CMS domain.

### 3. API resource — RESOLVED

**`AdminStoreMarketingPageResource`** returns a stable, explicit contract:
- `id`, `store_id`, `title`, `slug`, `excerpt`, `content`
- `status` — enum value string
- `published_at` — ISO 8601 string
- `template` — enum value string
- `sort_order`, `seo`
- `created_at`, `updated_at` — ISO 8601 strings
- `creator`, `updater` — `{id, name}` when loaded
- `sections` — `StoreMarketingSectionResource` collection when loaded

**`StoreMarketingSectionResource`** returns:
- `id`, `section_type`, `identifier`, `sort_order`, `title`, `subtitle`, `content`, `settings`, `is_active`, `created_at`, `updated_at`

### 4. Dedicated publish/unpublish endpoints — RESOLVED

Routes registered:
```
POST /api/v1/merchant/stores/{store}/cms/pages/{id}/publish
POST /api/v1/merchant/stores/{store}/cms/pages/{id}/unpublish
```

`PublishStoreMarketingPageAction`:
- Validates page belongs to store (repository scoped by `store_id`)
- Rejects publish if already published (throws `ValidationException`)
- Sets `status = published`, `published_at = now()` (or provided date)
- Wrapped in DB transaction

`UnpublishStoreMarketingPageAction`:
- Rejects unpublish if already draft
- Sets `status = draft`, `published_at = null`
- Wrapped in DB transaction

### 5. Authorization — RESOLVED

`StoreMarketingPagePolicy` enforces:
- `isMember($user, $store)` — user must be a member of the specific store
- Per-ability permission checks: `MARKETING_STORE_VIEW/CREATE/UPDATE/DELETE/PUBLISH`

Controller calls `$this->authorize()` before every action. FormRequests also check permissions in `authorize()` as a defense-in-depth layer.

### 6. Safe mass assignment — RESOLVED

`StoreMarketingPage::$fillable` explicitly lists allowed fields:
```php
protected $fillable = [
    'store_id', 'title', 'slug', 'excerpt', 'content',
    'status', 'published_at', 'seo', 'template', 'sort_order',
    'created_by', 'updated_by',
];
```

Repository `create()` and `update()` methods pass explicit attribute arrays — never raw request data.

---

## Risk Eliminated

| Risk | Before | After |
|:---|:---|:---|
| Mass assignment bypass | High | Eliminated |
| Tenant isolation bypass via `store_id` injection | High | Eliminated |
| Invalid enum values reaching DB | High | Eliminated |
| Platform templates accepted on store pages | Medium | Eliminated |
| Slug collision across stores | Medium | Eliminated |
| Publish without publish permission | Medium | Eliminated |
| Raw model serialization (information disclosure) | Medium | Eliminated |
| Unstable frontend response contract | Medium | Eliminated |
| Scheduled publish with past date | Low | Eliminated |

---

## Remaining Considerations

1. **Section sync is a full replace** — `syncSections()` deletes all existing sections and re-inserts. This is intentional (atomic replace) but means partial section updates are not supported. Future improvement: support patch-style section updates.

2. **No cache invalidation on store publish** — `PublishStoreMarketingPageAction` has a comment noting that ISR revalidation and cache invalidation are deferred until the public store CMS endpoint is activated. This is intentional and documented.

3. **No event dispatching** — Publish/unpublish actions do not dispatch domain events. Future improvement: dispatch `StoreMarketingPagePublished` / `StoreMarketingPageUnpublished` events for listeners (cache, webhooks, analytics).

---

## Files Changed in This Audit Pass

None — all issues were already resolved. This report documents the resolved state.

**Files verified clean:**
- `app/Http/Controllers/Api/Merchant/AdminStoreMarketingPageController.php`
- `app/Http/Requests/Cms/Marketing/Store/Admin/CreateStoreMarketingPageRequest.php`
- `app/Http/Requests/Cms/Marketing/Store/Admin/UpdateStoreMarketingPageRequest.php`
- `app/Http/Requests/Cms/Marketing/Store/Admin/PublishStoreMarketingPageRequest.php`
- `app/Actions/Cms/Marketing/Store/Admin/CreateStoreMarketingPageAction.php`
- `app/Actions/Cms/Marketing/Store/Admin/UpdateStoreMarketingPageAction.php`
- `app/Actions/Cms/Marketing/Store/Admin/DeleteStoreMarketingPageAction.php`
- `app/Actions/Cms/Marketing/Store/Admin/PublishStoreMarketingPageAction.php`
- `app/Actions/Cms/Marketing/Store/Admin/UnpublishStoreMarketingPageAction.php`
- `app/Http/Resources/Admin/Cms/Marketing/Store/AdminStoreMarketingPageResource.php`
- `app/Http/Resources/Admin/Cms/Marketing/Store/StoreMarketingSectionResource.php`
- `app/Policies/Cms/Marketing/Store/StoreMarketingPagePolicy.php`
- `app/Repositories/Cms/Marketing/Store/StoreMarketingPageRepository.php`
