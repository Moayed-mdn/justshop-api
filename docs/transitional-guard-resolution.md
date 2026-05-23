# Transitional Guard Resolution

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 4

## Overview

The transitional guard resolution system allows the platform to resolve the intended actor-specific guard without immediately cutting over from the legacy shared authority.

## Components

- **[TransitionalGuardResolver.php](file:///home/leader/projects/laravel/laratenant-backend/app/Services/Auth/TransitionalGuardResolver.php)**: Resolves the intended guard (`merchant`, `customer`, or `web`) based on the `SessionOwnershipContext`.
- **[GuardResolutionResult.php](file:///home/leader/projects/laravel/laratenant-backend/app/DTOs/Auth/Session/GuardResolutionResult.php)**: DTO containing the resolved guard and fallback status.

## Guard Mapping

| Auth Domain | Intended Guard | Fallback Guard |
|-------------|----------------|----------------|
| `merchant`  | `merchant`     | `web`          |
| `customer`  | `customer`     | `web`          |
| `platform`  | `merchant`     | `web`          |
| `unknown`   | `web`          | `web`          |

## Telemetry

Every resolution is enriched into the `RequestTraceContext` and logged with:
- `guard_resolved`
- `is_guard_fallback`
- `intended_guard_future`
