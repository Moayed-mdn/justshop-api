# Membership Semantic Governance Document

**Version:** 1.0  
**Status:** APPROVED  
**Wave:** 2  
**Date:** 2026-05-23

---

## Purpose

This document defines authoritative ownership semantics, membership lifecycle vocabulary, and compatibility-safe terminology for the current membership model. This is NOT Membership V2 implementation - it is semantic boundary clarification ONLY.

---

## Ownership Semantic Model

### Authoritative Ownership Rules

#### Rule 1: Store Owner (Primary Authority)
**Field:** `stores.owner_id`  
**Semantics:** The user who created the store and holds ultimate ownership authority  
**Capabilities:**
- Delete store
- Transfer ownership (future)
- Override all store-level permissions
- Cannot be removed from store membership

**Verification:**
```php
$isOwner = $user->id === $store->owner_id;
```

#### Rule 2: Store Membership (Operational Authority)
**Table:** `store_user` pivot  
**Semantics:** Users who have operational access to a store through explicit membership  
**Capabilities:** Determined by `role` field in pivot table

**Verification:**
```php
$isMember = $user->stores()->where('store_id', $store->id)->exists();
```

#### Rule 3: Role-Based Authority (Operational Scope)
**Field:** `store_user.role`  
**Semantics:** Defines operational scope within store membership  
**Valid Roles:**
- `store_admin` - Full operational authority within store
- `staff` - Limited operational authority within store

**Verification:**
```php
$isAdmin = $user->stores()
    ->where('store_id', $store->id)
    ->wherePivotIn('role', [StoreRoleEnum::STORE_ADMIN->value])
    ->exists();
```

#### Rule 4: Super Admin (Platform Authority)
**Field:** `users` role via Spatie  
**Semantics:** Platform-level authority that bypasses store-level restrictions  
**Capabilities:**
- Access all stores
- Manage platform-level resources
- Override store-level policies

**Verification:**
```php
$isSuperAdmin = $user->hasRole(RoleEnum::SUPER_ADMIN->value);
```

---

## Membership Lifecycle Vocabulary

### Lifecycle States

#### 1. Non-Member
**Definition:** User has no relationship with the store  
**Authorization:** No access to store resources  
**Transition To:** Member (via invitation or owner creation)

#### 2. Member (Active)
**Definition:** User has active membership in store  
**Authorization:** Access determined by role  
**Transition To:** Member (Inactive) via soft delete

#### 3. Member (Inactive)
**Definition:** User membership soft-deleted but recoverable  
**Authorization:** No access to store resources  
**Transition To:** Member (Active) via restore

#### 4. Owner
**Definition:** User is the store owner (special member state)  
**Authorization:** Full store authority  
**Transition To:** Cannot be removed (ownership transfer not yet implemented)

---

## Compatibility-Safe Terminology

### Current System Vocabulary

| Term | Meaning | Usage Context | Ambiguity Level |
|------|---------|---------------|----------------|
| **Owner** | User with `stores.owner_id` match | Store creation, deletion, ultimate authority | LOW |
| **Member** | User in `store_user` pivot | Store access, operational permissions | LOW |
| **Admin** | Member with `store_admin` role | Store management operations | MEDIUM (conflicts with super_admin) |
| **Staff** | Member with `staff` role | Limited store operations | LOW |
| **Super Admin** | Platform-level role | Platform management, bypass store restrictions | MEDIUM (naming collision with store_admin) |

### Semantic Clarifications

#### "Admin" Disambiguation
**Problem:** "admin" used for both store-level (`store_admin`) and platform-level (`super_admin`) roles  
**Current Resolution:**
- `store_admin` = Store-level operational authority
- `super_admin` = Platform-level authority
- Context determines meaning

**Future Migration Assumption:** May need explicit namespace separation (e.g., `StoreAdmin` vs `PlatformAdmin`)

#### "Membership" Scope
**Current Semantics:** Membership = presence in `store_user` pivot  
**Includes:** Owner (implicitly) + explicitly added members  
**Excludes:** Super admins (bypass membership checks)

---

## Authoritative Ownership Resolution

### Resolution Priority (Highest to Lowest)

1. **Super Admin Check** (Platform Authority)
   ```php
   if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
       return true; // Bypass all store-level checks
   }
   ```

2. **Owner Check** (Ultimate Store Authority)
   ```php
   if ($user->id === $store->owner_id) {
       return true; // Owner has full authority
   }
   ```

3. **Membership + Role Check** (Operational Authority)
   ```php
   return $user->stores()
       ->where('store_id', $store->id)
       ->wherePivotIn('role', $allowedRoles)
       ->exists();
   ```

4. **Deny** (No Authority)
   ```php
   return false;
   ```

---

## Compatibility Constraints

### Constraint 1: Owner Immutability
**Current State:** Owner cannot be changed or removed  
**Reason:** No ownership transfer mechanism exists  
**Migration Assumption:** Future ownership transfer will require:
- Explicit transfer action
- Validation of new owner membership
- Audit trail of ownership changes

