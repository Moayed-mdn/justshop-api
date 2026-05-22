# Wave 3A Auth & Routing Doctrine

## Purpose

This document records the **Wave 3A identity context normalization** rules.
It is a boundary-clarification phase only.

Wave 3A does **not** change runtime auth authority.

Still authoritative:

- shared `users` table
- shared Sanctum session model
- merchant auth routes under `/api/v1/users/*`
- existing merchant bootstrap contract
- existing checkout auth model

Still forbidden in Wave 3A:

- guard split
- cookie split
- customer-only sessions
- merchant-only sessions
- `customer_accounts` table
- `merchant_accounts` table
- checkout auth rewrite
- bootstrap slimming

## Identity-Context Doctrine

Identity is now normalized through `App\Services\Auth\IdentityContextResolver` and represented by `App\DTOs\Auth\Identity\IdentityContext`.

The normalized context carries:

- `actor_type`
- `actor_id`
- `onboarding_required`
- `operational_context`
- `auth_domain`

Supported explicit actor classes:

- `merchant`
- `customer`
- `super_admin`

Wave 3A intent:

- actor boundaries are explicit and observable
- onboarding applicability is explicit instead of indirectly inferred
- session-boundary preparation metadata is available without changing sessions

## Route-Domain Ownership Doctrine

Wave 3A introduces explicit ownership metadata through `identity.route` middleware.

### Merchant-owned domains

- `/api/v1/users/*`
- `/api/v1/admin/*`

### Customer-owned domain

- `/api/v1/storefront/account/*`

### Enforcement model

- merchant `/api/v1/users/*` routes are annotated in **observe** mode because they remain authoritative and backward-compatible
- merchant `/api/v1/admin/*` routes are annotated in **enforce** mode
- customer `/api/v1/storefront/account/*` routes are annotated in **enforce** mode

Route ownership is additive metadata. It exists to support telemetry, boundary checks, and future guard preparation.

## Onboarding Isolation Doctrine

Merchant onboarding semantics are now isolated through `App\Services\Auth\OnboardingApplicabilityResolver`.

Rules:

- customer actors explicitly bypass onboarding
- super-admin actors explicitly bypass onboarding
- merchant actors are explicitly evaluated
- onboarding remains merchant-only
- onboarding state machine is unchanged in Wave 3A

## Storefront Identity Doctrine

Wave 3A introduces an additive customer account namespace:

- `POST /api/v1/storefront/account/register`
- `POST /api/v1/storefront/account/login`
- `POST /api/v1/storefront/account/logout`
- `GET /api/v1/storefront/account/me`
- `GET /api/v1/storefront/account/bootstrap`

Characteristics:

- shared `users` table for now
- same session/guard topology for now
- no merchant bootstrap reuse
- no onboarding payload
- no store-management payload
- no merchant operational state

The storefront bootstrap is intentionally minimal and customer-safe.

## Session Ownership Doctrine

Wave 3B extends Wave 3A session preparation with explicit request-scoped ownership modeling through `App\Services\Auth\SessionOwnershipResolver` and `App\DTOs\Auth\Session\SessionOwnershipContext`.

Tracked fields:

- `auth_domain`
- `actor_type`
- `route_domain`
- `session_origin`
- `intended_guard_future`
- `onboarding_applicable`

This model is:

- request-scoped only
- telemetry-safe only
- non-authoritative
- not a session isolation mechanism

## Future Guard Topology

Wave 3B introduces shadow-only future guard hints.

Current future hints:

- `merchant_guard`
- `customer_guard`
- `ambiguous_guard`
- `shared_guard`

These are not active guards. They are preparatory signals for future cutover analysis.

## Guard Shadow Semantics

Wave 3B adds:

- `App\Services\Auth\MerchantGuardShadowResolver`
- `App\Services\Auth\CustomerGuardShadowResolver`
- `App\Services\Auth\GuardShadowAnalyzer`

Rules:

- shadow resolvers are observe-only
- shadow resolvers do not authenticate requests
- shadow resolvers do not change sessions
- shadow resolvers do not change cookies
- shadow resolvers only answer which guard **would** own the request in a future topology

## Contamination Detection Doctrine

Wave 3B adds contamination telemetry for:

- cross-domain session usage
- bootstrap misuse across domains
- onboarding leakage into customer-owned domains
- logout ambiguity
- guard ambiguity
- guard mismatch anomalies

These signals are for readiness evidence only. They do not enforce guard separation.

## Session Boundary Preparation Metadata

Wave 3A adds non-authoritative session annotations through `App\Services\Auth\SessionBoundaryMetadataResolver`.

Current metadata includes:

- `session_id`
- `auth_domain`
- `actor_type`
- `actor_id`
- `authority_model` = `shared_sanctum_session`
- `isolation_state` = `shared_until_guard_split`
- `ownership_key`

This is preparation metadata only. It does not introduce isolation.

## Guard Split Readiness Doctrine

Wave 3C introduces non-authoritative readiness validation for future guard split work.

Rules:

- simulation may model future split ownership
- simulation may not activate real guards
- simulation may not activate isolated session cookies
- simulation output is evidence only, never runtime authority

## Concurrent Session Doctrine

Wave 3C validates future concurrent-session behavior through simulation only.

Target scenarios include:

- merchant and storefront tabs open simultaneously
- merchant login while storefront is authenticated
- storefront login while merchant is authenticated
- logout in one context while the other should remain active
- csrf refresh during mixed-context usage

Current doctrine: all such scenarios remain shared-session behavior in production, and readiness analysis exists only to score the future split blast radius.

## CSRF Ownership Doctrine

Wave 3C validates CSRF ownership readiness by domain.

Rules:

- shared csrf behavior remains authoritative
- ownership validation may add telemetry and headers only
- no csrf lifecycle changes are allowed in Wave 3C
- simulated split scenarios may identify stale-refresh or mismatch risks

## Logout Ownership Doctrine

Wave 3C validates logout semantics for future split-safe invalidation.

Rules:

- current logout behavior remains globally shared
- simulation may model future merchant-only or customer-only invalidation scopes
- runtime logout behavior must not change in Wave 3C

## Split-Readiness Scoring Doctrine

Wave 3C produces formal readiness scoring with explicit status classification.

Allowed statuses:

- `READY`
- `PARTIALLY_READY`
- `BLOCKED`

Scoring dimensions:

- guard split readiness
- csrf isolation readiness
- logout isolation readiness
- frontend readiness
- session contamination score
- auth-domain stability score

## Readiness Reporting

Wave 3A readiness evidence is generated with:

```bash
php artisan architecture:wave3a-readiness-report --output=storage/app/testing/wave3a-readiness-report.json
```

Wave 3B readiness evidence is generated with:

```bash
php artisan architecture:wave3b-guard-readiness-report --output=storage/app/testing/wave3b-guard-readiness-report.json
```

Current expected readiness outcome:

- identity context health should be healthy
- onboarding isolation health should be healthy
- route-domain metadata coverage should be healthy
- guard split gate should remain in preparation-only status

## Remaining Wave 4 Blockers

Wave 3A does **not** remove the following blockers:

- shared user authority
- shared Sanctum authority
- shared session ownership
- no cookie split
- no guard split
- merchant auth route authority still active
- checkout auth model still shared
