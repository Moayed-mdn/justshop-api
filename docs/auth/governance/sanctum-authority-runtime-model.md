# Sanctum Authority Runtime Model

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 5

> Verified runtime note:
> The current platform model is still a shared Sanctum/browser session with additive actor-domain metadata.
> The `SanctumAuthorityResolver` describes a prepared mapping layer, but it is not the sole active runtime source of authentication authority across the app.

## Overview

Sanctum currently operates in a transitional model: browser authentication remains shared, while actor-domain metadata, intended guard mapping, and route-level ownership enforcement prepare the platform for a future explicit authority split.

## Multi-Guard Resolution

Sanctum is **explicitly configured to support `merchant`, `customer`, and `web` guards** in `config/sanctum.php`.

Current code-verified behavior:

- **Stateful Auth**: the app uses Sanctum's configured session guards together with `ApplyIdentityRouteContext`, which calls `Auth::shouldUse(...)` on annotated routes.
- **Guard Mapping**: `TransitionalGuardResolver` currently maps `merchant` and `platform` to the `merchant` guard, `customer` to `customer`, and fallback to `web`.
- **Prepared Resolver**: **[SanctumAuthorityResolver.php](file:///home/leader/projects/laravel/laratenant-backend/app/Services/Auth/Sanctum/SanctumAuthorityResolver.php)** exists as a prepared mapping helper, but it is not by itself the canonical proof that request auth has fully moved into a separate Sanctum authority layer.
- **Token Auth**: bearer-token support remains available through Sanctum compatibility, but the primary browser flow is still stateful session auth.

## Current Runtime Truth

- tests and frontend session metadata still describe the authority model as `shared_sanctum_session`
- session isolation is still reported as `shared_until_guard_split`
- readiness reporting still treats full guard split as preparation rather than completed activation
- the custom Sanctum authority resolver is a prepared component, not the canonical proof that request auth has fully cut over

## SPA Compatibility

The SPA authentication flow remains unchanged for the frontend, preserving backward compatibility with the existing Next.js and Vue implementations while the backend adds observability, ownership metadata, and partial route-level hardening.
