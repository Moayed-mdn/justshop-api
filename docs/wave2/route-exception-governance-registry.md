# Route Exception Governance Registry

**Version:** 1.0  
**Status:** APPROVED  
**Wave:** 2  
**Date:** 2026-05-23

---

## Purpose

This document defines approved platform exceptions, approval criteria, required middleware posture, tenant-isolation requirements, and forbidden route patterns for routes that deviate from the standard `{store}` scoping pattern.

---

## Approved Platform Exception Categories

### Category 1: Platform-Level Admin Resources
**Definition:** Resources managed at platform level, not store level  
**Approval Criteria:**
- Resource is genuinely platform-scoped (not store-specific)
- Requires super_admin role
- Has explicit policy authorization
- Has audit logging

**Examples:**
- CMS Blog Posts (shared across platform)
- CMS Marketing Pages (shared across platform)
- CMS Documentation (shared across platform)
- Leads (platform-level inquiries)

---

### Category 2: Authentication & Session Management
**Definition:** Routes that establish or manage authentication state  
**Approval Criteria:**
- Required for auth flow
- No store context needed
- CSRF protection where applicable
- Rate limiting applied

**Examples:**
- `/api/sanctum/csrf-cookie`
- `/api/v1/auth/login`
- `/api/v1/auth/register`
- `/api/v1/auth/logout`

---

### Category 3: Webhook & External Integration
**Definition:** Routes called by external services  
**Approval Criteria:**
- Signature verification required
- Idempotency enforced
- No session dependency
- Explicit authorization mechanism

**Examples:**
- `/api/stripe/webhook`
- `/api/paypal/webhook` (if implemented)

---

### Category 4: Public Storefront Content
**Definition:** Customer-facing content that doesn't require store context in URL  
**Approval Criteria:**
- Store resolved from subdomain/domain
- Customer-safe content only
- No admin operations
- Explicit store resolution documented

**Examples:**
- `/api/v1/storefront/products` (store from domain)
- `/api/v1/storefront/categories` (store from domain)

---

## Approved Exception Registry

### 1. CMS Blog (Platform Admin)

| Route | Method | Controller | Policy | Middleware | Tenant Isolation |
|-------|--------|------------|--------|------------|------------------|
| `/api/v1/admin/cms/blog` | GET | `AdminBlogController::index` | `BlogPostPolicy::viewAny` | `auth:sanctum`, `verified`, `role:super_admin` | N/A (platform resource) |
| `/api/v1/admin/cms/blog` | POST | `AdminBlogController::store` | `BlogPostPolicy::create` | `auth:sanctum`, `verified`, `role:super_admin` | N/A (platform resource) |
| `/api/v1/admin/cms/blog/{blogPost}` | GET | `AdminBlogController::show` | `BlogPostPolicy::view` | `auth:sanctum`, `verified`, `role:super_admin` | N/A (platform resource) |
| `/api/v1/admin/cms/blog/{blogPost}` | PUT | `AdminBlogController::update` | `BlogPostPolicy::update` | `auth:sanctum`, `verified`, `role:super_admin` | N/A (platform resource) |
| `/api/v1/admin/cms/blog/{blogPost}` | DELETE | `AdminBlogController::destroy` | `BlogPostPolicy::delete` | `auth:sanctum`, `verified`, `role:super_admin` | N/A (platform resource) |
| `/api/v1/admin/cms/blog/{blogPost}/publish` | POST | `AdminBlogController::publish` | `BlogPostPolicy::publish` | `auth:sanctum`, `verified`, `role:super_admin` | N/A (platform resource) |
| `/api/v1/admin/cms/blog/{blogPost}/unpublish` | POST | `AdminBlogController::unpublish` | `BlogPostPolicy::unpublish` | `auth:sanctum`, `verified`, `role:super_admin` | N/A (platform resource) |
| `/api/v1/admin/cms/blog/{blogPost}/schedule` | POST | `AdminBlogController::schedule` | `BlogPostPolicy::schedule` | `auth:sanctum`, `verified`, `role:super_admin` | N/A (platform resource) |

**Approval Reason:** Blog posts are platform-level content shared across all stores  
**Security Posture:** Explicit policy + super_admin role  
**Audit Requirement:** Policy telemetry active

---

### 2. CMS Marketing Pages (Platform Admin)

