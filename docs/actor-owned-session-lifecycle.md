# Actor-Owned Session Lifecycle

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 5

## Overview

The session lifecycle is now explicitly owned by the authenticated actor domain. Sessions are tagged during authentication and validated on every subsequent request.

## Session Tagging

**[SessionOwnershipManager.php](file:///home/leader/projects/laravel/laratenant-backend/app/Services/Auth/SessionOwnershipManager.php)** tags sessions with:
- `auth_domain`: The domain for which the session was issued.
- `actor_type`: The resolved actor context.

## Validation

The **[ApplyIdentityRouteContext.php](file:///home/leader/projects/laravel/laratenant-backend/app/Http/Middleware/ApplyIdentityRouteContext.php)** middleware enforces session ownership:
- If a session tagged as `merchant` is used on a `customer` route, the request is rejected as "contaminated."
- Mismatches are logged with high-severity telemetry.

## Logout

Logout is now guard-aware. Calling logout only terminates the session for the resolved guard, preventing global session invalidation storms in multi-actor environments.
