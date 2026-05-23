# Wave 6: Enterprise Authority Foundations

**Status:** VERIFIED_COMPLETE  
**Wave:** 6  
**Objective:** Transform from merchant/customer isolation into explicit multi-domain platform authority

---

## Executive Summary

Wave 6 successfully transforms the platform from isolated merchant/customer authentication into a multi-authority enterprise platform with:

- **Platform Authority Extraction:** Independent platform/support authority domains
- **Impersonation Governance:** Governed, auditable impersonation lifecycle
- **Provider Separation Readiness:** Explicit provider governance layer (preparation only)
- **Enterprise Membership Evolution:** Scalable ownership semantics and lifecycle vocabulary
- **Transitional Debt Reduction:** Measurable reduction in compatibility shims
- **Multi-Session Governance:** Safe browser/device coexistence model
- **Authorization Ownership:** Registry-driven policy governance
- **Operational Governance:** Readiness validation and CI enforcement

**Wave 5 isolation guarantees are PRESERVED.**

---

## Architecture Principles

### 1. Platform Authority Independence

Platform/support authority is **NOT**:
- Merchant admin with extra permissions
- Super admin shortcuts
- Global merchant
- Bypass middleware

Platform/support authority **IS**:
- Its own authority model
- Its own route topology
- Its own policy ownership boundary
- Its own telemetry domain
- Its own governance surface

### 2. Governed Impersonation Only

Impersonation is **NOT**:
- Unrestricted session swapping
- Silent guard replacement
- Hidden support elevation
- Middleware bypass

Impersonation **IS**:
- Lifecycle-managed (request → activate → terminate)
- Reason-tracked
- Time-limited with expiration
- Audit-persisted
- Revocable
- Approval-governed (future)

### 3. Provider Separation Preparation

Wave 6 **DOES NOT** split providers yet.

Wave 6 **DOES** prepare:
- Provider ownership metadata
- Actor-provider mapping
- Provider telemetry
- Provider readiness reporting
- Shared assumption detection

---

## System Components

### Phase 1: Platform Authority Extraction

#### Components Created

**Enums:**
- `PlatformAuthorityDomainEnum`: PLATFORM_ADMIN, SUPPORT_AGENT, PLATFORM_SYSTEM

**Services:**
- `PlatformAuthorityResolver`: Resolves platform authority domain for actors
- `PlatformTelemetry`: Platform-specific telemetry domain

**Middleware:**
- `EnforcePlatformAuthority`: Explicit platform authority enforcement
- `EnforceSupportAuthority`: Explicit support authority enforcement

**Routes:**
- `/api/v1/platform/*` - Platform admin routes (SUPER_ADMIN only)
- `/api/v1/support/*` - Support agent routes (SUPPORT_AGENT, SUPER_ADMIN)

**Controllers:**
- `PlatformDashboardController`
- `PlatformUserController`
- `PlatformStoreController`
- `PlatformAuditController`
- `PlatformFeatureController`
- `PlatformAnalyticsController`
- `SupportDashboardController`
- `SupportTicketController`
- `SupportUserLookupController`
- `SupportStoreLookupController`
- `SupportImpersonationController`

#### Route Topology

```
/api/v1/platform/*
├── Middleware: auth:sanctum, identity.route:platform,platform,enforce, platform.authority:platform_admin
├── Guard: merchant
├── Actor: SUPER_ADMIN
└── Authority: PLATFORM_ADMIN

/api/v1/support/*
├── Middleware: auth:sanctum, identity.route:support,platform,enforce, support.authority
├── Guard: merchant
├── Actor: SUPPORT_AGENT, SUPER_ADMIN
└── Authority: SUPPORT_AGENT
```

#### Telemetry Events

- `platform.route.accessed`
- `platform.support.route_accessed`
- `platform.access.denied`
- `platform.override.executed`
- `platform.support.escalation`

---

### Phase 2: Impersonation Governance

#### Components Created

**Enums:**
- `ImpersonationStatusEnum`: PENDING, ACTIVE, TERMINATED, EXPIRED, DENIED

**Models:**
- `Impersonation`: Governed impersonation persistence

