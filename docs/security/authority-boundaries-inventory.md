# Architectural Security Inventory: Authority Boundaries & Tenant Isolation

**Version:** 1.1  
**Status:** COMPLETE  
**Date:** 2026-05-25  
**Domain:** SaaS Security Architecture

---

## SECTION 1 — AUTHORITY DOMAINS

The system is partitioned into four primary authority domains, resolved dynamically via the `IdentityContextResolver`.

### 1. Platform Domain
- **Purpose:** Global administration and maintenance of the entire SaaS infrastructure.
- **Allowed Resource Scope:** Global analytics, user/store suspension, platform feature flags, platform-level CMS (blog, docs).
- **Intended Boundaries:** Strictly isolated from merchant/customer transactional data.
- **Middleware Stack:** `auth:sanctum` → `identity.route:platform` → `platform.authority:platform_admin`.
- **Guards Used:** `merchant` (shared provider).
- **Identity Resolution:** Resolved via `RoleEnum::SUPER_ADMIN`.
- **Scoping:** Platform-scoped (Global).

### 2. Support Domain
- **Purpose:** Limited operational support for merchants and customers.
- **Allowed Resource Scope:** Read-only access to user/store profiles, ticket management, governed impersonation.
- **Intended Boundaries:** Subset of Platform authority; cannot modify merchant data without impersonation.
- **Middleware Stack:** `auth:sanctum` → `identity.route:support` → `support.authority`.
- **Guards Used:** `merchant` (shared provider).
- **Identity Resolution:** Resolved via `RoleEnum::SUPPORT`.
- **Scoping:** Platform-scoped (Restricted).

### 3. Merchant Domain
- **Purpose:** Store-level administration and commerce management.
- **Allowed Resource Scope:** Products, Orders, Store Settings, Memberships for specific `store_id`.
- **Intended Boundaries:** Strictly tenant-isolated. One merchant MUST NOT access another store's resources.
- **Middleware Stack:** `auth:sanctum` → `identity.route:merchant_admin` → `store.context` → `onboarding.completed`.
- **Guards Used:** `merchant`.
- **Identity Resolution:** Resolved via existence of store membership or onboarding progress.
- **Scoping:** Tenant-scoped (`store_id`).

### 4. Customer Domain
- **Purpose:** Storefront commerce interaction (shopping, checkout, account management).
- **Allowed Resource Scope:** Personal profile, own orders, own addresses, own cart within a store context.
- **Intended Boundaries:** Strictly identity-isolated. One customer MUST NOT access another customer's data.
- **Middleware Stack:** `identity.route:storefront_commerce` (Observe mode).
- **Guards Used:** `customer` (Planned/Transitional).
- **Identity Resolution:** Default resolution path if not Platform or Merchant.
- **Scoping:** Identity-scoped + Tenant-scoped.

---

## SECTION 2 — ROUTE ARCHITECTURE

| Route Prefix | Middleware Chain | Authority Domain | Tenant Source | Impersonation |
| :--- | :--- | :--- | :--- | :--- |
| `/v1/platform` | `auth:sanctum`, `identity.route:platform`, `platform.authority` | Platform | N/A | No |
| `/v1/support` | `auth:sanctum`, `identity.route:support`, `support.authority` | Support | N/A | Entry Point |
| `/v1/admin/stores/{store}` | `auth:sanctum`, `verified`, `onboarding.completed`, `store.context` | Merchant Admin | Route Param | Yes (via Target) |
| `/v1/me` | `auth:sanctum`, `identity.route:merchant_users` | Merchant/Platform | `last_active_store_id` | Yes |
| `/v1/stores/*` | `identity.route:storefront_commerce` | Customer | Route Param/Header | No |
| `/v1/admin/leads` | `identity.route:platform` (Legacy) | Platform | N/A | No |
| `/v1/admin/cms/*` | `identity.route:platform` (Legacy) | Platform | N/A | No |

