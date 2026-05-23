# Authorization Ownership Registry

**Wave 6 — VERIFIED_COMPLETE**  
**Status:** Registry active. All known policies registered.

---

## Overview

Every policy in the platform MUST declare its ownership metadata. Actor-blind policies are forbidden after Wave 6.

---

## Registry Implementation

`App\Services\Authorization\PolicyOwnershipRegistry` — singleton, populated in `AppServiceProvider`.

Each policy registration declares:
- `owning_domain` — `AuthDomainEnum` value
- `supported_actor_domains` — array of `AuthDomainEnum` values
- `escalation_rules` — allowed escalation paths (e.g., `merchant_to_super_admin`)
- `support_override_rules` — abilities support actors can override

---

## Registered Policies

| Policy | Owning Domain | Supported Domains | Support Overrides |
|---|---|---|---|
| `StorePolicy` | `merchant` | `merchant`, `platform` | `view`, `update` |
| `OrderPolicy` | `merchant` | `merchant`, `platform` | `view` |
| `AddressPolicy` | `customer` | `customer`, `platform` | `view` |
| `BrandPolicy` | `merchant` | `merchant`, `platform` | — |
| `CategoryPolicy` | `merchant` | `merchant`, `platform` | — |
| `TagPolicy` | `merchant` | `merchant`, `platform` | — |
| `LeadPolicy` | `platform` | `platform` | `view` |
| `BlogPostPolicy` | `platform` | `platform` | `view` |
| `PaymentMethodPolicy` | `customer` | `customer`, `platform` | `view` |
| `DashboardPolicy` | `merchant` | `merchant`, `platform` | — |

---

## Escalation Rules

Escalation rules define allowed cross-actor authority paths:

| Rule | Meaning |
|---|---|
| `merchant_to_super_admin` | SUPER_ADMIN can perform merchant-domain policy actions |

---

## Topology Artifacts

`App\Services\Authorization\AuthorizationTopologyGenerator` generates:

- `storage/app/architecture/policy-domain-map.json` — all policies with ownership metadata
- `storage/app/architecture/actor-authority-map.json` — actor domains mapped to accessible policies
- `storage/app/architecture/escalation-boundary-report.json` — escalation paths per policy

---

## Forbidden After Wave 6

- Actor-blind policies (policies that don't declare ownership)
- Generic ownership assumptions
- Implicit admin escalation
- Mixed-domain policy resolution

---

## Feature Flag

`features.authorization.policy_registry.enabled` (default: `true`) enables the registry. When disabled, the registry is still populated but topology generation is skipped.
