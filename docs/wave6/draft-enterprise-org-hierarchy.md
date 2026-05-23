# DRAFT — Enterprise Organization Hierarchy

**Status: DRAFT — NOT ACTIVATED**  
**Target Wave:** Wave 8+  
**Prerequisite:** Wave 6 enterprise membership vocabulary, Wave 7 delegation governance

---

## Overview

This document describes the planned organization hierarchy model for enterprise customers. This is a DRAFT. No implementation has been started.

---

## Planned Model

### Organization

An `Organization` groups multiple `Store` instances under a single ownership entity.

```
Organization
  ├── Store A
  ├── Store B
  └── Store C
```

### Organization Membership

`organization_user` pivot table:
- `organization_id`
- `user_id`
- `role` (organization_owner, organization_admin, organization_member)
- `lifecycle_status` (MembershipLifecycleEnum)

### Authority Inheritance

Organization-level authority can be inherited by store members:
- `organization_owner` → inherits `store_owner` on all org stores
- `organization_admin` → inherits `store_admin` on all org stores
- `organization_member` → inherits `staff` on all org stores (configurable)

---

## Planned Tables

```sql
organizations (id, name, slug, owner_id, is_active, timestamps)
organization_user (id, organization_id, user_id, role, lifecycle_status, timestamps)
organization_store (id, organization_id, store_id, timestamps)
```

---

## Governance Contracts

- Organization authority MUST NOT bypass store-level policies
- Inherited authority MUST be explicitly declared in `AuthorityInheritanceModel`
- Organization membership MUST use `MembershipLifecycleEnum`
- All organization authority changes MUST emit telemetry

---

## Blockers

- `AuthorityInheritanceModel` must be activated (currently preparation only)
- `MembershipLifecycleEnum` enforcement must be active
- Policy ownership registry must support org-scoped policies
