# Authority Dependency Map

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 4

## Overview

This document maps all authentication and authorization dependencies within the platform. It identifies where runtime authority is shared and where actor-specific assumptions exist.

## 1. Request Identity Assumptions

Most controllers and DTOs rely on `request->user()` or `Auth::user()` which currently resolve through the shared `web` guard.

| Area | Dependency Pattern | Actor Context Dependency |
|------|--------------------|--------------------------|
| Controllers | `$request->user()` | Implicitly shared |
| DTOs | `$request->user()->id` | Implicitly shared |
| Policies | `User $user` argument | Implicitly shared |
| Middleware | `auth:sanctum` | Shared authority |

## 2. Guard Authority

Current guard configuration is globally shared.

| Guard | Driver | Provider | Usage |
|-------|--------|----------|-------|
| `web` | `session` | `users` | Authoritative for all sessions |
| `sanctum` | `guard` | `web` | Authoritative for all API routes |

## 3. Session & Cookie Ownership

| Resource | Ownership Type | Current State |
|----------|----------------|---------------|
| Session ID | Shared | Single global session pool |
| CSRF Token | Shared | Single CSRF lifecycle |
| Auth Cookie | Shared | `ecommerce_session` for all domains |
| XSRF Cookie | Shared | `XSRF-TOKEN` for all domains |

## 4. Token Authority (Sanctum)

Sanctum is used in "Stateful" mode for the SPA.

- **Issuance**: `Auth::login()` (Session-based).
- **Validation**: `auth:sanctum` middleware (Session-based).
- **Revocation**: `Auth::guard('web')->logout()` + `session()->invalidate()`.

## 5. Social Auth Authority

`SocialAuthService` uses `Auth::login($user)`, coupling social identity to the shared `web` guard immediately upon callback.

## 6. Policy Assumptions

Policies (e.g., `StorePolicy`, `OrderPolicy`) receive a `User` model. While Wave 3 introduced actor-aware checks (`isMerchant()`, `isCustomer()`), the underlying `User` model is still retrieved from a shared authority.
