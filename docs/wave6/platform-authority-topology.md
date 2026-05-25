# Platform Authority Topology

**Wave 6 — VERIFIED_COMPLETE**  
**Status:** Active  
**Isolation Guarantee:** Platform authority is INDEPENDENT from merchant authority.

---

## Overview

Wave 6 extracts platform/support authority into a true independent domain. Platform is NOT "merchant admin with extra permissions." It is its own authority model with its own route topology, actor ownership, middleware, and telemetry domain.

Current runtime nuance:

- platform and support routes use `AuthDomainEnum::PLATFORM`
- platform and support authority are enforced by dedicated middleware
- the current session guard still resolves to `merchant` for `platform` auth-domain requests
- authority isolation is active even though guard persistence has not split into a dedicated platform guard yet

---

## Route Topology

### Platform Domain — `/api/v1/platform/*`

| Route | Method | Actor | Authority |
|---|---|---|---|
| `/v1/platform/dashboard` | GET | SUPER_ADMIN | `platform_admin` |
| `/v1/platform/analytics` | GET | SUPER_ADMIN | `platform_admin` |
| `/v1/platform/users` | GET | SUPER_ADMIN | `platform_admin` |
| `/v1/platform/users/{user}/suspend` | PATCH | SUPER_ADMIN | `platform_admin` |
| `/v1/platform/users/{user}/activate` | PATCH | SUPER_ADMIN | `platform_admin` |
| `/v1/platform/stores` | GET | SUPER_ADMIN | `platform_admin` |
| `/v1/platform/stores/{store}/suspend` | PATCH | SUPER_ADMIN | `platform_admin` |
| `/v1/platform/stores/{store}/activate` | PATCH | SUPER_ADMIN | `platform_admin` |
| `/v1/platform/audit/logs` | GET | SUPER_ADMIN | `platform_admin` |
| `/v1/platform/features` | GET | SUPER_ADMIN | `platform_admin` |
| `/v1/platform/features/{feature}` | PATCH | SUPER_ADMIN | `platform_admin` |

**Middleware stack:**
```
auth:sanctum
identity.route:platform,platform,enforce
platform.authority:platform_admin
```

**Current guard runtime:** `merchant`

### Support Domain — `/api/v1/support/*`

| Route | Method | Actor | Authority |
|---|---|---|---|
| `/v1/support/dashboard` | GET | SUPPORT_AGENT, SUPER_ADMIN | `support_agent` |
| `/v1/support/tickets` | GET | SUPPORT_AGENT, SUPER_ADMIN | `support_agent` |
| `/v1/support/tickets/{ticket}` | GET | SUPPORT_AGENT, SUPER_ADMIN | `support_agent` |
| `/v1/support/tickets/{ticket}/assign` | PATCH | SUPPORT_AGENT, SUPER_ADMIN | `support_agent` |
| `/v1/support/tickets/{ticket}/resolve` | PATCH | SUPPORT_AGENT, SUPER_ADMIN | `support_agent` |
| `/v1/support/tickets/{ticket}/notes` | POST | SUPPORT_AGENT, SUPER_ADMIN | `support_agent` |
| `/v1/support/users/search` | GET | SUPPORT_AGENT, SUPER_ADMIN | `support_agent` |
| `/v1/support/users/{user}` | GET | SUPPORT_AGENT, SUPER_ADMIN | `support_agent` |
| `/v1/support/users/{user}/activity` | GET | SUPPORT_AGENT, SUPER_ADMIN | `support_agent` |
| `/v1/support/stores/search` | GET | SUPPORT_AGENT, SUPER_ADMIN | `support_agent` |
| `/v1/support/stores/{store}` | GET | SUPPORT_AGENT, SUPER_ADMIN | `support_agent` |
| `/v1/support/stores/{store}/activity` | GET | SUPPORT_AGENT, SUPER_ADMIN | `support_agent` |
| `/v1/support/impersonation/request` | POST | SUPPORT_AGENT, SUPER_ADMIN | `support_agent` |
| `/v1/support/impersonation/active` | GET | SUPPORT_AGENT, SUPER_ADMIN | `support_agent` |
| `/v1/support/impersonation/terminate` | DELETE | SUPPORT_AGENT, SUPER_ADMIN | `support_agent` |

**Middleware stack:**
```
auth:sanctum
identity.route:support,platform,enforce
support.authority
```

**Current guard runtime:** `merchant`

---

## Authority Boundaries

### What Platform Authority IS

- Its own route topology (`/v1/platform/*`, `/v1/support/*`)
- Its own middleware (`EnforcePlatformAuthority`, `EnforceSupportAuthority`)
- Its own actor types (`SUPER_ADMIN`, `SUPPORT_AGENT`, `PLATFORM_SYSTEM`)
- Its own telemetry domain (`PlatformTelemetry`)
- Its own `AuthDomainEnum::PLATFORM` value

### What Platform Authority IS NOT

- NOT merchant admin with extra permissions
- NOT a "super admin shortcut"
- NOT a "global merchant"
- NOT a bypass middleware
- NOT inherited from merchant authority

---

## Middleware Implementation

### `EnforcePlatformAuthority`

```
Class: App\Http\Middleware\EnforcePlatformAuthority
Alias: platform.authority
Parameter: required authority domain (e.g., platform_admin)
```

Resolution flow:
1. Authenticate user via `auth:sanctum`
2. Resolve `PlatformAuthorityDomainEnum` via `PlatformAuthorityResolver`
3. If `null` → deny with `UnauthorizedPlatformAccessException`
4. If `requiredAuthority` param set → validate against allowed authorities
5. Emit `platform.route.accessed` telemetry
6. Pass to next middleware

### `EnforceSupportAuthority`

```
Class: App\Http\Middleware\EnforceSupportAuthority
Alias: support.authority
```

Resolution flow:
1. Authenticate user via `auth:sanctum`
2. Check `PlatformAuthorityResolver::canAccessSupportRoutes()`
3. If false → deny with `UnauthorizedPlatformAccessException`
4. Emit `platform.support.route_accessed` telemetry
5. Pass to next middleware

---

## Telemetry Events

| Event | Level | Trigger |
|---|---|---|
| `platform.route.accessed` | info | Successful platform route access |
| `platform.support.route_accessed` | info | Successful support route access |
| `platform.access.denied` | warning | Platform access denied |
| `platform.override.executed` | warning | Platform override action |
| `platform.support.escalation` | warning | Support escalation event |
| `identity.platform_access.audited` | info | Platform access via identity middleware |

---

## Transitional Legacy Routes

The following routes are still transitional because they keep legacy `/v1/admin/*` URLs, even though the current route registration already applies both `identity.route:platform,platform,enforce` and explicit `platform.authority:platform_admin` middleware:

- `/v1/admin/stores/{store}/*` — merchant admin routes (legacy platform)
- `/v1/admin/leads` — lead management
- `/v1/admin/cms/*` — CMS management

**Wave 6 Goal:** These routes remain documented as transitional debt because of their URL placement and ownership history. Further migration work focuses on topology cleanup, not on adding `platform.authority` to the current route group.

---

## Rollback

Platform authority enforcement is gated by:
- `features.platform.authority.enabled` (kill switch)
- `features.platform.support.enabled` (kill switch)

Disabling either flag does NOT remove the middleware — it prevents route registration. The middleware itself is always active when routes are registered.
