# Transitional Security Debt

This document tracks known architectural security risks and "debt" items that exist due to the transitional state of the SaaS platform.

---

## 1. Authority Domain Debt

### D1.1: Policy `before()` Bypasses
- **Origin:** Early development phase where Super Admin was a "Global God" actor.
- **Current Risk:** Low. Implicit bypasses removed in Step 2.
- **Migration Difficulty:** Complete.
- **Status:** **Remediated**.

### D1.2: Legacy Admin Route Enforcement
- **Origin:** Routes created before the `platform.authority` middleware was introduced.
- **Current Risk:** Low. Migrated to explicit `platform.authority` in Step 2.
- **Migration Difficulty:** Complete.
- **Status:** **Remediated**.

---

## 2. Identity & Guard Debt

### D2.1: Shared User Provider
- **Origin:** Laravel default configuration.
- **Current Risk:** Medium. All guards (merchant, customer) use the same `users` table and provider, increasing the risk of credential leakage across domains.
- **Migration Difficulty:** High. Requires splitting the database or creating domain-specific providers.
- **Status:** **Future Risk**.

### D2.2: Guard Split Not Enforced
- **Origin:** Wave 5 transitional period.
- **Current Risk:** Low. Enforced in Step 3.
- **Migration Difficulty:** Complete.
- **Status:** **Remediated**.

---

## 3. Tenant Isolation Debt

### D3.1: Manual Repository Scoping
- **Origin:** Architectural decision to avoid global scopes for performance/flexibility.
- **Current Risk:** Medium. Relies on developers to always add `->where('store_id', ...)`.
- **Migration Difficulty:** High. Requires a fundamental change to the repository pattern or implementation of Global Scopes.
- **Status:** **Future Risk** (Maintenance burden).

### D3.2: Static Store Context
- **Origin:** Usage of `app('currentStore')` singleton.
- **Current Risk:** Low. Context is now explicitly cleared in `Queue::after` and monitored with telemetry in Step 4.
- **Migration Difficulty:** Medium. Requires refactoring to explicit context passing.
- **Status:** **Mitigated & Monitored**.