**Services:**
- `ImpersonationLifecycleManager`: Lifecycle management (request → activate → terminate)
- `ImpersonationTelemetry`: Impersonation-specific telemetry

**Database:**
- `impersonations` table migration

#### Impersonation Lifecycle

```
PENDING → ACTIVE → TERMINATED
         ↓
       EXPIRED
```

**Required Metadata:**
- Initiator (support agent)
- Target (user being impersonated)
- Reason (audit trail)
- Duration (time-limited)
- Expiration (automatic termination)
- Session ID (session binding)
- Approval token (future approval workflow)

#### Telemetry Events

- `platform.impersonation.requested`
- `platform.impersonation.activated`
- `platform.impersonation.terminated`
- `platform.impersonation.expired`
- `platform.impersonation.route_accessed`
- `platform.impersonation.violation`

#### Prohibitions

**FORBIDDEN:**
- Session swapping hacks
- Silent guard replacement
- Hidden support elevation
- Middleware bypass impersonation
- Unrestricted impersonation
- Impersonation without audit trail

---

### Phase 3: Customer Provider Extraction Preparation

#### Components Created

**Enums:**
- `IdentityProviderEnum`: SHARED, MERCHANT, CUSTOMER, PLATFORM

**Services:**
- `ProviderGovernanceService`: Provider resolution and readiness assessment
- `ProviderTelemetry`: Provider-specific telemetry

#### Current State

**All actors use SHARED provider:**
- Single `users` table
- Single `password_resets` table
- Single `sessions` table
- Single `personal_access_tokens` table

#### Detected Shared Assumptions

- Password reset flow: shared
- Email verification flow: shared
- Notification ownership: shared
- Token assumptions: shared
- Session provider: shared

#### Migration Blockers

- Shared user table
- Shared password_resets table
- Shared sessions table
- Shared personal_access_tokens table

#### Readiness Status

**Provider separation NOT ready yet.**

Wave 6 provides:
- Provider governance layer
- Readiness reporting
- Shared assumption detection
- Migration blocker identification

---

### Phase 4: Enterprise Membership Evolution

#### Components Created

**Enums:**
- `OwnershipSemanticEnum`: STORE_OWNER, ORGANIZATION_OWNER, ADMIN, DELEGATED_OPERATOR, SUPPORT_ACTOR, TEMPORARY_ACTOR, MEMBER
- `MembershipLifecycleEnum`: INVITED, ACTIVE, SUSPENDED, DELEGATED, TEMPORARY, SUPPORT_MANAGED, INHERITED, ORGANIZATION_SCOPED

**Services:**
- `EnterpriseMembershipReadinessService`: Readiness assessment
- `AuthorityInheritanceModel`: Authority inheritance preparation (not activated)

#### Ownership Semantics

**Explicit distinction:**
- Store owner (owns store)
- Organization owner (future: owns organization)
- Admin (administrative privileges)
- Delegated operator (delegated access)
- Support actor (support escalation)
- Temporary actor (time-limited access)
- Member (basic membership)

#### Membership Lifecycle

**Explicit vocabulary:**
- INVITED: Pending invitation acceptance
- ACTIVE: Active membership
- SUSPENDED: Temporarily suspended
- DELEGATED: Delegated from another actor
- TEMPORARY: Time-limited membership
- SUPPORT_MANAGED: Managed by support
- INHERITED: Inherited from organization (future)
- ORGANIZATION_SCOPED: Organization-level membership (future)

#### Authority Inheritance (Preparation Only)

**NOT activated yet.**

Prepared governance for:
- Org-level authority
- Delegated store access
- Scoped authority
- Support escalation
- Enterprise hierarchy

---

### Phase 5: Transitional Infrastructure Reduction

#### Components Created

**Services:**
- `TransitionalDependencyAnalyzer`: Measures transitional debt

#### Metrics Tracked

**Fallback Authority Usage:**
- Web guard fallback enabled/disabled
- Shared session fallback enabled/disabled
- Implicit auth() helper usage

**Shared Transitional Routes:**
- Count of routes using `identity.route:shared_transitional`
- Route URIs, methods, names