**Inconsistencies & Risks:**
- **Legacy Platform Routes:** `/v1/admin/leads` and `/v1/admin/cms/*` lack explicit `platform.authority` middleware, relying on legacy `identity.route` enforcement.
- **Mixed Identity Domains:** `/v1/me` serves as a bootstrap for all actors, creating a shared surface that must handle Platform, Merchant, and Customer logic simultaneously.

---

## SECTION 3 — POLICY INVENTORY

| Policy | Resource | Tenant Scoped | before() | super_admin bypass | Membership Required | Risk Level |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `StorePolicy` | `Store` | Yes | No | Yes (Implicit) | Yes | MEDIUM |
| `ProductPolicy` | `Product` | Yes | No | No (Explicit) | Yes | LOW |
| `OrderPolicy` | `Order` | Yes | No | No (Explicit) | Yes | LOW |
| `TagPolicy` | `Tag` | Yes/Global | Yes | Yes (Explicit) | No | **HIGH** |
| `LeadPolicy` | `Lead` | No | Yes | Yes (Explicit) | No | **HIGH** |
| `BlogPostPolicy` | `BlogPost` | No | No | No | No (Permissions) | LOW |
| `AddressPolicy` | `Address` | No | No | No | No (Ownership) | LOW |
| `StoreMarketingPagePolicy`| `MarketingPage` | Yes | No | No | Yes | LOW |

**Inconsistency Highlights:**
- **Bypass Fragmentation:** `TagPolicy` and `LeadPolicy` use `before()` for `super_admin` bypass, while `ProductPolicy` and `OrderPolicy` do not, creating inconsistent authority for platform admins.
- **Membership vs Permissions:** `BlogPostPolicy` relies entirely on permissions, while `StorePolicy` requires explicit membership.

---

## SECTION 4 — SUPER ADMIN CAPABILITIES

| Usage | Capability Granted | Tenant Bypass | Policy Bypass | Middleware Bypass |
| :--- | :--- | :--- | :--- | :--- |
| `IdentityContextResolver` | Grants `PLATFORM_ADMIN` context. | N/A | N/A | Yes (via `matchesOwnership`) |
| `ApplyIdentityRouteContext` | Allows access to both Platform and Merchant domains. | Yes | No | Yes |
| `TagPolicy::before` | Grants all abilities on all Tags. | **YES** | **YES** | N/A |
| `LeadPolicy::before` | Grants all abilities on all Leads. | **YES** | **YES** | N/A |
| `StoreRepository` | `getAccessibleStores` returns ALL active stores. | **YES** | N/A | N/A |
| `OnboardingApplicability` | Bypasses onboarding requirements. | N/A | N/A | Yes |

**Authority Separation Violations:**
- `super_admin` bypasses tenant isolation at the Repository level (`StoreRepository`) and Policy level (`TagPolicy`), violating the principle that platform admins should not implicitly access merchant resources.

---

## SECTION 5 — TENANT ISOLATION MECHANISMS

| Mechanism | Application | Enforcement Type | Risk |
| :--- | :--- | :--- | :--- |
| `store_id` filter | Repository queries | Manual/Explicit | High (Missing in new queries) |
| `StoreContext` | Middleware for `/v1/admin/stores/{store}` | Centralized | Low |
| `currentStore` singleton | Application-wide context | State-based | Medium (Context leakage in jobs) |
| `HasStoreMembership` | Policies (`isMember`, `isAdmin`) | Trait-based | Medium (Inconsistent usage) |
| `identity.route` | Domain separation at route level | Middleware | Low |
| Route Model Binding | Automatic model resolution | Framework-based | High (Needs manual scoping check) |

---

## SECTION 6 — IMPERSONATION ARCHITECTURE

