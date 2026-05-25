# Forbidden Security Patterns

This document identifies architectural anti-patterns that violate the security principles of the SaaS platform. These patterns are forbidden and must be remediated when found.

---

## 1. Authorization Bypasses

### P1.1: Implicit Super Admin Bypass in Policies
- **Risk:** High. Allows platform admins to access merchant resources without an audit trail (impersonation). Violates authority domain separation.
- **Status:** **Remediated** (Step 2).
- **Bad Example:**
  ```php
  public function before(User $user, string $ability) {
      if ($user->hasRole('super_admin')) return true;
  }
  ```
- **Preferred Pattern:** Use the `ImpersonationGovernanceService` or explicit store membership.
  ```php
  // No before() method. Membership or Governed Impersonation is required.
  public function view(User $user, Store $store) {
      return $this->isMember($user, $store); // isMember accounts for impersonation
  }
  ```

### P1.2: Direct `hasRole` Check for Authorization
- **Risk:** Medium. Hardcodes authority logic and bypasses the Policy layer. Makes auditing and domain shifts difficult.
- **Bad Example:**
  ```php
  if (auth()->user()->hasRole('merchant')) { ... }
  ```
- **Preferred Pattern:** Use Policy-based authorization.
  ```php
  $this->authorize('view', $resource);
  ```

---

## 2. Tenant Isolation Violations

### P2.1: Unscoped Database Queries
- **Risk:** Critical. Leads to cross-tenant data leakage.
- **Bad Example:**
  ```php
  $product = Product::find($id); // Missing store_id check
  ```
- **Preferred Pattern:** Always scope by `store_id` at the repository level.
  ```php
  $product = Product::where('store_id', $storeId)->find($id);
  ```

### P2.2: Global Mutable Tenant State
- **Risk:** High. Leads to context leakage in async jobs (Queues) or long-running processes (Octane).
- **Status:** **Mitigated** (Step 3: Runtime Cleanup).
- **Bad Example:**
  ```php
  Tenant::set($storeId); // Static/Global setter
  ```
- **Preferred Pattern:** Pass the `storeId` explicitly through DTOs and Actions, and rely on `Queue::after` cleanup.
  ```php
  public function execute(UpdateProductDTO $dto) {
      $storeId = $dto->storeId;
      // ...
  }
  ```

---

## 3. Route & Identity Confusion

### P3.1: Route Model Binding without Validation
- **Risk:** High. Allows an attacker to guess IDs of resources belonging to other tenants.
- **Bad Example:**
  ```php
  Route::get('/orders/{order}', [OrderController::class, 'show']);
  ```
- **Preferred Pattern:** Ensure the bound model belongs to the active store context via middleware or Policy.
  ```php
  public function show(User $user, Order $order) {
      $this->authorize('view', $order); // Policy checks $order->store_id
  }
  ```

### P3.2: Mixed Authority Middleware
- **Risk:** Medium. Confuses the identity domain and allows platform actors to inherit merchant permissions implicitly.
- **Bad Example:**
  ```php
  Route::middleware(['auth', 'permission:manage-store'])->group(...); // Who is 'auth'?
  ```
- **Preferred Pattern:** Use domain-specific middleware.
  ```php
  Route::middleware(['auth:sanctum', 'identity.route:merchant_admin'])->group(...);
  ```
