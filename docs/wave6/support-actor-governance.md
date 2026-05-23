# Support Actor Governance

**Wave 6 — VERIFIED_COMPLETE**  
**Status:** Active  
**Domain:** `AuthDomainEnum::PLATFORM` / `PlatformAuthorityDomainEnum::SUPPORT_AGENT`

---

## Overview

Support actors are a **subset** of platform authority. They have limited, read-oriented access to platform data and governed impersonation capability. They do NOT inherit merchant authority. They do NOT share ownership assumptions with merchant actors.

---

## Actor Definition

| Property | Value |
|---|---|
| `ActorContextEnum` | `SUPPORT_AGENT` |
| `AuthDomainEnum` | `PLATFORM` |
| `PlatformAuthorityDomainEnum` | `SUPPORT_AGENT` |
| `RoleEnum` | `support` |
| `OperationalContextEnum` | `PLATFORM_ADMIN` |

---

## Identity Resolution

`IdentityContextResolver` resolves support actors via:

```php
if ($user->hasRole(RoleEnum::SUPPORT->value)) {
    return new IdentityContext(
        actorType: ActorContextEnum::SUPPORT_AGENT,
        actorId: (int) $user->id,
        onboardingRequired: false,
        operationalContext: OperationalContextEnum::PLATFORM_ADMIN,
        authDomain: AuthDomainEnum::PLATFORM,
    );
}
```

Support actors are resolved **before** merchant candidates. This prevents a support user with a store membership from being misclassified as a merchant.

---

## Authority Boundaries

### Support actors CAN:

- Access `/v1/support/*` routes
- Read user profiles and activity (read-only)
- Read store profiles and activity (read-only)
- View and manage support tickets
- Request governed impersonation (when enabled)
- Access support dashboard

### Support actors CANNOT:

- Access `/v1/platform/*` routes (platform_admin only)
- Modify user accounts directly
- Modify store configurations directly
- Access merchant admin routes (`/v1/admin/*`)
- Access customer storefront routes
- Perform impersonation without audit trail
- Escalate to platform_admin authority

---

## Route Ownership

Support routes are owned by `AuthDomainEnum::PLATFORM` with `RouteDomainEnum::SUPPORT`.

The `ApplyIdentityRouteContext` middleware enforces:
```
allowedActorTypes for PLATFORM domain: [SUPER_ADMIN, SUPPORT_AGENT]
```

---

## Impersonation Governance

Support actors may request impersonation via `POST /v1/support/impersonation/request`.

All impersonation is:
- **Governed** — requires reason, has expiration
- **Audited** — every action emits telemetry
- **Reversible** — can be terminated at any time
- **Persisted** — stored in `impersonations` table

See `impersonation-governance-model.md` for full lifecycle.

---

## Telemetry

All support route access emits `platform.support.route_accessed` with:
- `actor_type`: `support_agent`
- `actor_id`: user ID
- `route`: accessed path

Support escalations emit `platform.support.escalation` with escalation type and metadata.

---

## Governance Enforcement

`EnforceSupportAuthority` middleware validates:
1. User is authenticated
2. `PlatformAuthorityResolver::canAccessSupportRoutes()` returns true
3. Emits telemetry on both success and denial

Denial emits `platform.access.denied` with reason `not_support_actor`.
