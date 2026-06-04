# Wave 8 Architecture Validation

**Status:** VERIFIED_COMPLETE  
**Date:** 2026-06-02  
**Purpose:** Formal architecture review of proposed Phase 3 design before implementation  
**Scope:** Design validation only — no code written, no files modified

---

## Table of Contents

1. [Existing Architecture Compatibility](#part-1--existing-architecture-compatibility)
2. [Session Lifecycle Analysis](#part-2--session-lifecycle-analysis)
3. [Sanctum Compatibility](#part-3--sanctum-compatibility)
4. [Security Validation](#part-4--security-validation)
5. [Governance Compliance](#part-5--governance-compliance)
6. [Alternative Designs](#part-6--alternative-designs)
7. [Final Recommendation](#part-7--final-recommendation)

---

## Part 1 — Existing Architecture Compatibility

### 1.1 Current Session Ownership Model

The codebase already has an explicit session ownership system established in Wave 4.

**Three session keys** are written by `SessionOwnershipManager::tag()` during login/register:

| Session Key | Set By | When | Purpose |
|---|---|---|---|
| `auth_domain` | `SessionOwnershipManager::tag()` | After successful authentication | Tracks which domain (merchant/customer) authenticated |
| `actor_type` | `SessionOwnershipManager::tag()` | After successful authentication | Tracks actor type |
| `actor_id` | `SessionOwnershipManager::tag()` | After successful authentication | Tracks authenticated user ID |

**Sources**:
- `app/Services/Auth/SessionOwnershipManager.php:17-22` — writes `auth_domain`, `actor_type`, `actor_id`
- `app/Http/Controllers/Api/Merchant/AuthController.php` — calls `$this->sessionOwnershipManager->tag($request, $user, 'merchant')` after login
- `app/Http/Controllers/Api/Storefront/Account/StorefrontAccountController.php:78` — calls `$this->sessionOwnershipManager->tag($request, $user, 'customer')` after login
- `docs/auth/sessions/actor-bound-session-ownership.md:13-19` — documents the three keys

**These session keys are consumed by**:
- `ApplyIdentityRouteContext::enforceSessionOwnership()` at line 165 — reads `sessionAuthDomain` to detect contamination
- `SanctumAuthorityResolver::resolve()` at line 21-23 — reads `auth_domain`, `actor_type`, `actor_id`
- `SessionOwnershipResolver::resolve()` at line 29 — reads `sessionAuthDomain` from session

### 1.2 Proposed Addition: `csrf_auth_domain`

The Phase 3 design proposes:

```php
$request->session()->put('csrf_auth_domain', $ownership->authDomain);
```

This adds a **fourth** ownership session key. The question is whether this creates a competing authority source.

### 1.3 Conflict Analysis

| Dimension | `auth_domain` (existing) | `csrf_auth_domain` (proposed) |
|---|---|---|
| **Set by** | `SessionOwnershipManager::tag()` | `CsrfOwnershipPreparationController::show()` |
| **When** | After authentication (login/register) | During CSRF token refresh |
| **Auth state** | Authenticated | Pre-auth or guest |
| **Resolution method** | Identity context from authenticated user | `resolveForCsrf()` using Referer heuristic |
| **Consumed by** | Contamination detection, guard resolution | `CheckCsrfDomain` middleware |
| **Scope** | Session lifetime (until logout) | Session lifetime (until overwritten by next CSRF refresh) |

**Verdict: These are competing authority sources.** They serve different purposes (auth identity vs CSRF origin), but both attempt to answer the same question: "Which domain does this session belong to?"

The critical risk is **divergence**: `auth_domain` could be `merchant` (set during merchant login) while `csrf_auth_domain` could be `customer` (set by a customer-tab CSRF refresh in the same session). When `CheckCsrfDomain` validates a merchant request against `csrf_auth_domain = customer`, it would reject a legitimate request.

### 1.4 Existing Ownership Resolution Paths

The codebase has **two parallel resolution methods** in `SessionOwnershipResolver`:

| Method | Inputs | Used by |
|---|---|---|
| `resolve()` | IdentityContext + RouteDomainContext | `ApplyIdentityRouteContext` middleware for all annotated routes |
| `resolveForCsrf()` | Referer/Origin header only | `CsrfOwnershipPreparationController` for CSRF endpoint |

The `resolve()` method is the authoritative path — it uses authenticated identity + route annotation. The `resolveForCsrf()` method is a degraded heuristic that works pre-auth. The proposed `csrf_auth_domain` would be set by the degraded heuristic but used to validate requests that may have already been through `resolve()`.

### 1.5 Existing `auth_domain` Detection in Middleware

`ApplyIdentityRouteContext::enforceSessionOwnership()` already checks session ownership:

```php
// Line 160 — SHARED_TRANSITIONAL exemption
if ($sessionOwnership->routeDomain === RouteDomainEnum::SHARED_TRANSITIONAL) {
    return;
}

// Line 165 — Contamination detection
if ($sessionOwnership->sessionAuthDomain !== null
    && $sessionOwnership->sessionAuthDomain !== $sessionOwnership->authDomain) {
    throw new InvalidIdentityDomainAccessException('Session contamination detected.');
}
```

This means the **existing `auth_domain` session key is already being used for domain enforcement** on non-transitional routes. The proposed `csrf_auth_domain` enforcement would run in parallel to this existing check, creating two separate ownership gates on authenticated requests.

### 1.6 Risk Summary

| Risk | Severity | Explanation |
|---|---|---|
| Dual ownership systems | **High** | `auth_domain` (authenticated domain) and `csrf_auth_domain` (CSRF origin) can diverge in the same session |
| Inconsistent enforcement | **High** | `enforceSessionOwnership()` checks `auth_domain`; `CheckCsrfDomain` checks `csrf_auth_domain` — two gates, two sources of truth |
| CSRF tag from degraded heuristic | **Medium** | `resolveForCsrf()` uses Referer heuristic which defaults to `merchant` when no Referer is present — unreliable input used for enforcement |
| No integration with `SessionOwnershipManager` | **Medium** | The proposed code writes `csrf_auth_domain` directly with `$request->session()->put()`, bypassing the existing `SessionOwnershipManager` service |

---

## Part 2 — Session Lifecycle Analysis

### 2.1 Scenario A — Merchant Single-Tab

```
Timeline:
  t0: Merchant login → SessionOwnershipManager::tag($request, $user, 'merchant')
                        Session keys: auth_domain=merchant, actor_type=merchant, actor_id=1

  t1: POST /api/v1/users/auth/logout (no XSRF-TOKEN cookie)
      → Nitro CSRF bootstrap fires
      → CsrfOwnershipPreparationController
      → resolveForCsrf(Request) → Referer = "https://admin.example.com/merchant/dashboard"
      → Referer contains "/storefront/account"? No → routeDomain = 'merchant_users', authDomain = 'merchant'
      → session.csrf_auth_domain = 'merchant'
      → Session keys: auth_domain=merchant, csrf_auth_domain=merchant ✓

  t2: POST /api/v1/users/auth/logout
      → CheckCsrfDomain: csrf_auth_domain=merchant vs request domain (identity=merchant) → match ✓
      → Request proceeds
```

**Result: Works correctly. Single domain, single tab, no conflict.**

### 2.2 Scenario B — Customer Single-Tab

```
Timeline:
  t0: Customer login → SessionOwnershipManager::tag($request, $user, 'customer')
                        Session keys: auth_domain=customer, actor_type=customer, actor_id=2

  t1: POST /api/v1/storefront/account/logout (no XSRF-TOKEN cookie)
      → Nitro CSRF bootstrap fires
      → resolveForCsrf(Request) → Referer = "https://store.example.com/storefront/account/profile"
      → Referer contains "/storefront/account"? Yes → routeDomain = 'customer_account', authDomain = 'customer'
      → session.csrf_auth_domain = 'customer'
      → Session keys: auth_domain=customer, csrf_auth_domain=customer ✓

  t2: POST /api/v1/storefront/account/logout
      → CheckCsrfDomain: csrf_auth_domain=customer vs request domain (identity=customer) → match ✓
      → Request proceeds
```

**Result: Works correctly. Single domain, single tab, no conflict.**

### 2.3 Scenario C — Multi-Tab Multi-Domain (THE CRITICAL SCENARIO)

```
Browser session cookie: ecommerce_session=abc123 (shared across both tabs)

╔══════════════════════════════════════════════════════════════╗
║  Tab 1: Merchant Admin                Tab 2: Storefront    ║
║  https://admin.example.com             https://store.example.com
╠══════════════════════════════════════════════════════════════╣
║  Session cookie: abc123               Session cookie: abc123║
║  XSRF-TOKEN: (separate)               XSRF-TOKEN: (separate)
╚══════════════════════════════════════════════════════════════╝

Timeline:
  t0: Tab 1 merchant login → SessionOwnershipManager::tag()
                              Session: auth_domain=merchant, actor_type=merchant

  t1: Tab 1 performs action (XSRF-TOKEN missing)
      → Nitro CSRF bootstrap fires
      → Referer = "https://admin.example.com/merchant/dashboard"
      → resolveForCsrf → "/storefront/account" not found → authDomain='merchant'
      → Session: csrf_auth_domain=merchant ← written by Tab 1

  t2: Tab 2 performs action (XSRF-TOKEN missing)
      → Nitro CSRF bootstrap fires
      → Referer = "https://store.example.com/storefront/account/orders"
      → resolveForCsrf → "/storefront/account" found → authDomain='customer'
      → Session: csrf_auth_domain=customer ← OVERWRITTEN by Tab 2

  t3: Tab 1 makes state-changing request (XSRF-TOKEN still valid, no new CSRF refresh)
      → CheckCsrfDomain:
          csrf_auth_domain = customer  ← set by Tab 2 at t2
          request identity domain = merchant  ← from authenticated session
          → csrf_auth_domain != request domain → 419 AUTH_012 ✗ WRONG!
```

**Result: FAILS. Tab 1's legitimate merchant request is rejected because Tab 2 overwrote `csrf_auth_domain`.**

### 2.4 Underlying Cause

The `csrf_auth_domain` is a **single session value** that gets overwritten by every CSRF refresh. Two tabs sharing the same session cookie will overwrite each other's CSRF domain tag. The last tab to trigger a CSRF refresh determines the tag for ALL subsequent requests from ALL tabs.

### 2.5 Can `csrf_auth_domain` Oscillate?

**Yes.** With two tabs open (merchant + customer), each tab switch that triggers a CSRF refresh will overwrite the tag:

```
Tab 1 refresh → csrf_auth_domain=merchant
Tab 2 refresh → csrf_auth_domain=customer
Tab 1 refresh → csrf_auth_domain=merchant
Tab 2 refresh → csrf_auth_domain=customer
...
```

Each CSRF refresh toggles the value. This oscillation is unavoidable because:
- The XSRF-TOKEN cookie is scoped per frontend domain
- Each domain independently triggers CSRF refresh when its cookie is missing
- But the session cookie is shared
- Session data (including `csrf_auth_domain`) is per-session, not per-request-source

### 2.6 Does One Tab Invalidate Another?

Not directly — the CSRF token itself remains valid. But the `csrf_auth_domain` tag can change, causing the `CheckCsrfDomain` middleware to reject requests from the other tab.

### 2.7 Can the Oscillation Be Mitigated?

Possible mitigations and their limitations:

| Mitigation | Limitation |
|---|---|
| Don't overwrite `csrf_auth_domain` if it already matches the request's `auth_domain` | The tag was set pre-auth (CSRF refresh happens before identity is known at the controller level) |
| Check `csrf_auth_domain` against `auth_domain` — skip enforcement if `auth_domain` is present | Defeats the purpose for authenticated users — CSRF domain check is redundant when `auth_domain` enforcement already exists |
| Use per-domain session cookies instead of shared session | This is a cookie split — explicitly deferred from Waves 3A-3C, requires governance waiver |
| Validate `csrf_auth_domain` only for guest requests | Reduces scope to pre-auth only — authenticated users already have `auth_domain` enforcement |

---

## Part 3 — Sanctum Compatibility

### 3.1 Sanctum CSRF Token Validation

Laravel Sanctum's CSRF verification is implemented in `vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/VerifyCsrfToken.php`.

The core validation at line 134-141:

```php
protected function tokensMatch($request)
{
    $token = $this->getTokenFromRequest($request);

    return is_string($request->session()->token()) &&
           is_string($token) &&
           hash_equals($request->session()->token(), $token);
}
```

**Simplified**: It compares `session.token()` against the decrypted `X-XSRF-TOKEN` header (or `_token` input field). It uses `hash_equals()` for timing-safe comparison.

**There is no ownership concept in Sanctum's CSRF verification.** All CSRF tokens in the same session are equivalent. Sanctum has no concept of:
- Which domain or guard requested the token
- Which user or actor the token belongs to
- Whether the token was issued for merchant or customer use

### 3.2 Sanctum Stateful Domain Detection

`EnsureFrontendRequestsAreStateful::fromFrontend()` at line 73-92 checks the `Referer` or `Origin` header against the configured `sanctum.stateful` domains. This determines whether to apply Sanctum's frontend middleware stack (EncryptCookies, StartSession, VerifyCsrfToken, AuthenticateSession). It does NOT:
- Tag the CSRF token with domain information
- Restrict token usage to a specific domain
- Create separate token pools per domain

### 3.3 Sanctum Middleware Pipeline

From `EnsureFrontendRequestsAreStateful::frontendMiddleware()` (line 48-65):

```php
protected function frontendMiddleware()
{
    $middleware = [
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ValidateCsrfToken::class,   // alias for VerifyCsrfToken
        AuthenticateSession::class,
    ];
    // ...
}
```

There is no ownership-aware middleware in Sanctum's pipeline. The session middleware (`StartSession`) restores the session token, and `VerifyCsrfToken` checks it. Neither has any domain-awareness.

### 3.4 What Wave 8 Would Add vs Sanctum

| Layer | Sanctum understands | Wave 8 adds |
|---|---|---|
| Token comparison | `hash_equals(session.token, request.token)` | Same |
| Token → domain mapping | None | `csrf_auth_domain` session tag |
| Domain enforcement on CSRF | None | `CheckCsrfDomain` middleware |
| Domain mismatch handling | Throws `TokenMismatchException` on token mismatch | Throws `CsrfDomainMismatchException` (419) on domain mismatch |

### 3.5 Compatibility Verdict

**Wave 8 would create ownership rules that Sanctum does not understand.** The `CheckCsrfDomain` middleware is a custom layer on top of Sanctum's `VerifyCsrfToken`. Key consequences:

1. **Sanctum's token validation runs first** (in the middleware pipeline). If the token is valid, Sanctum passes. Then `CheckCsrfDomain` runs and may reject the request for domain mismatch — even though the token itself is cryptographically valid.

2. **Laravel treats all XSRF-TOKEN cookies as equivalent**. The `CsrfCookieController` (and our `CsrfOwnershipPreparationController`) always set the same `XSRF-TOKEN` cookie. The Wave 8 design does NOT change what cookie is set — only adds a session-side validation check. The cookie itself has no ownership metadata.

3. **Token regeneration during logout removes all ownership tagging**. `SessionOwnershipManager::invalidate()` calls `$request->session()->invalidate()` and `$request->session()->regenerateToken()`. After logout, the `csrf_auth_domain` tag is cleared (session is destroyed). This is correct behavior — the CSRF domain tag should not survive logout.

### 3.6 Risk: Validation Order

The `CheckCsrfDomain` middleware must run AFTER `VerifyCsrfToken` (so the token is validated before the domain check) but BEFORE route handler execution. If it runs before `VerifyCsrfToken`, a domain mismatch would reject a request before the token is even checked — potentially leaking information about valid session states.

If it runs after, the sequence would be:
1. `VerifyCsrfToken` validates the token → **passes**
2. `CheckCsrfDomain` validates the domain → may **reject**
3. The request never reaches the controller

The controller receives a 419 error without any indication that the token itself was valid. This is a security improvement (no information leakage) but could be confusing to debug.

---

## Part 4 — Security Validation

### 4.1 Can an Attacker Spoof `csrf_auth_domain`?

**Attack surface**: The `csrf_auth_domain` session key is set by `CsrfOwnershipPreparationController::show()` based on `resolveForCsrf()`, which reads the `Referer` header (or `Origin` as fallback).

**The Referer header is forwarded from the browser through the Nitro proxy.** The proxy reads `getHeader(event, 'referer')` from the incoming request and adds it to the headers sent to Laravel.

#### Attack Path A — Referer Manipulation via Meta Tag

```html
<meta name="referrer" content="no-referer">
<!-- or -->
<meta name="referrer" content="origin">
```

If an attacker can inject a meta tag into a page the victim visits, the Referer sent with requests from that page can be suppressed (to empty) or limited to origin-only.

**Impact**: Suppressed Referer defaults `resolveForCsrf()` to `merchant`. The attacker can make CSRF refreshes from a customer page be tagged as merchant. But this is a one-way attack (customer → merchant), and the result is that customer requests are tagged with merchant — meaning they'd pass merchant-domain checks but potentially fail customer-domain checks.

**Mitigation**: The Referer tag would need to be `https://victim.com/storefront/account/...` for the customer path. If suppressed, falls to merchant.

#### Attack Path B — Origin Manipulation

The `Origin` header is also forwarded (line 219-222 in `api.ts`). The `Origin` header is set by the browser and cannot be overridden by JavaScript for cross-origin requests (it's a forbidden header). For same-origin requests, the Origin is the page's origin.

**Impact**: Cannot be spoofed for cross-origin requests. For same-origin, the attacker doesn't need CSRF manipulation because they already control the page.

#### Attack Path C — Direct Nitro-to-Laravel Request

An attacker would need to send a direct request to `http://localhost:8000/api/v1/sanctum/csrf-cookie` with a forged `Referer` header. This requires:
1. Access to the internal network (Nitro-to-Laravel is server-to-server, not exposed to the internet)
2. Knowledge of the internal Laravel host

If the attacker has internal network access, they can set any Referer value, tagging the session with any domain. However:
- The attacker would need the victim's session cookie to actually impact the victim
- The `csrfauth_domain` tag only affects one session at a time
- The tag is overwritten by the next legitimate CSRF refresh from any tab

### 4.2 Attack Path D — Session Fixation + Domain Tagging

If an attacker can obtain a valid session ID for a victim (e.g., via session fixation), they could:
1. Send a CSRF refresh with merchant Referer → `csrf_auth_domain = merchant`
2. Victim uses the session → all requests validated against merchant domain
3. If victim's actual requests are customer-domain, they get 419 errors (denial of service)

**Impact**: DoS for one session. The victim would need to log out (which destroys the session) to escape.

**Mitigation**: Session fixation is already mitigated by Sanctum's session regeneration on login.

### 4.3 Attack Path E — Cross-Domain CSRF Token Reuse

This is the existing risk that Wave 8 aims to mitigate:
- Merchant page sets XSRF-TOKEN cookie
- Attacker tricks victim's browser into sending that cookie + X-XSRF-TOKEN header to a customer endpoint
- The shared token is valid for both domains → state-changing action on customer domain succeeds

**Wave 8 mitigation**: `CheckCsrfDomain` checks `csrf_auth_domain` against the request's identity domain. If the CSRF token was issued from a merchant Referer, it would be tagged as `merchant`, and using it on a customer endpoint would fail.

**Attack bypass**: The attacker triggers a CSRF refresh from a customer page first (setting `csrf_auth_domain = customer`), then uses that token. This requires the attacker to have the victim first visit a customer page — limiting the attack window.

### 4.4 Ownership State Poisoning

An attacker who can trigger a CSRF refresh on a victim's active session can set `csrf_auth_domain` to any value resolvable by `resolveForCsrf()`. The attacker can only choose between `merchant` (default/no-Referer) and `customer` (Referer contains `/storefront/account`). This is a limited choice set.

After poisoning, the next state-changing request from the victim's legitimate tab may fail (if the domains don't match). The victim would need to reload the page (triggering a new CSRF refresh with the correct Referer) to fix the tag.

### 4.5 Security Verdict

| Attack | Feasibility | Impact | Mitigated by |
|---|---|---|---|
| Referer suppression | Medium — meta tag injection required | Low — defaults to merchant | Limited scope — only two domain choices |
| Origin forgery | Low — browser prevents cross-origin overrides | N/A | Browser security model |
| Direct Nitro exploitation | Low — requires internal network access | Medium — session-level DoS | Network security boundaries |
| Session fixation + tagging | Low — Sanctum regenerates on login | Low — single session DoS | Sanctum session regeneration |
| Cross-domain token reuse | Medium — existing risk | Medium — state-changing CSRF | Wave 8 partial mitigation |
| Ownership poisoning | Medium — requires CSRF refresh trigger | Low — temporary, fixed by next refresh | Session tag volatility |

**The proposed design does not introduce new high-severity vulnerabilities** but **also does not fully solve** the cross-domain CSRF token reuse problem because the `csrf_auth_domain` tag can be overwritten.

---

## Part 5 — Governance Compliance

### 5.1 The Question

Does session tagging satisfy governance, or is it effectively a cookie split implemented indirectly?

### 5.2 What the Governance Forbids

`docs/EXECUTION_GOVERNANCE.md` repeatedly forbids **"cookie split"** across multiple waves:

- **Wave 3A** (line 126): `- cookie split`
- **Wave 3B** (line 163): `- cookie split`
- **Wave 3C** (line 201): `- cookie split`

The governance also forbids:
- `session split` (Wave 3A, line 127)
- `customer-only session storage` (Wave 3A, line 128)
- `merchant-only session storage` (Wave 3A, line 129)

### 5.3 What the Governance Allows

The governance explicitly allows **session tagging** as a legitimate additive pattern. Evidence:

`docs/auth/governance/auth-surface-classification.md:18-19,30-31`:
> "Creates a shared Laravel session, then tags it as `merchant`."
> "Creates a shared Laravel session, then tags it as `customer`."

`docs/auth/sessions/actor-bound-session-ownership.md:9`:
> "Session ownership is now explicitly tracked by tagging sessions with actor metadata during the authentication lifecycle."

`docs/auth/governance/auth-rollback-matrix.md:16`:
> "Session Tagging | Session corruption | Revert `SessionOwnershipManager` usage"

The `auth_domain` session tag was implemented in Wave 4 and is an **active, governance-approved pattern**. It tags a shared session with ownership metadata.

### 5.4 Is `csrf_auth_domain` a Cookie Split?

**No.** A cookie split would involve:
- Different cookie names (e.g., `merchant_session` vs `customer_session`)
- Different cookie paths or domains
- Independent session storage per domain
- Independent CSRF tokens per domain

The proposed `csrf_auth_domain` is:
- A server-side session key (not a cookie)
- Stored within the existing shared Laravel session
- Set by the same CSRF endpoint
- Cleared when the session is destroyed

It follows the exact same pattern as the existing `auth_domain` tag.

### 5.5 Is `CheckCsrfDomain` a Cookie Split?

The middleware validates that the CSRF domain tag matches the request's identity domain. It does NOT:
- Create separate cookie namespaces
- Split session storage
- Require different cookies per domain
- Change how the XSRF-TOKEN cookie is set or read

It is a **validation layer** on top of the existing shared cookie, not a cookie split.

### 5.6 Governance Verdict

| Concern | Assessment |
|---|---|
| Is this a cookie split? | **No.** Same single XSRF-TOKEN cookie, same session, same endpoint. Only a session-side validation tag is added. |
| Does this require governance waiver? | **No.** It follows the existing `auth_domain` session tagging pattern that was governance-approved in Wave 4. |
| Does this conflict with Wave 3A-3C forbidden scope? | **No.** Cookie split, session split, and domain-specific storage remain untouched. |
| Does this align with Wave 8 debt roadmap? | **Yes.** The debt roadmap defines Wave 8 as "CSRF ownership finalization" without specifying implementation approach. |

**However**: If `csrf_auth_domain` tag causes operational issues (e.g., 419 errors on valid requests due to oscillations), it may effectively force a cookie split as a remediation step. If the governance forbids cookie split at the architecture level, the oscillation problem must be solved within the session-tagging model.

### 5.7 Feature Flag Compliance

The governance requires feature flags for all enforcement changes (`docs/EXECUTION_GOVERNANCE.md:396-398`):
> "Runtime flags: can be toggled without deploy; used for cutover, canary, rollback, shadow-read, dual-write, kill switches."

The proposed `CSRF_DOMAIN_ENFORCE=false` flag complies with this requirement. It enables:
- Canary deployment (flag ON for subset of traffic)
- Shadow mode (flag OFF, logging only)
- Immediate rollback (toggle flag to OFF)

---

## Part 6 — Alternative Designs

### 6.1 Option A — Current Proposal (Single Endpoint + Session Tagging)

| Dimension | Assessment |
|---|---|
| **Description** | Single CSRF endpoint. `csrf_auth_domain` session key. `CheckCsrfDomain` middleware. Feature-flag enforcement. |
| **Complexity** | Low — 1 new middleware, 1 session key, 1 error code |
| **Migration risk** | **High** — multi-tab oscillation causes 419 errors on legitimate requests. Last-writer-wins design flaw. |
| **Governance compliance** | Compliant — follows existing session tagging pattern |
| **Rollback complexity** | Low — feature flag toggle or remove middleware |
| **Operational risk** | **High** — undetected oscillation in production would cause hard-to-debug 419 errors for multi-tab users |
| **Security** | Partial — prevents simplest cross-domain reuse but not robust against tag overwriting |

### 6.2 Option B — Telemetry Only (Phases 1 + 2, No Phase 3)

| Dimension | Assessment |
|---|---|
| **Description** | Phase 1 (fix telemetry pipeline) + Phase 2 (tests + baseline). No enforcement. `shared_transitional` remains on CSRF route. |
| **Complexity** | Minimal — no new middleware, no session key, no error code |
| **Migration risk** | None — no behavioral change |
| **Governance compliance** | Fully compliant — telemetry is additive infrastructure |
| **Rollback complexity** | Trivial — revert URL change in `api.ts` |
| **Operational risk** | None — no enforcement point |
| **Security** | No improvement — same shared token as today |
| **Evidence value** | **High** — provides production data to design correct Phase 3 |

### 6.3 Option C — Ownership-Aware CSRF Verifier

| Dimension | Assessment |
|---|---|
| **Description** | Instead of session tagging, create a custom CSRF verifier that decorates tokens with domain metadata. The XSRF-TOKEN cookie value is prefixed with the domain: `merchant:token_value` or `customer:token_value`. The verifier checks the prefix matches the request domain. |
| **Complexity** | **High** — requires custom `VerifyCsrfToken` override, custom `CsrfCookieController`, decryption logic changes, cookie format change |
| **Migration risk** | High — existing tokens without domain prefix would be rejected. Must handle backward compatibility. |
| **Governance compliance** | Borderline — this IS effectively a cookie split via content, not name. Could be interpreted as circumventing the governance. |
| **Rollback complexity** | High — requires dual-read of prefixed and unprefixed tokens during migration window |
| **Operational risk** | High — token format change could cause cascading failures |
| **Security** | Strong — domain is cryptographically bound to the token. No oscillation. |

### 6.4 Option D — Multiple CSRF Endpoints

| Dimension | Assessment |
|---|---|
| **Description** | Three endpoints: `GET /sanctum/csrf-cookie/merchant`, `GET /sanctum/csrf-cookie/customer`, `GET /sanctum/csrf-cookie` (legacy fallback). Nitro selects based on route domain. Each sets the same XSRF-TOKEN but session-tags differently. |
| **Complexity** | Medium — 3 routes, frontend routing logic, session key |
| **Migration risk** | Medium — frontend must be updated to call the correct endpoint per route |
| **Governance compliance** | Compliant — still uses shared session, shared token, no cookie split |
| **Rollback complexity** | Medium — fall back to legacy endpoint for all routes |
| **Operational risk** | Medium — Nitro must maintain route-to-endpoint mapping; any mismatch causes 419 |
| **Security** | Medium — different endpoints give different tags, but oscillation still exists with shared session |

### 6.5 Option E — Per-Guard CSRF Tokens (Gold Standard)

| Dimension | Assessment |
|---|---|
| **Description** | Separate CSRF token pool per guard domain. Separate cookie names: `XSRF-TOKEN-MERCHANT`, `XSRF-TOKEN-CUSTOMER`. Separate session storage keys per guard. Each Nitro domain (admin/storefront) refreshes only its own token. |
| **Complexity** | **High** — requires cookie split, session split, dual token storage, custom `VerifyCsrfToken`, custom `CsrfCookieController`, frontend routing changes |
| **Migration risk** | **High** — multiple components change simultaneously |
| **Governance compliance** | **Requires waiver** — cookie split was explicitly deferred from Waves 3A-3C. Not permitted without architecture review. |
| **Rollback complexity** | **High** — dual-token format with backward compatibility shunt |
| **Operational risk** | **High** — cascading failures possible across cookie, session, CSRF, and guard subsystems |
| **Security** | **Strongest** — complete token isolation between domains. No oscillation, no cross-use. |

### 6.6 Comparison Matrix

| Criterion | A (Current) | B (Telemetry) | C (Verifier) | D (Multi-endpoint) | E (Per-guard) |
|---|---|---|---|---|---|
| Implementation effort | Low | Minimal | High | Medium | Very high |
| Multi-tab safety | **Fails** | N/A | Safe | **Fails** | Safe |
| Security improvement | Partial | None | Strong | Partial | Complete |
| Governance risk | None | None | Medium | None | **High** |
| Rollback time | Minutes | Minutes | Hours | Minutes | Hours-days |
| Operational risk | High | None | High | Medium | Very high |
| Telemetry value | Low | High | Low | Low | Low |
| Forward compatibility | Low | High | Medium | Low | High |

---

## Part 7 — Final Recommendation

### 7.1 Verdict

**Do not implement Phase 3 as currently designed.** The session tagging approach has a fundamental architectural flaw: **`csrf_auth_domain` oscillation in multi-tab multi-domain scenarios** causes false-positive 419 errors that cannot be resolved without a cookie split.

### 7.2 The Flaw

```
Problem:
  Two tabs, same session, different domains.
  Last CSRF refresh writes csrf_auth_domain.
  Next request from other tab sees wrong domain tag.
  → 419 error on legitimate request.

Root cause:
  Single session value cannot track per-origin CSRF state.
  Session is shared; CSRF refresh is per-frontend-domain.
  These are contradictory constraints.

Resolution requires:
  Either per-domain sessions (cookie split — governance-restricted)
  Or no enforcement based on session-scoped CSRF domain tags
```

### 7.3 Recommended Path

**Implement Phase 1 + Phase 2 only. Do not proceed to enforcement.**

| Phase | Action | Status |
|---|---|---|
| Phase 1 | Fix telemetry pipeline (URL change + Referer forwarding) | **Safe — proceed** |
| Phase 2 | Write tests + collect baseline + analyze ownership data | **Safe — proceed** |
| Phase 3 (current design) | Session tagging + CheckCsrfDomain middleware | **Do not implement** |

### 7.4 Evidence

The evidence against Phase 3 enforcement is:

1. **Session lifecycle analysis** (Part 2.3) — Scenario C demonstrates the oscillation failure with a concrete timeline. Merchant tab's request is rejected because customer tab overwrote `csrf_auth_domain`.

2. **Competing authority sources** (Part 1.3) — `auth_domain` and `csrf_auth_domain` can diverge. Two ownership systems in the same session create contradictory enforcement gates.

3. **Sanctum incompatibility** (Part 3.5) — Sanctum has no ownership model. Our `CheckCsrfDomain` would be a foreign layer on top of Sanctum's token validation, causing token-valid but domain-rejected responses (419 errors).

4. **`resolveForCsrf()` degraded heuristic** (Part 4) — The CSRF domain tag is set using Referer-based inference that defaults to `merchant` when no Referer is present. Enforcement based on this unreliable input would produce inconsistent results.

5. **Telemetry gap** (Part 6.2) — Without Phase 1+2 production data, we have zero information about how often multi-tab scenarios occur, what the Referer coverage rate is, or whether the oscillation risk is theoretical or practical.

### 7.5 Path Forward

```
Phase 1 (telemetry fix) + Phase 2 (tests + baseline)
                            ↓
              Collect production data for 2-4 weeks
                            ↓
              Analyze: multi-tab frequency, Referer coverage,
              guardMismatchAnomaly rate, ambiguousOwnershipPath rate
                            ↓
              Decision gate based on real data:
                            │
              ┌─────────────┴─────────────┐
              ↓                           ↓
     Low multi-tab rate          High multi-tab rate
     Proceed to redesigned       Postpone enforcement
     Phase 3                      until cookie split
                                  governance is resolved
```

### 7.6 Redesigned Phase 3 Options

If telemetry shows low multi-tab conflict (e.g., < 1% of sessions), a redesigned Phase 3 could:

**Use `auth_domain` instead of `csrf_auth_domain` for CSRF validation:**

Instead of tagging the session with a new `csrf_auth_domain` key during CSRF refresh, validate the incoming request's CSRF token against the already-existing `auth_domain` session key. This eliminates the oscillation problem because `auth_domain` is set once during login and doesn't change until logout.

For guest requests (no `auth_domain`), skip CSRF domain enforcement. Guest CSRF tokens are inherently domain-agnostic.

**Implementation**:
```php
// CheckCsrfDomain middleware
public function handle(Request $request, Closure $next): mixed
{
    // Guest requests: no auth_domain, no enforcement
    $authDomain = $request->session()->get('auth_domain');
    if ($authDomain === null) {
        return $next($request);
    }

    // Authenticated requests: validate CSRF against auth_domain
    $requestDomain = $this->resolveRequestDomain($request);
    if ($authDomain !== $requestDomain) {
        // Log mismatch, throw 419 if enforcement enabled
    }

    return $next($request);
}
```

**Benefit**: Uses the existing `auth_domain` (set during login, stable for the session) instead of a new oscillating tag. CSRF tokens issued during a merchant session can only be used for merchant requests. Guest CSRF is unconstrained.

**Limitation**: Does not protect against guest-initiated CSRF being reused across domains. But guest CSRF is a limited-risk scenario (no authenticated actions).

This redesigned approach can only be validated with Phase 2 telemetry data showing how often `auth_domain` exists during CSFR validation and how often mismatches occur.

### 7.7 Summary

| Question | Answer |
|---|---|
| Should Phase 3 (session tagging + enforcement) be implemented as designed? | **No** — oscillation flaw makes it unsafe for multi-tab users |
| Is the session tagging approach itself a cookie split? | **No** — it follows the existing `auth_domain` pattern, governance-compliant |
| Should Phase 1 and Phase 2 proceed? | **Yes** — they are safe, additive, and produce essential telemetry |
| Can Phase 3 be redesigned to avoid oscillation? | **Yes** — use existing `auth_domain` instead of `csrf_auth_domain` for authenticated CSRF validation; skip enforcement for guests |
| Is a governance waiver needed for cookie split? | **Not yet** — should be determined after Phase 2 telemetry data is available |
| What is the biggest unresolved risk? | **Multi-tab multi-domain CSRF oscillation** — cannot be resolved with session-scoped tags without per-domain session isolation |