### Constraint 2: Implicit Owner Membership
**Current State:** Owner is implicitly a member (may or may not have pivot entry)  
**Reason:** Historical data inconsistency  
**Migration Assumption:** Future normalization may require:
- Explicit pivot entries for all owners
- Backfill migration for historical data
- Consistent membership queries

### Constraint 3: Role Semantics Stability
**Current State:** `store_admin` and `staff` roles are stable  
**Reason:** Established in production use  
**Migration Assumption:** Future role expansion must:
- Preserve existing role semantics
- Add new roles additively
- Maintain backward compatibility

### Constraint 4: Super Admin Bypass
**Current State:** Super admin bypasses all store-level checks  
**Reason:** Platform management requirement  
**Migration Assumption:** Future changes must:
- Preserve super admin bypass capability
- Add explicit audit logging for super admin actions
- Maintain emergency access capability

---

## Future Migration Assumptions

### Assumption 1: Membership V2 Scope
**If/When Implemented:**
- Explicit invitation system
- Temporal access (expiring memberships)
- Scoped capabilities (granular permissions)
- Organization hierarchy (multi-store management)

**Compatibility Requirement:**
- Must coexist with current pivot-based membership
- Must support gradual migration
- Must preserve existing owner/member semantics

### Assumption 2: Ownership Transfer
**If/When Implemented:**
- Explicit transfer action with confirmation
- New owner must be existing member
- Audit trail required
- Rollback capability required

**Compatibility Requirement:**
- Must preserve `stores.owner_id` as single source of truth
- Must maintain owner authority semantics
- Must support emergency ownership recovery

### Assumption 3: Role Expansion
**If/When Implemented:**
- Additional operational roles (e.g., `viewer`, `editor`, `manager`)
- Role hierarchy or inheritance
- Custom role definitions

**Compatibility Requirement:**
- Must preserve `store_admin` and `staff` semantics
- Must support role migration path
- Must maintain policy compatibility

---

## Explicit NON-GOALS

### NOT Building in Wave 2

1. **Membership V2 Implementation**
   - No new tables
   - No invitation system
   - No temporal access
   - No scoped capabilities

2. **Organization Hierarchy**
   - No multi-store grouping
   - No parent/child store relationships
   - No organization-level permissions

3. **Ownership Transfer**
   - No transfer mechanism
   - No ownership history
   - No multi-owner support

4. **Advanced RBAC**
   - No custom role creation
   - No role inheritance
   - No capability composition

5. **Impersonation**
   - No admin-as-user capability
   - No session switching
   - No delegated access

---

## Policy Implementation Guidance

### Current Policy Pattern

```php
class ExamplePolicy
{
    use HasStoreMembership;

    public function before(User $user, string $ability): ?bool
    {
        // Super admin bypass (platform authority)
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }
        return null;
    }

    public function update(User $user, Store $store): bool
    {
        // Owner check (ultimate authority)
        if ($user->id === $store->owner_id) {
            return true;
        }

        // Membership + role check (operational authority)
        return $this->isAdmin($user, $store);
    }
}
```

### Semantic Consistency Rules

1. **Always check super admin first** (in `before()` method)
2. **Check owner for ultimate authority** (in specific methods)
3. **Check membership + role for operational authority** (in specific methods)
4. **Use trait helpers for consistency** (`HasStoreMembership`)
5. **Log all authorization decisions** (`InteractsWithPolicyTelemetry`)

---

## Validation & Verification

### Ownership Validation Tests

```php
// Test 1: Owner has ultimate authority
$owner = $store->owner;
$this->assertTrue($owner->id === $store->owner_id);

// Test 2: Super admin bypasses membership
$superAdmin = User::factory()->create();
$superAdmin->assignRole(RoleEnum::SUPER_ADMIN);
$this->assertTrue($superAdmin->hasRole(RoleEnum::SUPER_ADMIN->value));

// Test 3: Member has operational authority
$member = User::factory()->create();
$store->users()->attach($member->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
$this->assertTrue($member->stores()->where('store_id', $store->id)->exists());

// Test 4: Non-member has no authority
$nonMember = User::factory()->create();
$this->assertFalse($nonMember->stores()->where('store_id', $store->id)->exists());
```

---

## Governance Compliance

### ARCHITECTURE.md Compliance
- ✓ Policies remain single source of truth
- ✓ No authorization in Actions
- ✓ Explicit ownership resolution
- ✓ Tenant isolation preserved

### EXECUTION_GOVERNANCE.md Compliance
- ✓ No big-bang migrations
- ✓ Compatibility-first approach
- ✓ Explicit semantic boundaries
- ✓ Future migration assumptions documented

---

## Conclusion

This document establishes clear semantic boundaries for the current membership model without implementing Membership V2. All ownership resolution follows explicit, documented rules. Future migrations must preserve these semantics while adding new capabilities additively.

**Status:** APPROVED for Wave 2  
**Next Review:** Before Membership V2 design (Wave 5+)
