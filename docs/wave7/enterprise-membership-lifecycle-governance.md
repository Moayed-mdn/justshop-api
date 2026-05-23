# Enterprise Membership Lifecycle Governance

**Wave 7 — VERIFIED_COMPLETE**  
**Status:** Active  
**Domain:** `MERCHANT` / `CUSTOMER`

---

## Overview

Wave 7 formalizes the membership lifecycle for enterprise-scale authority. The `store_user` pivot table is now governed by explicit transition rules and lifecycle states.

---

## Membership Lifecycle States

| State | Description |
|---|---|
| `invited` | User has been invited but not yet accepted |
| `pending_activation` | User has accepted but requires platform/admin activation |
| `active` | Full active membership |
| `suspended` | Membership temporarily disabled by admin/platform |
| `revoked` | Permanent revocation of access |

---

## Transition Governance

Transitions are managed by `MembershipLifecycleManager`.

### Allowed Transitions:
- `invited` → `active`, `revoked`, `pending_activation`
- `pending_activation` → `active`, `revoked`
- `active` → `suspended`, `revoked`
- `suspended` → `active`, `revoked`

### Forbidden Transitions:
- `revoked` → ANY (Revocation is final)
- `suspended` → `pending_activation`

---

## Governance Tools

- `MembershipLifecycleManager` — Centralized transition logic and validation.
- `architecture:enterprise-membership-governance-report` — Detects stale active stores, orphaned memberships, and privilege leakage.

---

## Audit Artifacts

- `enterprise-membership-governance-report.json` — Detailed lifecycle health report.
