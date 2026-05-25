# Auth Routing Doctrine

## Purpose

This document is the canonical auth doctrine for route ownership, identity domains, guard resolution, and shared-session constraints.

Use this file for current runtime truth.
Use narrower docs under `docs/auth/` for focused references, payload contracts, governance details, and guides.

## Current Verified Runtime

The codebase currently runs in a mixed hardening state:

- authentication still enters through `auth:sanctum` and a shared browser session cookie
- the shared user provider and shared `users` table remain authoritative
- explicit session guards exist for `web`, `merchant`, and `customer`
- `identity.route` middleware resolves route ownership, identity context, session ownership, and the intended guard on annotated routes
- `ApplyIdentityRouteContext` calls `Auth::shouldUse($guard)` and rejects illegal fallback on non-transitional annotated routes
- logout is still globally scoped because `LogoutUserAction` falls back to `Auth::guard('web')->logout()` while `AUTH_GUARD_SPLIT_ENABLED` is disabled by default, and `SessionOwnershipManager::invalidate()` invalidates the full Laravel session

This means route-level guard activation is active, but cookie/session persistence and logout semantics are still shared.

## Auth Domains

| Auth Domain | Primary Actor Types | Current Runtime Notes |
|-------------|---------------------|-----------------------|
| `merchant` | `merchant` | Merchant auth and admin surfaces resolve to the `merchant` guard on annotated routes. |
| `customer` | `customer` | Customer account surface resolves to the `customer` guard on annotated routes. |
| `platform` | `super_admin`, `support_agent` | Platform routes are enforced as platform-owned, but the intended guard still resolves to `merchant`. |
| `shared_transitional` | mixed or guest | Transitional endpoints stay exempt from strict ownership and fallback rejection. |

## Route Ownership Doctrine

Current route families are owned as follows:

| Route Family | Owner Domain | Enforcement | Notes |
|--------------|--------------|-------------|-------|
| `/api/v1/users/*` | `merchant` | `enforce` | Includes merchant auth/bootstrap/profile surfaces. |
| `/api/v1/admin/stores/{store}/*` | `merchant` | `enforce` | Merchant-admin store operations remain merchant-owned. |
| `/api/v1/storefront/account/*` | `customer` | `enforce` | Dedicated customer account namespace. |
| `/api/v1/stores/{store}/*` | `customer` | `observe` | Storefront commerce remains customer-facing but still transitional in places. |
| `/api/v1/platform/*` | `platform` | `enforce` | Explicit platform authority is required. |
| legacy platform admin CMS/leads under `/api/v1/admin/*` | `platform` | `enforce` | These routes currently include both `identity.route:platform` and explicit `platform.authority:platform_admin` middleware. |
| `/api/v1/public/*` | `customer` | `observe` | Public content is guest-safe and non-authenticated. |
| `/sanctum/csrf-cookie` and other shared handoff endpoints | `shared_transitional` | `observe` | Shared browser bootstrap still exists. |

## Identity Context Doctrine

`IdentityContextResolver` is the canonical resolver for actor classification.

Supported runtime actor types are:

- `merchant`
- `customer`
- `super_admin`
- `support_agent`

The resolved identity context carries:

- `actor_type`
- `actor_id`
- `onboarding_required`
- `operational_context`
- `auth_domain`

Current guarantees:

- customer actors bypass merchant onboarding
- merchant actors retain merchant onboarding semantics
- platform actors remain outside merchant onboarding
- route-domain mismatches on enforced routes return `403`

## Session Ownership Doctrine

`SessionOwnershipManager` persists the active session domain with:

- `auth_domain`
- `actor_type`
- `actor_id`

`SessionOwnershipResolver` and `SessionBoundaryMetadataResolver` enrich each request with:

- route ownership
- session origin
- future intended guard
- authority model `shared_sanctum_session`
- isolation state `shared_until_guard_split`

This metadata is authoritative for telemetry and contamination detection, but it does not create separate browser cookies or separate persistent session stores.

## Guard Resolution Doctrine

`TransitionalGuardResolver` currently maps domains as follows:

| Session/Auth Domain | Intended Guard |
|---------------------|----------------|
| `merchant` | `merchant` |
| `customer` | `customer` |
| `platform` | `merchant` |
| unknown or shared transitional fallback | `web` |

Current runtime behavior:

- `ApplyIdentityRouteContext` resolves the intended guard on annotated routes
- `Auth::shouldUse($guard)` is applied before controller execution
- non-`shared_transitional` annotated routes reject fallback guard resolution
- shared-transitional routes remain exempt so shared bootstrap/csrf handoff behavior keeps working

This is stronger than shadow-only preparation, but it is still not a full browser-session cutover.

## Merchant Surface Doctrine

Merchant-auth authoritative routes remain under `/api/v1/users/auth/*` and `/api/v1/users/*`.

Current merchant login flow:

- `AuthController::login()` authenticates the user
- `Auth::login($user)` creates the Laravel session
- the session is regenerated
- `SessionOwnershipManager::tag(..., 'merchant')` marks the session domain

Merchant bootstrap remains the larger operational payload and still includes stores, onboarding, permissions, config, and session metadata.

## Customer Surface Doctrine

Customer account routes live under `/api/v1/storefront/account/*`.

Current customer login flow:

- `StorefrontAccountController::login()` authenticates a customer-only actor
- `Auth::login($user)` creates the Laravel session
- the session is regenerated
- `SessionOwnershipManager::tag(..., 'customer')` marks the session domain

Current customer bootstrap contract is intentionally minimal:

- `user`
- `identity_context`
- `session`
- response `meta.session` for frontend session metadata

It does not reuse the merchant bootstrap payload.

## Logout Doctrine

Current logout behavior is intentionally mixed:

- logout resolves session ownership and intended guard
- if `AUTH_GUARD_SPLIT_ENABLED` is `false` (the default), `LogoutUserAction` logs out the `web` guard for compatibility
- `SessionOwnershipManager::invalidate()` clears ownership keys and invalidates the full Laravel session
- CSRF token regeneration still happens globally

Therefore:

- logout intent is actor-aware
- route protection is actor-aware
- persistent browser logout remains globally scoped

## Browser Coexistence Doctrine

The browser still uses a shared session cookie, so mixed merchant/customer activity can still contend with the same underlying browser session.

Current known truth:

- multi-tab merchant/customer coexistence is monitored, not fully isolated
- shared-cookie overwrite risk still exists
- shared CSRF bootstrap still exists
- readiness and simulation layers are still used to evaluate future split safety

## Not Yet Completed

The following are not yet true in the live runtime:

- separate browser cookies per actor domain
- per-guard persistent session isolation
- split-safe logout that only invalidates one actor domain
- separate providers or separate account tables
- full removal of shared-session assumptions from SPA/browser flows
