# Security Guarantees

This document defines the explicit architectural guarantees of the SaaS platform. These guarantees represent the "Absolute Laws" of the system and are used as the basis for security testing and audit.

---

## 1. Tenant Isolation Guarantees

### G1.1: Merchant Cross-Store Isolation
- **Description:** A Merchant actor authenticated in one store MUST NOT be able to access, modify, or view resources belonging to another store.
- **Affected Systems:** All Merchant Admin APIs, Repositories, Policies.
- **Enforcement Layers:** `store.context` middleware, Policy membership checks, Repository `store_id` scoping.
- **Current Status:** **Enforced** (via manual repository scoping and policies).

### G1.2: Customer Identity Isolation
- **Description:** A Customer actor MUST NOT be able to access other customers' personal data (addresses, payment methods, orders) even within the same store.
- **Affected Systems:** Storefront Account APIs, `AddressPolicy`, `OrderPolicy`.
- **Enforcement Layers:** Policy ownership checks (`$user->id === $subject->user_id`).
- **Current Status:** **Enforced**.

### G1.3: Tenant Context Integrity
- **Description:** The tenant context (`store_id`) must remain consistent throughout a single request lifecycle. It MUST NOT silently switch or be lost during complex operations.
- **Affected Systems:** `StoreContext` middleware, `RequestTraceContextManager`.
- **Enforcement Layers:** Immutable `storeId` instance in the container per request.
- **Current Status:** **Enforced**.

---

## 2. Authority Domain Guarantees

### G2.1: Platform vs Merchant Boundary
- **Description:** Platform actors (Super Admins) MUST NOT implicitly access or mutate merchant resources. Access to merchant resources MUST require explicit store membership or a governed impersonation flow.
- **Affected Systems:** `TagPolicy`, `LeadPolicy`, `StoreRepository`, `StorePolicy`.
- **Enforcement Layers:** `platform.authority` middleware, `IdentityContextResolver`, Policy membership checks (with impersonation bypass).
- **Current Status:** **Enforced** (Implicit bypasses removed; governed impersonation required).

### G2.2: Support Actor Restriction
- **Description:** Support actors are restricted to read-only operations on merchant/customer data unless they are in an active, governed impersonation session.
- **Affected Systems:** Support APIs, `EnforceSupportAuthority` middleware.
- **Enforcement Layers:** Middleware gating, read-only repository methods.
- **Current Status:** **Partially Enforced** (Impersonation governance is in place, but read-only enforcement is manual).

### G2.3: Impersonation Auditability
- **Description:** Every action performed during an impersonation session MUST be logged with both the initiator's identity and the target's identity.
- **Affected Systems:** `ImpersonationLifecycleManager`, `ImpersonationTelemetry`.
- **Enforcement Layers:** `ImpersonationTelemetry` service.
- **Current Status:** **Enforced**.

---

## 3. Background System Guarantees

### G3.1: Queue Job Isolation
- **Description:** Queue workers MUST NOT retain tenant or user state between job executions. Every job must initialize its own context and clear it upon completion.
- **Affected Systems:** All queued Jobs and Listeners.
- **Enforcement Layers:** Job constructors requiring `storeId`, manual context clearing (future).
- **Current Status:** **Not Enforced** (Relies on developer discipline; global state leakage is possible).

### G3.2: Cache Key Scoping
- **Description:** All tenant-specific data stored in shared cache MUST use a key prefixed with the `store_id`.
- **Affected Systems:** Cache services.
- **Enforcement Layers:** Manual key generation in services.
- **Current Status:** **Partially Enforced**.
