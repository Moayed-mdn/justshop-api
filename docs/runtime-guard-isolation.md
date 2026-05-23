# Runtime Guard Isolation

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 5

## Overview

Runtime guard isolation is the activation of explicit actor-domain guards for every request. It eliminates the shared `web` guard authority in favor of domain-bound guards.

## Guard Resolution

The **[TransitionalGuardResolver.php](file:///home/leader/projects/laravel/laratenant-backend/app/Services/Auth/TransitionalGuardResolver.php)** resolves the intended guard:

| Domain | Guard |
|--------|-------|
| Merchant | `merchant` |
| Customer | `customer` |
| Platform | `merchant` |
| Shared | `web` |

## Enforcement

When `auth.guard_split.enforce` is active, requests to non-transitional routes that resolve to the legacy `web` guard are rejected. This ensures that every authenticated action is performed under an explicit actor-bound authority.