- **Entry Point:** `POST /v1/support/impersonation/request` (Support Domain).
- **Lifecycle Manager:** `ImpersonationLifecycleManager` (Governed: Pending → Active → Terminated/Expired).
- **Session Handling:** `session_id` recorded in `impersonations` table; correlation ID tagged in session.
- **Actor Switching:** Initiator retains identity but operates on target's resources via `impersonation_id` in session.
- **Audit Logging:** Exhaustive telemetry in `ImpersonationTelemetry`.
- **Termination:** Manual via `DELETE /v1/support/impersonation/terminate` or automatic via `expires_at`.

**Architecture Boundaries:**
- **Domain Shift:** Moves Support Actor from `PLATFORM` domain to `MERCHANT` domain operations.
- **Leakage Risk:** Original `super_admin` or `support` authority may leak if policies check `hasRole` instead of actor context.
- **Nested Impersonation:** Explicitly forbidden in `ImpersonationGovernanceService`.

---

## SECTION 7 — CURRENT VS TARGET SECURITY MODEL

### CURRENT MODEL: Mixed Legacy + Modern Authority
- **Authority:** Fragmented between implicit role checks and modern domain-aware policies.
- **Isolation:** Relies on developer discipline for `store_id` scoping in repositories.
- **Platform Access:** `super_admin` retains implicit bypasses in several key policies.
- **Middleware:** Dual enforcement using legacy `identity.route` and modern `platform.authority`.

### TARGET MODEL: Explicit Domain-Driven Security
- **Authority:** 100% Policy-driven with zero `before()` bypasses.
- **Isolation:** Enforced via Global Scopes or mandated Repository wrappers.
- **Platform Access:** Zero implicit access to merchant resources; 100% governed via Impersonation.
- **Middleware:** Unified authority enforcement per route domain.

### MIGRATION DIRECTION: Hardening Phase
- **Step 1:** Freeze behavior and document guarantees (Complete).
- **Step 2:** Normalize all Policies to remove `before()` bypasses (Complete).
- **Step 3:** Enforce strict guard separation and runtime hardening (Complete).
- **Step 4:** Implement automated CI/CD security scanning.

---

## SECTION 8 — CROSS-TENANT ACCESS MATRIX

| Actor Type | Platform Resources | Merchant Resources | Customer Resources | Cross-Tenant Risk |
| :--- | :--- | :--- | :--- | :--- |
| `super_admin` | Full Access | **Governed Only** (via Impersonation) | Read-Only (Support) | **LOW** |
| `support` | Restricted (Read) | Read-Only (unless Impersonating) | Read-Only | LOW |
| `merchant_admin` | Denied | **Strictly Scoped** | Denied | LOW |
| `customer` | Denied | Denied | **Strictly Scoped** | LOW |
| `impersonated` | N/A | Governed & Audited | Governed & Audited | **LOW** (Step 3) |

---

## SECTION 9 — TOP ARCHITECTURAL RISKS

1. **Manual Scoping:** Reliance on manual `where('store_id', ...)` in Repositories instead of Global Scopes (Exploitability: Medium).
2. **Mixed Bootstrap:** `/v1/me` handling all actor types in a single controller (Impact: Medium).
3. **Shared User Provider:** All guards use the same `users` table (Future Risk).
4. **Onboarding Context:** Potential for incomplete onboarding states to allow partial resource access (Impact: Low).
5. **Telemetry Noise:** High volume of security events may mask real attacks (Operational Risk).

---

## SECTION 10 — SECURITY MATURITY ASSESSMENT

- **Current Maturity:** **Level 3 (Managed)**. Boundaries are defined and monitored, but not yet fully hardened.
- **Biggest Strength:** The `IdentityContext` and `RouteDomain` model provides a solid mathematical foundation for domain separation.
- **Biggest Weakness:** Inconsistent "shortcuts" for `super_admin` that bypass the very boundaries the architecture was built to enforce.
- **Transitional Debt:** Legacy admin routes and the shared user provider across all guards.
- **Next Strategy:** Policy Normalization — remove all `before()` bypasses and move `super_admin` access to the governed Impersonation layer.

---
**End of Inventory Report**
