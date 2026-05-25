# Runtime Guard Isolation

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 5

> Verified runtime note:
> The codebase resolves and applies intended guards on annotated routes, but the overall auth model is still documented and tested as `shared_sanctum_session` with `shared_until_guard_split`.
> This document describes partial runtime hardening, not a fully completed guard-split cutover.

## Overview

Runtime guard isolation is **active for all annotated routes**. Route middleware (`ApplyIdentityRouteContext`) resolves and applies explicit actor-domain guards (`merchant`, `customer`, `web`) before the controller executes. This ensures that the correct authentication guard is used for the request domain, even though the platform still retains a shared-session browser cookie.

## Guard Resolution

The **[TransitionalGuardResolver.php](file:///home/leader/projects/laravel/laratenant-backend/app/Services/Auth/TransitionalGuardResolver.php)** resolves the intended guard:

| Domain | Guard |
|--------|-------|
| Merchant | `merchant` |
| Customer | `customer` |
| Platform | `merchant` |
| Shared | `web` |

## Enforcement

Current runtime behavior combines three layers:

- `ApplyIdentityRouteContext` resolves the intended guard and calls `Auth::shouldUse($guardResolution->guard)` on annotated routes.
- Shared-transitional routes are explicitly exempt from strict ownership enforcement.
- Readiness metadata and tests still treat the platform as `shared_until_guard_split`.

## What Is Active Today

- route-level identity and ownership enforcement is active
- contamination detection is active
- explicit guard intent is resolved per request
- fallback rejection exists for non-transitional annotated routes

## What Is Not Fully Cut Over Yet

- shared Sanctum session authority remains the documented platform model
- shared browser session/cookie behavior remains authoritative
- readiness services still classify guard split as not fully activated
- simulation code still compares intended guards against legacy `web` as the current baseline