| Route | Method | Controller | Policy | Middleware | Tenant Isolation |
|-------|--------|------------|--------|------------|------------------|
| `/api/v1/admin/cms/marketing-pages` | GET | `AdminMarketingPageController::index` | `MarketingPagePolicy::viewAny` | `auth:sanctum`, `verified`, `role:super_admin` | N/A (platform resource) |
| `/api/v1/admin/cms/marketing-pages` | POST | `AdminMarketingPageController::store` | `MarketingPagePolicy::create` | `auth:sanctum`, `verified`, `role:super_admin` | N/A (platform resource) |
| `/api/v1/admin/cms/marketing-pages/{page}` | GET | `AdminMarketingPageController::show` | `MarketingPagePolicy::view` | `auth:sanctum`, `verified`, `role:super_admin` | N/A (platform resource) |
| `/api/v1/admin/cms/marketing-pages/{page}` | PUT | `AdminMarketingPageController::update` | `MarketingPagePolicy::update` | `auth:sanctum`, `verified`, `role:super_admin` | N/A (platform resource) |
| `/api/v1/admin/cms/marketing-pages/{page}` | DELETE | `AdminMarketingPageController::destroy` | `MarketingPagePolicy::delete` | `auth:sanctum`, `verified`, `role:super_admin` | N/A (platform resource) |
| `/api/v1/admin/cms/marketing-pages/{page}/publish` | POST | `AdminMarketingPageController::publish` | `MarketingPagePolicy::publish` | `auth:sanctum`, `verified`, `role:super_admin` | N/A (platform resource) |

**Approval Reason:** Marketing pages are platform-level content shared across all stores  
**Security Posture:** Explicit policy + super_admin role  
**Audit Requirement:** Policy telemetry active

---

### 3. CMS Documentation (Platform Admin)

| Route | Method | Controller | Policy | Middleware | Tenant Isolation |
|-------|--------|------------|--------|------------|------------------|
| `/api/v1/admin/cms/docs` | GET | `AdminDocumentController::index` | Permission middleware | `auth:sanctum`, `verified`, `role:super_admin`, `permission:cms.doc.view` | N/A (platform resource) |
| `/api/v1/admin/cms/docs` | POST | `AdminDocumentController::store` | Permission middleware | `auth:sanctum`, `verified`, `role:super_admin`, `permission:cms.doc.create` | N/A (platform resource) |
| `/api/v1/admin/cms/docs/{id}` | GET | `AdminDocumentController::show` | Permission middleware | `auth:sanctum`, `verified`, `role:super_admin`, `permission:cms.doc.view` | N/A (platform resource) |
| `/api/v1/admin/cms/docs/{id}` | PUT | `AdminDocumentController::update` | Permission middleware | `auth:sanctum`, `verified`, `role:super_admin`, `permission:cms.doc.update` | N/A (platform resource) |
| `/api/v1/admin/cms/docs/{id}` | DELETE | `AdminDocumentController::destroy` | Permission middleware | `auth:sanctum`, `verified`, `role:super_admin`, `permission:cms.doc.delete` | N/A (platform resource) |
| `/api/v1/admin/cms/doc-sections` | GET | `AdminDocumentSectionController::index` | Permission middleware | `auth:sanctum`, `verified`, `role:super_admin`, `permission:cms.doc.view` | N/A (platform resource) |
| `/api/v1/admin/cms/doc-sections` | POST | `AdminDocumentSectionController::store` | Permission middleware | `auth:sanctum`, `verified`, `role:super_admin`, `permission:cms.doc.create` | N/A (platform resource) |
| `/api/v1/admin/cms/doc-sections/{id}` | GET | `AdminDocumentSectionController::show` | Permission middleware | `auth:sanctum`, `verified`, `role:super_admin`, `permission:cms.doc.view` | N/A (platform resource) |
| `/api/v1/admin/cms/doc-sections/{id}` | DELETE | `AdminDocumentSectionController::destroy` | Permission middleware | `auth:sanctum`, `verified`, `role:super_admin`, `permission:cms.doc.delete` | N/A (platform resource) |

**Approval Reason:** Documentation is platform-level content shared across all stores  
**Security Posture:** Permission middleware + super_admin role  
**Audit Requirement:** Permission middleware logging

**Note:** Documentation uses permission middleware instead of explicit policies. This is acceptable for platform-level admin resources but should be migrated to explicit policies in future normalization.

---

