# Sanctum Authority Governance

**Version:** 1.1  
**Status:** VERIFIED_COMPLETE  
**Wave:** 6

## Overview

Sanctum remains the authenticated entrypoint for SPA/session requests, but the surrounding authority model is now layered:

- `auth:sanctum` still authenticates requests against the configured session guards
- `identity.route` middleware then resolves route ownership, intended guard, and contamination state
- browser-session persistence still remains shared

## Configuration

**[sanctum.php](file:///home/leader/projects/laravel/laratenant-backend/config/sanctum.php)** is configured with these guards:

- `web`
- `merchant`
- `customer`

Related shared-session facts:

- the session cookie is still `ecommerce_session`
- stateful SPA domains remain shared across the app
- the shared `users` provider remains authoritative for all three guards

## Runtime Authority Model

Current verified runtime is a mixed model:

- Sanctum guard configuration is multi-guard capable
- route middleware applies merchant/customer intent on annotated routes
- request/session metadata still reports `shared_sanctum_session` and `shared_until_guard_split`
- shared CSRF bootstrap and shared browser cookie behavior still exist

## Role Of `SanctumAuthorityResolver`

The **[SanctumAuthorityResolver.php](file:///home/leader/projects/laravel/laratenant-backend/app/Services/Auth/Sanctum/SanctumAuthorityResolver.php)** exists in the codebase as a supporting resolver for session-domain-to-guard mapping, but it is not the primary wired entrypoint that proves request authentication has fully cut over inside Sanctum itself.

The currently verified runtime authority comes from the combination of:

- `auth:sanctum` using the configured guards in `config/sanctum.php`
- `ApplyIdentityRouteContext` calling `Auth::shouldUse(...)` on annotated routes
- `TransitionalGuardResolver` mapping `merchant` and `platform` to `merchant`, `customer` to `customer`, and fallback to `web`

## Current Constraints

The following are still true today:

- logout is globally scoped by default
- browser tabs still contend for the same underlying session cookie
- cookie isolation has not been completed
- provider/table separation has not been completed

## Governance Conclusion

The correct reading is:

- Sanctum is configured for multi-guard resolution
- route-level authority hardening is active
- shared-session browser compatibility still constrains the final authority model
