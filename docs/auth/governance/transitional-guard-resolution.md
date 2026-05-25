# Transitional Guard Resolution

**Version:** 1.1  
**Status:** VERIFIED_COMPLETE  
**Wave:** 6

## Overview

The transitional guard resolver is no longer just a planning artifact.
It actively determines the intended guard for annotated routes and is consumed by `ApplyIdentityRouteContext` before controller execution.

## Components

- **[TransitionalGuardResolver.php](file:///home/leader/projects/laravel/laratenant-backend/app/Services/Auth/TransitionalGuardResolver.php)** resolves the intended guard from `SessionOwnershipContext`.
- **[ApplyIdentityRouteContext.php](file:///home/leader/projects/laravel/laratenant-backend/app/Http/Middleware/ApplyIdentityRouteContext.php)** applies `Auth::shouldUse($guard)` and rejects illegal fallback on non-transitional annotated routes.
- **[GuardResolutionResult.php](file:///home/leader/projects/laravel/laratenant-backend/app/DTOs/Auth/Session/GuardResolutionResult.php)** carries the resolved guard, fallback state, and telemetry fields.

## Guard Mapping

| Auth Domain | Intended Guard | Current Meaning |
|-------------|----------------|-----------------|
| `merchant` | `merchant` | Merchant-owned routes should execute under the merchant guard. |
| `customer` | `customer` | Customer-owned routes should execute under the customer guard. |
| `platform` | `merchant` | Platform actors still resolve through the merchant session guard path today. |
| unknown/shared transitional | `web` | Allowed only for explicitly transitional/shared flows. |

## Runtime Behavior

Current verified behavior:

- route annotations drive guard resolution
- `Auth::shouldUse($guard)` is called for annotated routes
- non-`shared_transitional` routes reject fallback resolution
- contamination and fallback findings are logged into request telemetry

This is active runtime hardening, not shadow-only analysis.

## Important Constraint

Guard resolution and route enforcement are ahead of logout/session persistence.
`LogoutUserAction` still chooses `web` when `AUTH_GUARD_SPLIT_ENABLED` is disabled, and `SessionOwnershipManager::invalidate()` still invalidates the full Laravel session.

So the current state is:

- explicit route-level guard intent: active
- explicit route-level guard application: active
- browser-session isolation: not complete
- per-domain logout isolation: not complete
