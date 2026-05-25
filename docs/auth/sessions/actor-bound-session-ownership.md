# Actor-Bound Session Ownership

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 4

## Overview

Session ownership is now explicitly tracked by tagging sessions with actor metadata during the authentication lifecycle.

## Ownership Tagging

The **[SessionOwnershipManager.php](file:///home/leader/projects/laravel/laratenant-backend/app/Services/Auth/SessionOwnershipManager.php)** service is responsible for persisting actor metadata into the session.

| Session Key | Description |
|-------------|-------------|
| `auth_domain`| The authoritative domain (`merchant`, `customer`) |
| `actor_type` | The resolved actor type |
| `actor_id`   | The unique ID of the authenticated user |

## Logout Semantics

`LogoutUserAction` uses the `SessionOwnershipManager` to perform actor-aware invalidation. While currently invalidating the global session to ensure compatibility, it prepares the platform for future concurrent session isolation.

## Contamination Detection

Telemetry now detects "session contamination" where a session tagged for one domain is used to access routes belonging to another.
