# Enterprise Membership Authority Model

**Wave 6 — VERIFIED_COMPLETE**  
**Status:** Vocabulary defined. Complex inheritance NOT activated.

---

## Overview

The legacy `store_user.role` model (values: `store_admin`, `staff`) is insufficient for enterprise-scale authority semantics. Wave 6 prepares the vocabulary and governance contracts without activating complex inheritance.

---

## Membership Lifecycle Vocabulary

`App\Enums\Enterprise\MembershipLifecycleEnum`

| Value | Description |
|---|---|
| `invited` | User has been invited but not yet accepted |
| `active` | Full active membership |
| `suspended` | Membership suspended by admin or platform |
| `delegated` | Access delegated from another actor |
| `temporary` | Time-limited access |
| `support_managed` | Membership managed by support actor |
| `inherited` | Authority inherited from organization (future) |
| `organization_scoped` | Scoped to organization level (future) |

The `store_user` table now has a `lifecycle_status` column (default: `active`) to support this vocabulary.

---

## Ownership Semantics

`App\Enums\Enterprise\OwnershipSemanticEnum`

| Value | Description |
|---|---|
| `store_owner` | Direct store owner (via `stores.owner_id`) |
| `organization_owner` | Organization-level owner (future) |
| `admin` | Store admin via `store_user.role = store_admin` |
| `delegated_operator` | Delegated access (future) |
| `support_actor` | Support agent with impersonation access |
| `temporary_actor` | Time-limited access |
| `member` | Standard staff member |

---

## Authority Inheritance Model

`App\Services\Enterprise\AuthorityInheritanceModel`

Current behavior (Wave 6):
- `resolveOwnershipSemantic(user, store)` → resolves from `store_user.role`
- `canInheritAuthority(user, store)` → always `false` (no inheritance yet)
- `getInheritedAuthority(user, store)` → always `null`
- `isDelegatedAccess(user, store)` → always `false`
- `isSupportEscalation(user, store)` → checks if actor is `support_agent`
- `getAuthorityScope(user, store)` → always `store_scoped`

Future behavior (Wave 7+):
- Org-level authority resolution
- Delegated store access
- Inherited authority from organization hierarchy
- Support escalation with explicit scope

---

## Database Schema Changes

**`store_user` table additions (Wave 6):**

| Column | Type | Default | Description |
|---|---|---|---|
| `lifecycle_status` | string | `active` | `MembershipLifecycleEnum` value |
| `lifecycle_changed_at` | timestamp | null | When lifecycle last changed |
| `lifecycle_changed_by_actor_type` | string | null | `ActorContextEnum` of changer |
| `lifecycle_changed_by_actor_id` | bigint | null | ID of actor who changed lifecycle |

---

## Enterprise Readiness Report

`App\Services\Enterprise\EnterpriseMembershipReadinessService::getReadinessReport()` returns:

```json
{
  "membership_lifecycle_vocabulary_defined": true,
  "ownership_semantics_defined": true,
  "authority_inheritance_model_prepared": true,
  "complex_inheritance_activated": false,
  "organization_hierarchy_activated": false,
  "delegation_governance_activated": false,
  "support_escalation_governance_activated": false,
  "blockers": {
    "organization_model_not_created": true,
    "organization_membership_table_not_created": true,
    "delegation_governance_not_implemented": true,
    "inherited_authority_resolution_not_implemented": true
  }
}
```

---

## Feature Flags

| Flag | Default | Description |
|---|---|---|
| `enterprise.membership.lifecycle.enabled` | `false` | Enable lifecycle vocabulary enforcement |
| `enterprise.authority.inheritance.enabled` | `false` | Enable authority inheritance (future) |

---

## What Is NOT Activated

- Organization model and table
- Organization membership table
- Delegation governance
- Inherited authority resolution
- Enterprise hierarchy

These are documented in `draft-enterprise-org-hierarchy.md`.
