# Ownership Architecture Review

## 1. Core Concepts

The ownership subsystem governs **who can access what** in the multi-tenant application. Every tenant-scoped operation passes through this layer.

### Key Classes

| Class | Role | File |
|-------|------|------|
| `OwnershipManager` | Top-level orchestrator | `app/Services/Ownership/OwnershipManager.php` |
| `SessionOwnershipManager` | HTTP session-based flow | `app/Services/Ownership/SessionOwnershipManager.php` |
| `OwnershipRequest` | Session-level ownership payload | `app/Models/Ownership/OwnershipRequest.php` |
| `GuardResolutionResult` | Resolved context after guard checks | `app/Models/Ownership/GuardResolutionResult.php` |
| `TenantGuard` — `BoundaryGuard` | Guard implementations | `app/Services/Ownership/TenantGuard.php` etc. |
| `TransitionalGuardResolver` | Resolves which guard applies | `app/Services/Ownership/TransitionalGuardResolver.php` |
| `GuardResolverContract` | Interface for resolvers | `app/Contracts/Ownership/GuardResolverContract.php` |
| `OwnershipGuardContract` | Interface for guards | `app/Contracts/Ownership/OwnershipGuardContract.php` |

## 2. Request Flow

```
HTTP Request
  → OwnershipMiddleware (app/Http/Middleware/OwnershipMiddleware.php)
    → OwnershipManager::resolve()
      → SessionOwnershipManager::resolve()
        → OwnershipRequest creation
        → GuardResolverContract::resolve()
          → TransitionalGuardResolver::resolve()
            → Matches TenantGuard, BoundaryGuard, etc.
            → Returns GuardResolutionResult
        → Guard applies ownership rules
  → Controller action
```

## 3. Guard Resolution Algorithm (TransitionalGuardResolver)

1. Check `auth_domain` from the resolved result
2. Map `auth_domain` to a guard class via internal mapping
3. Instantiate the matched guard
4. Call `GuardResolutionResult::isResolved()` — if false, skip
5. Guard applies its ownership logic

**Current mapping** (from `TransitionalGuardResolver`):
- `customer` → `TenantGuard`
- `merchant` → `TenantGuard`
- `admin` → `BoundaryGuard`
- Default → null (no guard applied)

## 4. Session Flow

1. `OwnershipMiddleware` captures `auth_domain` and `auth_id` from the authenticated user
2. `SessionOwnershipManager::startSession()` stores these in session
3. On subsequent requests, the session values are used to reconstruct the ownership context
4. `SessionOwnershipManager::tag()` tags the session with ownership metadata
5. On logout, `SessionOwnershipManager::clearSession()` removes ownership keys

### Session Keys Used

| Key | Purpose | Set By |
|-----|---------|--------|
| `ownership_auth_domain` | Domain of authenticated user | `startSession()` |
| `ownership_auth_id` | ID of authenticated user | `startSession()` |
| `ownership_resolved` | Whether session has been resolved | `resolve()` |

## 5. Contracts

**`OwnershipGuardContract`**:
```php
public function resolve(OwnershipRequest $request, GuardResolutionResult $result): GuardResolutionResult;
```

Currently only one implementation (`TenantGuard`) is active. `BoundaryGuard` exists but is **unused** in resolver mapping.

## 6. Observations & Risks

### 6.1 Single Active Implementation
`TransitionalGuardResolver` only maps to `TenantGuard` for all tenant domains. `BoundaryGuard` is dead code. If the intent is to differentiate guard logic per domain, the resolver mapping should be extended.

### 6.2 `GuardResolutionResult::$authDomain` Nullability
`$authDomain` is typed as `string` but can be `null` when no guard matches. This caused a runtime bug we fixed in Wave 8. The type hint should reflect `?string`.

### 6.3 Tight Coupling to Session
The entire flow depends on Laravel session. For non-HTTP contexts (queues, artisan commands, API tokens), the session may not be available, causing silent failures.

### 6.4 No Fallback Strategy
When no guard matches, the resolver returns a result with `isResolved() = false`. The middleware does not enforce how this is handled — controllers must check `$request->ownershipContext()`.

### 6.5 Minimal Test Coverage
- `OwnershipMiddlewareTest`: covers basic resolution
- `SessionOwnershipManagerTest`: covers session lifecycle
- `TransitionalGuardResolverTest`: covers guard matching
- `CsrfOwnershipPreparationControllerTest`: covers ownership preparation for CSRF
- Missing: integration tests for full request flow, edge cases with missing session, boundary guard tests

## 7. Recommendations

1. **Add `api` guard domain** for token-based authentication outside session
2. **Type-hint `$authDomain` as `?string`** (completed in Wave 8)
3. **Add fallback behavior** in middleware for unresolved ownership
4. **Increase integration test coverage** for the full resolution pipeline
5. **Revisit BoundaryGuard** — either implement or remove