### 4. Leads (Platform Admin)

| Route | Method | Controller | Policy | Middleware | Tenant Isolation |
|-------|--------|------------|--------|------------|------------------|
| `/api/v1/admin/leads` | GET | `AdminLeadController::index` | `LeadPolicy::viewAny` | `auth:sanctum` | N/A (platform resource) |
| `/api/v1/admin/leads/{lead}` | GET | `AdminLeadController::show` | `LeadPolicy::view` | `auth:sanctum` | N/A (platform resource) |
| `/api/v1/admin/leads/{lead}/status` | PATCH | `AdminLeadController::updateStatus` | `LeadPolicy::update` | `auth:sanctum` | N/A (platform resource) |
| `/api/v1/admin/leads/{lead}` | DELETE | `AdminLeadController::destroy` | `LeadPolicy::delete` | `auth:sanctum` | N/A (platform resource) |

**Approval Reason:** Leads are platform-level inquiries not tied to specific stores  
**Security Posture:** Explicit policy (super_admin bypass in policy `before()`)  
**Audit Requirement:** Policy telemetry active

**Wave 2 Remediation:** Added explicit `LeadPolicy` authorization to all routes

---

### 5. Authentication Routes

| Route | Method | Controller | Policy | Middleware | Tenant Isolation |
|-------|--------|------------|--------|------------|------------------|
| `/api/sanctum/csrf-cookie` | GET | `CsrfOwnershipPreparationController::show` | None (CSRF setup) | `api`, `web` | N/A (auth infrastructure) |
| `/api/v1/auth/login` | POST | `AuthController::login` | None (public) | `api`, `guest` | N/A (auth flow) |
| `/api/v1/auth/register` | POST | `AuthController::register` | None (public) | `api`, `guest` | N/A (auth flow) |
| `/api/v1/auth/logout` | POST | `AuthController::logout` | None (authenticated) | `api`, `auth:sanctum` | N/A (auth flow) |
| `/api/v1/auth/me` | GET | `AuthController::me` | None (authenticated) | `api`, `auth:sanctum` | N/A (user context) |
| `/api/v1/auth/bootstrap` | GET | `AuthController::bootstrap` | None (authenticated) | `api`, `auth:sanctum` | N/A (user context) |

**Approval Reason:** Required for authentication flow  
**Security Posture:** CSRF protection, rate limiting, guest/auth middleware  
**Audit Requirement:** Security log channel active

---

### 6. Webhook Routes

| Route | Method | Controller | Policy | Middleware | Tenant Isolation |
|-------|--------|------------|--------|------------|------------------|
| `/api/stripe/webhook` | POST | `StripeWebhookController::handle` | Signature verification | `api` | Store resolved from webhook payload |

**Approval Reason:** External service integration  
**Security Posture:** Stripe signature verification  
**Audit Requirement:** Webhook processing logged

---

## Approval Criteria Matrix

| Criterion | Platform Admin | Auth/Session | Webhook | Public Storefront |
|-----------|---------------|--------------|---------|-------------------|
| **Explicit Policy** | Required | Optional | N/A (signature) | Required |
| **Role Restriction** | super_admin | N/A | N/A | N/A |
| **CSRF Protection** | Yes | Yes | No | Yes |
| **Rate Limiting** | Optional | Required | Optional | Required |
| **Audit Logging** | Required | Required | Required | Optional |
| **Signature Verification** | N/A | N/A | Required | N/A |
| **Store Resolution** | N/A | N/A | From payload | From domain |

---

## Required Middleware Posture

### Platform Admin Routes
**Minimum Required:**
- `auth:sanctum` - Authentication
- `verified` - Email verification
- `role:super_admin` - Platform admin role

**Optional:**
- `permission:*` - Granular permissions (alternative to explicit policy)

**Forbidden:**
- `guest` - Admin routes must be authenticated

---

### Auth/Session Routes
**Minimum Required:**
- `api` - API middleware group
- `guest` OR `auth:sanctum` - Depending on route

**Optional:**
- `throttle:*` - Rate limiting
- `web` - For CSRF routes

**Forbidden:**
- `role:*` - Auth routes should not require roles

---

### Webhook Routes
**Minimum Required:**
- `api` - API middleware group

**Optional:**
- Custom signature verification middleware

**Forbidden:**
- `auth:sanctum` - Webhooks use signature verification
- `verified` - Not applicable
- `role:*` - Not applicable

