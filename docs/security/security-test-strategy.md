# Security Test Strategy

This document defines the strategy for testing tenant isolation, authority boundaries, and overall SaaS security.

---

## 1. Test Categories

### C1: Tenant Isolation (CRITICAL)
- **Objective:** Verify that data belonging to Store A is never accessible by Store B.
- **Attack Scenario:** A merchant user from Store A attempts to access an Order ID belonging to Store B via the API.
- **Expected Behavior:** `403 Forbidden` or `404 Not Found`.
- **Systems Covered:** All Admin APIs, Repositories, Policies.

### C2: Authority Domain Isolation (HIGH)
- **Objective:** Verify that actors cannot cross authority domains without governed flows.
- **Attack Scenario:** A Customer attempts to access a Merchant Admin route (`/v1/admin/*`).
- **Expected Behavior:** `403 Forbidden` (Invalid Identity Domain).
- **Systems Covered:** `ApplyIdentityRouteContext` middleware, Identity Resolvers.

### C3: Super Admin Boundary (HIGH)
- **Objective:** Verify that Super Admins cannot implicitly access merchant data.
- **Attack Scenario:** A Super Admin attempts to view a Store's Products without membership or impersonation.
- **Expected Behavior:** `403 Forbidden` (until Policy bypasses are removed).
- **Systems Covered:** Policies, Repositories.

### C4: Impersonation Governance (MEDIUM)
- **Objective:** Verify that impersonation follows the governed lifecycle.
- **Attack Scenario:** A support agent attempts to activate an expired or unapproved impersonation request.
- **Expected Behavior:** Exception or `403 Forbidden`.
- **Systems Covered:** `ImpersonationLifecycleManager`.

---

## 2. Test Execution Levels

### Level 1: Policy Unit Tests
- **Focus:** Logic inside `app/Policies`.
- **Method:** `Gate::forUser($user)->check('view', $resource)`.
- **Frequency:** CI/CD for every commit.

### Level 2: Integration Route Tests
- **Focus:** Full HTTP stack including Middleware and Authorization.
- **Method:** `$this->actingAs($user)->getJson('/api/v1/...')`.
- **Frequency:** CI/CD for every commit.

### Level 3: Cross-Tenant Matrix Tests
- **Focus:** Automated sweep of actor types against resource types.
- **Method:** Pest `dataset` combining (Actor A, Actor B) with (Store A Resource, Store B Resource).
- **Frequency:** Nightly or pre-release.

---

## 3. Regression Prevention

- **Forbidden Pattern Scans (ACTIVE):** Artisan command `architecture:detect-forbidden-patterns` scans for `before()` bypasses, unscoped queries, and direct state mutations.
- **CI/CD Integration:** Scanner and Tenant Isolation tests are integrated into the deployment pipeline to block breaking changes.
- **Telemetry Audits:** Security events (`tenant.store_mismatch`, `authorization.denied`, `queue.job.context_cleared`) are monitored in real-time.