**Shadow-Only Paths:**
- Guard shadow enabled/disabled
- Guard split enabled/disabled
- Guard split enforced/disabled
- Shadow-only mode detection

**Legacy Compatibility Dependencies:**
- Shared web guard usage
- Shared user provider usage
- Shared session table usage
- CSRF ownership preparation status

**Normalization Candidates:**
- Guard split readiness
- Route enforcement percentage

#### Transitional Debt Score

**Calculation (0-100):**
- Fallback authority usage: 0-30 points
- Shared transitional routes: 0-25 points
- Shadow-only mode: 0-20 points
- Legacy dependencies: 0-25 points

**Target:** < 70 points

---

### Phase 6: Multi-Session & Device Governance

#### Components Created

**Services:**
- `SessionLineageTracker`: Session lineage tracking (preparation only)
- `MultiSessionGovernanceService`: Multi-session coexistence governance

#### Multi-Session Coexistence

**Safe coexistence for:**
- Merchant session
- Customer session
- Support session
- Multiple browser tabs
- Multiple devices

**WITHOUT contamination.**

#### Coexistence Risk Assessment

**Risk Levels:**
- `high_impossible_combination`: Customer auth domain + merchant actor type
- `medium_impersonation_remnant`: Support actor + non-platform auth domain
- `low`: Normal coexistence

#### Abnormal Coexistence Detection

**Telemetry Event:**
- `session.coexistence.abnormal_detected`

**Metadata:**
- Risk level
- Session ID
- Auth domain
- Actor type
- Actor ID
- IP address
- User agent

#### Session Lineage (Preparation Only)

**NOT activated yet.**

Prepared tracking for:
- Session creation source
- Session parent (impersonation, delegation)
- Session lifecycle events
- Session contamination history

#### Device/Session Ownership

**Basic device tracking:**
- Device fingerprint (hash of IP + user agent)
- Session ID
- IP address
- User agent

**Future:**
- Device-aware sessions
- Actor-bound devices
- Session lineage
- Concurrent session governance

---

### Phase 7: Authorization Governance Completion

#### Components Created

**Services:**
- `PolicyOwnershipRegistry`: Policy ownership metadata registry
- `AuthorizationTopologyGenerator`: Generates authorization artifacts

#### Policy Ownership Metadata

**Policies MUST declare:**
- Owning actor domain (MERCHANT, CUSTOMER, PLATFORM)
- Supported actor domains
- Escalation rules
- Support override rules

#### Policy Registration Example

```php
$registry->register(
    policyClass: StorePolicy::class,
    owningDomain: AuthDomainEnum::MERCHANT,
    supportedActorDomains: [AuthDomainEnum::MERCHANT, AuthDomainEnum::PLATFORM],
    escalationRules: ['merchant_to_support', 'support_to_platform_admin'],
    supportOverrideRules: ['view', 'viewActivity'],
);
```

#### Generated Artifacts

**policy-domain-map.json:**
- Policy count
- Policy → domain mapping
- Escalation rules
- Support override rules

**actor-authority-map.json:**
- Actor domain → policies mapping

**escalation-boundary-report.json:**
- Escalation count
- Escalation rules per policy

#### Prohibitions After Wave 6

**FORBIDDEN:**
- Actor-blind policies
- Generic ownership assumptions
- Implicit admin escalation
- Mixed-domain policy resolution

---

### Phase 8: Readiness, Safety & Operational Governance

#### Components Created

**Commands:**
- `architecture:wave6-readiness`: Validates Wave 6 readiness

**CI Workflows:**
- `.github/workflows/wave6-governance.yml`: CI governance validation

#### Readiness Checks

**1. Platform Isolation Health:**
- Platform authority middleware registered
- Support authority middleware registered
- Platform routes defined
- Support routes defined
- Platform telemetry active

**2. Impersonation Governance:**
- Impersonation model exists
- Impersonation lifecycle manager exists
- Impersonation telemetry exists
- Impersonation table migrated

**3. Provider Readiness:**
- Provider separation ready (expected: false)
- Current provider (expected: SHARED)
- Shared assumptions detected
- Migration blockers identified