---

## Tenant Isolation Requirements

### Platform-Level Resources
**Requirement:** N/A (not store-scoped)  
**Verification:** Resource model has no `store_id` column  
**Examples:** BlogPost, MarketingPage, CmsDocument, Lead

---

### Store-Resolved Resources
**Requirement:** Store must be resolved and validated  
**Verification:** 
- Store resolution documented
- Store validation enforced
- Queries scoped by resolved store

**Examples:** Storefront routes with domain-based store resolution

---

### Webhook Resources
**Requirement:** Store resolved from webhook payload  
**Verification:**
- Store ID extracted from payload
- Store validated before processing
- All operations scoped to resolved store

**Examples:** Stripe webhook processing

---

## Forbidden Route Patterns

### Pattern 1: Debug/Test Routes in Production
**Pattern:** `/api/test/*`, `/api/debug/*`  
**Status:** FORBIDDEN  
**Reason:** Security risk, information disclosure

```php
// ✗ FORBIDDEN
Route::get('/api/test/auth', function () {
    return auth()->user();
});
```

---

### Pattern 2: Unauthenticated Admin Routes
**Pattern:** Admin routes without `auth:sanctum`  
**Status:** FORBIDDEN  
**Reason:** Security risk, unauthorized access

```php
// ✗ FORBIDDEN
Route::prefix('v1/admin')
    ->group(function () {
        Route::get('/users', 'AdminUserController@index'); // No auth!
    });
```

---

### Pattern 3: Store-Scoped Routes Without Store Parameter
**Pattern:** Store operations without `{store}` parameter  
**Status:** FORBIDDEN (unless approved exception)  
**Reason:** Tenant isolation risk

```php
// ✗ FORBIDDEN (unless approved exception)
Route::get('/api/v1/products', 'ProductController@index'); // No {store}!

// ✓ ALLOWED
Route::get('/api/v1/stores/{store}/products', 'ProductController@index');
```

---

### Pattern 4: Mixed Store/Non-Store Patterns in Same Domain
**Pattern:** Inconsistent store scoping within domain  
**Status:** FORBIDDEN  
**Reason:** Architectural inconsistency

```php
// ✗ FORBIDDEN
Route::get('/api/v1/stores/{store}/products', 'ProductController@index');
Route::get('/api/v1/products/{id}', 'ProductController@show'); // Inconsistent!

// ✓ ALLOWED
Route::get('/api/v1/stores/{store}/products', 'ProductController@index');
Route::get('/api/v1/stores/{store}/products/{id}', 'ProductController@show');
```

---

## Route Classification Table

| Route Pattern | Classification | Store Scoping | Authorization | Approval Status |
|--------------|----------------|---------------|---------------|-----------------|
| `/api/v1/stores/{store}/*` | Store-scoped | Required | Policy + Membership | STANDARD |
| `/api/v1/admin/cms/*` | Platform admin | N/A | Policy + super_admin | APPROVED EXCEPTION |
| `/api/v1/admin/leads/*` | Platform admin | N/A | Policy + super_admin | APPROVED EXCEPTION |
| `/api/v1/auth/*` | Authentication | N/A | Guest/Auth middleware | APPROVED EXCEPTION |
| `/api/sanctum/*` | Session management | N/A | CSRF protection | APPROVED EXCEPTION |
| `/api/stripe/webhook` | External integration | Payload-resolved | Signature verification | APPROVED EXCEPTION |
| `/api/v1/storefront/*` | Public storefront | Domain-resolved | Customer-safe | APPROVED EXCEPTION |

---

## Governance Compliance

### ARCHITECTURE.md Compliance
- ✓ Store-scoped routes use `{store}` parameter
- ✓ Platform exceptions explicitly documented
- ✓ Authorization requirements clear
- ✓ Tenant isolation requirements defined

### EXECUTION_GOVERNANCE.md Compliance
- ✓ Exception approval criteria defined
- ✓ Security posture requirements documented
- ✓ Audit requirements specified
- ✓ Forbidden patterns identified

---

## Conclusion

This registry establishes clear governance for route exceptions. All approved exceptions have explicit approval criteria, security posture requirements, and tenant isolation verification. New exceptions must follow the approval process and meet documented criteria.

**Status:** APPROVED for Wave 2  
**Next Review:** Before Wave 3 (identity context normalization)
