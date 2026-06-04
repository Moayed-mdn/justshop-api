# CSRF Ownership Finalization

**Wave 8**  
**Status:** DRAFT — Implementation specification  
**Depends on:** Wave 6 transitional authority reduction, Wave 7 provider separation readiness

---

## Table of Contents

1. [Purpose](#1-purpose)
2. [Current State](#2-current-state)
3. [Phase 1 — Fix Telemetry Pipeline](#3-phase-1--fix-telemetry-pipeline)
4. [Phase 2 — Verify & Harden Ownership Inference](#4-phase-2--verify--harden-ownership-inference)
5. [Phase 3 — Per-Domain CSRF](#5-phase-3--per-domain-csrf)
6. [API Contracts](#6-api-contracts)
7. [Migration Plan](#7-migration-plan)
8. [Rollback Strategy](#8-rollback-strategy)
9. [Risk Assessment](#9-risk-assessment)
10. [Test Plan](#10-test-plan)
11. [Open Questions](#11-open-questions)

---

## 1. Purpose

Finalize the CSRF ownership model so that the `shared_transitional` middleware can be removed from `GET /api/sanctum/csrf-cookie` and CSRF token isolation is achieved between merchant and customer domains.

This is the last remaining `shared_transitional` route after Stripe webhooks are resolved in Wave 7.

---

## 2. Current State

### 2.1 Route Topology

| URI | Controller | Active? | Production hit? |
|---|---|---|---|
| `GET /sanctum/csrf-cookie` | `CsrfCookieController` (plain Sanctum) | Yes — via `vendor/.../SanctumServiceProvider.php:72` | **Yes** — called by Nitro proxy |
| `GET /sanctum/csrf-cookie` | `CsrfCookieController` (plain Sanctum) | Yes — via `routes/web.php:10` (but overwritten by service provider) | No — dead route |
| `GET /api/sanctum/csrf-cookie` | `CsrfOwnershipPreparationController` | Yes — via `routes/api.php:287-288` | **No** — never called by any frontend |

### 2.2 Critical Discovery

The Nitro proxy at `justshop-frontend/server/utils/api.ts:237` calls `{apiRoot}/sanctum/csrf-cookie` which resolves to the **plain Sanctum controller** (Route B). The custom `CsrfOwnershipPreparationController` (Route C) with ownership telemetry and response headers is **never exercised in production**. All ownership telemetry infrastructure is dead code.

### 2.3 Ownership Inference

`SessionOwnershipResolver::resolveForCsrf()` (line 45) uses a single heuristic:
- If `Referer` contains `/storefront/account` → `customer_account` / `customer`
- Otherwise → `merchant_users` / `merchant`
- Falls back to `Origin` if `Referer` is empty

This is fragile (see audit Section 4) and never receives a Referer anyway because the Nitro proxy does not forward Referer/Origin headers on the CSRF bootstrap call.

### 2.4 Middleware Enforcement

`ApplyIdentityRouteContext::enforceSessionOwnership()` at line 160 returns immediately for `SHARED_TRANSITIONAL` routes. Two enforcement paths are skipped:
1. Session contamination detection (cross-domain mismatch → 403)
2. Fallback guard rejection (`isFallback` → throw)

These cannot be enabled until per-domain CSRF exists.

### 2.5 Current Debt Score

- **Transitional Dependency Analyzer**: 5 points for the shared transitional CSRF route
- **Wave 8 target**: 0 points (removal of `shared_transitional` from CSRF endpoint)

---

## 3. Phase 1 — Fix Telemetry Pipeline

### 3.1 Problem

The preparation controller exists but is never called. All Phase 2 analysis depends on production telemetry.

### 3.2 Change

In `justshop-frontend/server/utils/api.ts`, change the CSRF bootstrap URL from:

```
${apiRoot}/sanctum/csrf-cookie
```

to:

```
${apiBase}/sanctum/csrf-cookie
```

Where `apiBase` = `http://localhost:8000/api/v1` (includes path prefix) and `apiRoot` = `http://localhost:8000` (bare origin). This makes the request hit Route C (`/api/v1/sanctum/csrf-cookie`) which maps to `CsrfOwnershipPreparationController`.

### 3.3 Forward Referer/Origin from Nitro to Laravel

Add the following header forwarding before the CSRF bootstrap call at `server/utils/api.ts:237`:

```typescript
// At line ~218, alongside the existing Origin forwarding
const referer = getHeader(event, 'referer')
if (referer) {
  headers.set('Referer', referer)
}
```

This gives `resolveForCsrf()` the signal it needs to distinguish customer-account requests from merchant requests.

### 3.4 Verification

1. Nitro CSRF bootstrap hits `CsrfOwnershipPreparationController::show()` instead of `CsrfCookieController::show()`
2. Response includes `X-Session-Auth-Domain`, `X-Session-Route-Domain`, `X-Future-Guard-Hint` headers
3. `session.csrf.ownership_traced` log entries appear for every CSRF bootstrap
4. No change in browser behavior: same cookie, same Set-Cookie headers
5. All existing checkout tests continue to pass

### 3.5 Files Changed

| File | Change |
|---|---|
| `justshop-frontend/server/utils/api.ts` | Change CSRF URL from `apiRoot` to `apiBase`; add `Referer` header forwarding |
| `tests/Feature/Checkout/CheckoutWebhookTest.php` | No change (webhooks don't call CSRF) |

---

## 4. Phase 2 — Verify & Harden Ownership Inference

### 4.1 Write Missing Tests

#### Priority 1 — `resolveForCsrf()` unit tests

New file: `tests/Unit/Services/Auth/SessionOwnershipResolverCsrfTest.php`

| Test | Input | Expected `authDomain` |
|---|---|---|
| Customer referer path | `Referer: http://storefront.test/storefront/account/profile` | `customer` |
| Merchant referer path | `Referer: http://admin.test/merchant/dashboard` | `merchant` |
| No referer fallback | No `Referer` header, no `Origin` header | `merchant` |
| Origin-only fallback | `Origin: http://storefront.test` | `merchant` (default — `/storefront/account` not in Origin) |
| Storefront account in Origin | `Origin: http://storefront.test/storefront/account/login` | `customer` |
| Empty string referer | `Referer: ` (empty) | `merchant` |
| Referer with query string | `Referer: http://storefront.test/storefront/account?redirect=/dashboard` | `customer` |

#### Priority 2 — `CsrfOwnershipPreparationController` test

New file: `tests/Feature/Auth/CsrfOwnershipPreparationControllerTest.php`

| Test | What it verifies |
|---|---|
| Returns 204 | Status code matches Sanctum default |
| Sets `X-Session-Auth-Domain` | Header present and matches ownership context |
| Sets `X-Session-Route-Domain` | Header present and matches ownership context |
| Sets `X-Future-Guard-Hint` | Header present and matches shadow analysis |
| Delegates to `CsrfCookieController` | Cookie set in response matches Sanctum token |
| Merchant referer path | Headers show `merchant` domain |
| Customer referer path | Headers show `customer` domain |
| No referer | Defaults to `merchant` domain |

#### Priority 3 — `shared_transitional` middleware enforcement test

Existing middleware tests should add:

| Test | What it verifies |
|---|---|
| CSRF route returns 200/204 under `shared_transitional` | Route is reachable regardless of session identity |
| CSRF route returns `X-Identity-Route-Domain: shared_transitional` | Middleware annotation visible in response |

### 4.2 Verify Ownership Telemetry

After Phase 1 deploys, collect and analyze `session.csrf.ownership_traced` log entries:

1. **Correlate authDomain values with actual request paths** — do `customer` assertions match when Referer contains `/storefront/account`?
2. **Count `guardMismatchAnomaly` occurrences** — measure how often shadow analysis finds a conflict
3. **Count `ambiguousOwnershipPath` occurrences** — measure how often both merchant and customer resolvers match

### 4.3 Telemetry-Based Decision Gate

| Metric | Threshold | Action |
|---|---|---|
| Referer missing rate | > 10% of CSRF calls | Add Origin-based fallback enhancement before Phase 3 |
| `guardMismatchAnomaly` rate | > 1% | Investigate cross-domain session contamination before Phase 3 |
| `ambiguousOwnershipPath` rate | > 5% | Add stronger resolution heuristic before Phase 3 |

---

## 5. Phase 3 — Per-Domain CSRF

### 5.1 Architecture Decision

**Chosen approach**: Single endpoint with domain-scoped session tagging (Option B).

Rejected Option A (separate endpoints) because it requires frontend route-level awareness of which CSRF endpoint to call. Rejected Option C (separate cookie names) because it conflicts with the guard split entry criteria in `EXECUTION_GOVERNANCE.md`.

**Design**:

```
Browser → Nitro proxy → Laravel CSRF endpoint
                          ↓
              Session tagged with `csrf_auth_domain`
                          ↓
              XSRF-TOKEN cookie scoped by domain metadata
                          ↓
              Subsequent requests checked: X-XSRF-TOKEN must match
              the request's resolved auth domain
```

### 5.2 Step 1 — Tag Session with CSRF Domain

In `CsrfOwnershipPreparationController::show()`, after ownership resolution:

```php
$ownership = $this->sessionOwnershipResolver->resolveForCsrf($request);
$request->session()->put('csrf_auth_domain', $ownership->authDomain);
```

This tags the session with the domain that initiated the CSRF refresh. The tag persists until the session ends or a new CSRF refresh occurs from a different domain.

### 5.3 Step 2 — Validate CSRF Token Domain

Create a middleware: `CheckCsrfDomain.php`

```php
public function handle(Request $request, Closure $next): mixed
{
    // Only check state-changing requests
    if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
        return $next($request);
    }

    $csrfDomain = $request->session()->get('csrf_auth_domain');
    $requestDomain = $this->resolveRequestDomain($request);

    if ($csrfDomain !== null && $csrfDomain !== $requestDomain) {
        throw new CsrfDomainMismatchException(ErrorCode::AUTH_012);
    }

    return $next($request);
}
```

Where `resolveRequestDomain()` uses the existing identity middleware pipeline (`IdentityContext::authDomain` for authenticated requests, `resolveForCsrf()` for guest).

### 5.4 Step 3 — Register Middleware

Add `CheckCsrfDomain` to `app/Http/Kernel.php` middleware groups:

By default, all `api` routes get it. The CSRF endpoint itself is excluded (tagging must happen before validation).

### 5.5 Step 4 — Remove `shared_transitional` from CSRF Route

Change `routes/api.php:287-288` from:

```php
Route::get('/sanctum/csrf-cookie', [CsrfOwnershipPreparationController::class, 'show'])
    ->middleware(['web', 'identity.route:shared_transitional,merchant,observe']);
```

to:

```php
Route::get('/sanctum/csrf-cookie', [CsrfOwnershipPreparationController::class, 'show'])
    ->middleware(['web']);
```

The `/api/` prefix already provides API scoping. The identity middleware is no longer needed because `CheckCsrfDomain` performs CSRF-level enforcement.

### 5.6 Step 5 — Update `TransitionalGuardResolver`

In `TransitionalGuardResolver::resolve()`, the `isFallback` check at line 23 no longer needs the `SHARED_TRANSITIONAL` exemption:

```php
// Before
$isFallback = $intendedGuard === 'web' && $context->routeDomain !== RouteDomainEnum::SHARED_TRANSITIONAL;

// After
$isFallback = $intendedGuard === 'web';
```

**Only after** the CSRF route no longer uses `shared_transitional`. This is the last step, not the first.

### 5.6 New Error Code

Add to `app/Enums/ErrorCode.php`:

```php
case AUTH_012 = 'AUTH_012'; // CSRF domain mismatch
```

New exception class:

```php
namespace App\Exceptions\Auth;

use App\Enums\ErrorCode;

class CsrfDomainMismatchException extends \App\Exceptions\BaseApiException
{
    public function __construct()
    {
        parent::__construct(
            message: 'CSRF token was issued for a different authentication domain',
            code: ErrorCode::AUTH_012,
            httpStatus: 419, // Standard CSRF status
        );
    }
}
```

### 5.7 Files Changed

| File | Change |
|---|---|
| `app/Http/Controllers/Api/Shared/Auth/Preparation/CsrfOwnershipPreparationController.php` | Add session tagging at line 28 |
| `app/Http/Middleware/CheckCsrfDomain.php` | New file |
| `app/Exceptions/Auth/CsrfDomainMismatchException.php` | New file |
| `app/Enums/ErrorCode.php` | Add `AUTH_012` |
| `routes/api.php` | Remove `shared_transitional` middleware from CSRF route |
| `app/Http/Kernel.php` | Register `CheckCsrfDomain` in middleware groups |
| `app/Services/Auth/TransitionalGuardResolver.php` | Remove `SHARED_TRANSITIONAL` exemption (line 23) — last step |

---

## 6. API Contracts

### 6.1 `GET /api/v1/sanctum/csrf-cookie`

**Request** (unchanged):
- No body
- `Referer` header (optional, for ownership inference)

**Response** (unchanged body):
- `204 No Content`
- `Set-Cookie: XSRF-TOKEN=...` (same as Sanctum default)

**Response** (new headers):
- `X-Session-Auth-Domain: customer|merchant`
- `X-Session-Route-Domain: customer_account|merchant_users`
- `X-Future-Guard-Hint: customer_guard|merchant_guard|ambiguous_guard`

**Response** (new session effect):
- `session.csrf_auth_domain` set to resolved auth domain

### 6.2 State-changing requests

**Request** (unchanged):
- `X-XSRF-TOKEN: <token>` header (browser auto-sends from cookie)
- `Cookie: XSRF-TOKEN=<token>` (Nitro proxy forwards)

**New server behavior**:
- `CheckCsrfDomain` compares `csrf_auth_domain` (from CSRF refresh session) against the request's resolved identity domain
- Returns `419 CSRF domain mismatch` with `AUTH_012` if domains don't match

### 6.3 Error Response

```json
{
  "success": false,
  "error": {
    "code": "AUTH_012",
    "message": "CSRF token was issued for a different authentication domain"
  },
  "status": 419
}
```

---

## 7. Migration Plan

### 7.1 Phase Order

```
Phase 1 (telemetry fix)
  └── Deploy to production
  └── Collect telemetry for 1 week minimum
  └── Verify: Referer/Origin headers arriving at Laravel

Phase 2 (tests + analysis)
  └── Write all missing tests in development
  └── Run against Phase 1 production data
  └── Decision gate (see 4.3)

Phase 3 (per-domain CSRF)
  └── Step 1-3: Tag, middleware, register (dev)
  └── Deploy behind feature flag: CSRF_DOMAIN_ENFORCE=false
  └── Step 4: Monitor, no incidents for 1 week
  └── Flip CSRF_DOMAIN_ENFORCE=true
  └── Step 5: Remove shared_transitional, update TransitionalGuardResolver
```

### 7.2 Feature Flag

```php
// config/auth.php or config/csrf-domain.php
'csrf_domain_enforce' => env('CSRF_DOMAIN_ENFORCE', false),
```

When `false`:
- `CheckCsrfDomain` runs but logs mismatches instead of throwing
- Session tagging (`csrf_auth_domain`) is written but not validated
- `shared_transitional` remains on the CSRF route

When `true`:
- `CheckCsrfDomain` throws on mismatch
- `shared_transitional` removed from CSRF route
- `TransitionalGuardResolver` updated

### 7.3 Deployment Sequence

| Step | Actions | Verification |
|---|---|---|
| 1 | Deploy Phase 1 (URL change, Referer forwarding) | Telemetry appears in logs |
| 2 | Deploy Phase 2 (tests only) | All tests pass |
| 3 | Deploy Phase 3 with flag OFF | Session tagging works, no enforcement |
| 4 | Monitor 1 week | No `guardMismatchAnomaly` spikes |
| 5 | Enable flag on staging | Tests pass, manual cross-domain test OK |
| 6 | Enable flag on production (canary) | No increase in 419 errors |
| 7 | Enable flag on production (100%) | Monitor 1 week |
| 8 | Remove flag, commit to enforced state | Clean up check in `CheckCsrfDomain` |

---

## 8. Rollback Strategy

### 8.1 Per-Phase Rollback

| Phase | Rollback Action | Safe? |
|---|---|---|
| 1 | Revert URL change in `api.ts`, revert Referer forwarding | Immediate — cookie not affected |
| 2 | Tests only — no production code change | N/A |
| 3 | Set `CSRF_DOMAIN_ENFORCE=false` → returns to logging-only mode | Immediate — 419 errors stop |
| 3 | If flag was removed, re-add `shared_transitional` to CSRF route | Immediate — re-enables guard exemption |

### 8.2 State Migration

- Session `csrf_auth_domain` values are ephemeral — they expire with sessions. No data migration needed.
- Existing XSRF-TOKEN cookies remain valid regardless of enforcement state.
- If rollback removes enforcement mid-session, existing CSRF tokens continue to work (they were validated at issuance, not at use).

---

## 9. Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Phase 1 URL change breaks CSRF bootstrap | Low | Users can't login/checkout | Pin to specific route name instead of URL pattern; test in staging first |
| Referer header stripped by browser/privacy extension | Medium | Ownership inference defaults to merchant | Phase 2 telemetry reveals rate; Origin fallback already exists |
| `csrf_auth_domain` mismatch during legitimate cross-tab usage | Medium | 419 errors for users with both merchant and customer tabs open | Log + enforce only per-request, not per-session; tab switching triggers new CSRF refresh |
| CSRF domain middleware conflicts with Stripe webhooks (stateless) | Low | 419 on webhook processing | Webhooks bypass CSRF middleware (already stateless, no session) |
| Phase 3 creates AUTH_012 errors at scale | Low | Customer-facing errors | Feature flag allows immediate disable; no data loss |

---

## 10. Test Plan

### 10.1 New Tests

| Test file | Tests | Phase |
|---|---|---|
| `tests/Unit/Services/Auth/SessionOwnershipResolverCsrfTest.php` | 8 unit tests | 2 |
| `tests/Feature/Auth/CsrfOwnershipPreparationControllerTest.php` | 8 feature tests | 2 |
| `tests/Feature/Auth/CsrfDomainEnforcementTest.php` | 6 tests | 3 |
| `tests/Feature/Auth/CsrfDomainMismatchExceptionTest.php` | 2 tests | 3 |

### 10.2 Test Scenarios

#### CsrfDomainEnforcementTest

| # | Scenario | Expected |
|---|---|---|
| 1 | Customer CSRF refresh → customer state-changing request | 200 OK |
| 2 | Merchant CSRF refresh → merchant state-changing request | 200 OK |
| 3 | Customer CSRF refresh → merchant state-changing request | 419 AUTH_012 |
| 4 | Merchant CSRF refresh → customer state-changing request | 419 AUTH_012 |
| 5 | No CSRF refresh → valid XSRF-TOKEN from cookie | 200 OK (backward compat) |
| 6 | Flag OFF → mismatch logged not thrown | 200 OK + log entry |

### 10.3 Existing Tests That Must Pass

```
TEST  → tests/Feature/Checkout/CheckoutInitiateTest.php          (7 tests)
TEST  → tests/Feature/Checkout/CheckoutInitiateDatabaseTest.php  (8 tests)
TEST  → tests/Feature/Checkout/CheckoutWebhookTest.php           (10 tests)
TEST  → tests/Feature/Auth/SessionGuardTelemetryTest.php         (3 tests)
TEST  → tests/Feature/Auth/GuardSplitSimulationEngineTest.php    (3 tests)
TEST  → tests/Feature/Auth/GuardSplitValidationScoringTest.php   (2 tests)
TEST  → tests/Feature/Auth/WaveThreeCGuardSplitValidationReportTest.php (1 test)
TEST  → tests/Feature/Auth/SessionOwnershipPreparationTest.php   (2 tests)
TEST  → tests/Feature/Auth/FrontendSessionMetadataTest.php       (2 tests)
```

---

## 11. Open Questions

1. **Should `csrf_auth_domain` be validated at the middleware level or the session level?** Middleware = per-request check (recommended). Session = persists across requests but could stale.
2. **What exact route name should Phase 1 use?** Currently `{apiBase}/sanctum/csrf-cookie`. Should we use a named route (`route('api.v1.sanctum.csrf-cookie')`) instead of URL construction? The Nitro proxy doesn't have route helper access — it constructs full URLs.
3. **Should the Nitro proxy forward `Referer` for CSRF bootstrap but not other endpoints?** Yes — only the CSRF bootstrap needs it. Other endpoints have authenticated identity context.
4. **Is `419` the correct HTTP status for CSRF domain mismatch?** Sanctum uses 419 for CSRF token mismatch. Using the same status for domain mismatch is consistent with existing Laravel convention.
5. **Should `CheckCsrfDomain` apply to GraphQL endpoints?** Apollo client sets `X-XSRF-TOKEN` from cookie. The middleware checks the session tag, not the cookie content. GraphQL should be covered — but only if GraphQL uses sessions (confirm).