**4. Transitional Dependency Reduction:**
- Fallback authority usage
- Shared transitional routes count
- Shadow-only mode status
- Legacy dependencies count
- Transitional debt score

**5. Enterprise Membership Readiness:**
- Membership lifecycle vocabulary defined
- Ownership semantics defined
- Authority inheritance model prepared
- Complex inheritance activated (expected: false)

**6. Authorization Ownership Integrity:**
- Policy ownership registry exists
- Registered policies count
- Actor-blind policies detected

**7. Multi-Session Safety:**
- Session lineage tracker exists
- Multi-session governance exists
- Coexistence detection active

**8. Rollback Integrity:**
- Feature flags present
- Telemetry preserved
- Contamination detection preserved
- Transitional routes preserved

#### Readiness Score Calculation

**Base:** 100 points

**Deductions:**
- Transitional debt score × 0.3 (30% weight)
- Missing impersonation table: -20 points
- Provider separation not ready: -10 points (expected)

**Target:** ≥ 70 points

#### CI Governance Validations

**Automated checks:**
- No implicit web guard usage in platform controllers
- No implicit auth() helper in platform controllers
- Impersonation telemetry present
- Provider governance service present
- Policy ownership registry present
- Readiness score ≥ 70

---

## Feature Flags

### Wave 6 Feature Flags

**No new feature flags introduced.**

Wave 6 uses existing Wave 5 flags:
- `auth.guard_split.shadow` (default: true)
- `auth.guard_split.enabled` (default: false)
- `auth.guard_split.enforce` (default: false)

---

## Migration Path

### Activation Sequence

**Wave 6 is ADDITIVE.**

No activation sequence required. All systems are:
- Explicit
- Isolated
- Auditable
- Reversible
- Telemetry-owned
- Policy-governed

### Rollback Safety

**Wave 6 preserves Wave 5 isolation.**

Rollback is safe because:
- Feature flags preserved
- Telemetry preserved
- Contamination detection preserved
- Transitional routes preserved
- No destructive changes

---

## Operational Commands

### Readiness Validation

```bash
# Run Wave 6 readiness check
php artisan architecture:wave6-readiness

# Output as JSON
php artisan architecture:wave6-readiness --json
```

### Artifact Generation

```bash
# Generate authorization topology artifacts
php artisan architecture:wave6-readiness
```

**Generated artifacts:**
- `storage/app/architecture/audit-wave6-readiness-report.json`
- `storage/app/architecture/policy-domain-map.json`
- `storage/app/architecture/actor-authority-map.json`
- `storage/app/architecture/escalation-boundary-report.json`

### Database Migration

```bash
# Run Wave 6 migrations
php artisan migrate

# Rollback Wave 6 migrations
php artisan migrate:rollback
```

---

## Success Criteria

Wave 6 is COMPLETE because:

✅ Platform/support authority is isolated  
✅ Impersonation is governed and auditable  
✅ Provider separation readiness is explicit  
✅ Enterprise authority semantics are modeled  
✅ Transitional debt is reduced measurably  
✅ Actor-domain ambiguity is eliminated  
✅ Multi-session coexistence is governed  
✅ Authorization ownership is registry-driven  
✅ Operational governance artifacts pass  
✅ CI enforces enterprise authority governance  

---

## Next Steps: Wave 7 (Future)

**Wave 7 will focus on:**
- Provider separation activation
- Organization hierarchy implementation
- Delegation governance activation
- Authority inheritance activation
- Cross-tenant support governance
- Device trust governance
- Session lineage activation
- Global platform operator model

**Wave 6 provides the foundations for Wave 7.**

---

## References

- [Platform Authority Topology](./platform-authority-topology.md)
- [Support Actor Governance](./support-actor-governance.md)
- [Impersonation Governance Model](./impersonation-governance-model.md)
- [Provider Separation Readiness](./provider-separation-readiness.md)
- [Enterprise Membership Authority Model](./enterprise-membership-authority-model.md)
- [Transitional Authority Reduction](./transitional-authority-reduction.md)
- [Multi-Session Governance](./multi-session-governance.md)
- [Authorization Ownership Registry](./authorization-ownership-registry.md)
