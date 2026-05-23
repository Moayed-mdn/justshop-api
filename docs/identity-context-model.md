# Identity Context Model

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 3

## Overview

The Identity Context Model defines the authoritative actor type and domain for every request. It eliminates shared-runtime ambiguity by explicitly resolving the user's role and domain before any authorization logic is executed.

## Actor Types

| Actor Type | Auth Domain | Resolution Criteria |
|------------|-------------|---------------------|
| `super_admin` | `platform` | User has `super_admin` role |
| `merchant` | `merchant` | User has store membership OR active onboarding step |
| `customer` | `customer` | User has no store membership AND no onboarding step |

## Enforcement

- **Route Domain:** Every route family is assigned a `RouteDomainEnum`.
- **Enforcement Mode:** Routes can be in `observe` (telemetry only) or `enforce` (hard stop) mode.
- **Actor-Domain Validation:** Middleware ensures the resolved `ActorContext` matches the route's expected domain.

## Telemetry

All identity resolutions and domain mismatches are logged with:
- `actor_id`
- `actor_context`
- `route_domain`
- `enforcement_mode`
- `session_boundary`
