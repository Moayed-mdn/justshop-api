# Dead Code Remediation

## 1. Unused Classes

### 1.1 `BoundaryGuard`
- **File**: `app/Services/Ownership/BoundaryGuard.php`
- **Status**: Unreachable
- **Evidence**: `TransitionalGuardResolver` does not map any `auth_domain` to `BoundaryGuard`. The constructor hint suggests it was intended for non-tenant (boundary) domains like `admin`, but this was never wired.

```php
// TransitionalGuardResolver — only TenantGuard is referenced
private array $guardMap = [
    'customer' => TenantGuard::class,
    'merchant' => TenantGuard::class,
];
```

### 1.2 `BoundaryGuard` Test
- **File**: `tests/Unit/Services/Ownership/BoundaryGuardTest.php`
- **Status**: Orphaned (tests a dead class)
- **Evidence**: Test exists and references `BoundaryGuard` but it's never run in CI for actual boundary scenarios.

### 1.3 `HasTenant` Trait (Sentry)
- **File**: `app/Services/Logging/HasTenant.php`
- **Status**: Likely unused
- **Evidence**: `SentryLoggingService` does not use it. No other references found except imports in files that don't invoke its methods.
- **Recommendation**: Confirm and remove.

### 1.4 `TenantServiceProvider` — Missing Registration
- **File**: `app/Providers/TenantServiceProvider.php`
- **Status**: Registered but several bindings have no consumers at runtime in the ownership path. The `OwnershipManager` binding and `GuardResolverContract` binding are used. The `MonologLoggerProcessor` binding should be confirmed.

### 1.5 Various Factory Classes
- `app/Modules/Subscriptions/Models/SubscriptionServiceFactory.php`
- `app/Modules/*/Factories/*`
- **Status**: Some factories in the codebase appear orphaned. Needs per-file confirmation.

## 2. Unused Methods / Dead Parameters

### 2.1 `TransitionalGuardResolver::__construct` — `$tenantGuard` parameter
- `$tenantGuard` is injected but never stored as a property. The resolver instantiates guards from the class map string instead of using the injected instance.

```php
public function __construct(
    private readonly ?TenantGuard $tenantGuard = null,  // stored but never used
    private readonly ?BoundaryGuard $boundaryGuard = null, // stored but never used
)
```

### 2.2 `SessionOwnershipManager::clearSession()`
- Called from `OwnershipManager::clearSession()`. Used, but the broader `OwnershipManager::clearSession()` path is not exercised by any route.

### 2.3 `MetricService` — Stub actions
- `MetricService` contains methods like `increment()` that appear to be stubs with TODO comments. Not wired to any real metric backend.

## 3. Dead Tests

### 3.1 `BoundaryGuardTest`
- Tests a dead class. If `BoundaryGuard` is removed, this test must be removed.

### 3.2 Potentially other guard tests
- Any test referencing guard classes that are not in the resolver map should be reviewed.

## 4. Recommendation: Pruning Plan

| Item | Action | Risk | Priority |
|------|--------|------|----------|
| `BoundaryGuard` | Remove or implement wiring | Low (already dead) | Medium |
| `BoundaryGuardTest` | Remove with class | Low | Medium |
| `HasTenant` trait | Verify unused, then remove | Low | Low |
| `TransitionalGuardResolver` unused constructor params | Clean up | Low | Low |
| `MetricService` stubs | Complete or remove | Medium | Medium |
| Orphaned factories | Audit per module | Low | Low |

## 5. Caveats

- Do NOT remove code that is part of an **intentional future roadmap** (e.g., `BoundaryGuard` may be needed for admin domain isolation).
- If kept, `BoundaryGuard` should be wired to the resolver or clearly annotated with `@todo` or `@future`.
