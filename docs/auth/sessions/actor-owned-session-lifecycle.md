# Actor-Owned Session Lifecycle

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 5

> Verified runtime note:
> Session ownership metadata is actor-aware, but logout still invalidates the whole Laravel session for compatibility.
> This document describes the current lifecycle behavior, not a future per-guard session isolation model.

## Overview

The session lifecycle is actor-aware at the metadata and enforcement layers. Sessions are tagged during authentication, validated on subsequent requests, and checked for cross-domain contamination. The underlying browser session remains globally invalidated on logout.

## Session Tagging

**[SessionOwnershipManager.php](file:///home/leader/projects/laravel/laratenant-backend/app/Services/Auth/SessionOwnershipManager.php)** tags sessions with:
- `auth_domain`: The domain for which the session was issued.
- `actor_type`: The resolved actor context.
- `actor_id`: The authenticated user ID

## Validation

The **[ApplyIdentityRouteContext.php](file:///home/leader/projects/laravel/laratenant-backend/app/Http/Middleware/ApplyIdentityRouteContext.php)** middleware enforces session ownership:
- If a session tagged as `merchant` is used on a `customer` route, the request is rejected as "contaminated."
- Mismatches are logged with high-severity telemetry.
- Guard intent is resolved per request, but route enforcement still operates inside a shared-session model.

## Logout

Logout resolves the intended guard before calling `Auth::guard($guard)->logout()`, but the cleanup step still calls `SessionOwnershipManager::invalidate()`, which **invalidates the full session and regenerates the CSRF token globally**.

Current verified behavior:

- guard intent is actor-aware
- session metadata is cleared explicitly
- the full Laravel session is invalidated globally
- logout remains globally scoped for compatibility and security
