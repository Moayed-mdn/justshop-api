# Execution Governance Program

## Purpose

This document converts the platform evolution roadmap into a safe execution program.
It is operational guidance only. It does not authorize implementation shortcuts.

`ARCHITECTURE.md` remains the highest authority for architecture intent, tenant isolation,
authorization boundaries, route topology, and layering doctrine.

When roadmap execution guidance conflicts with local implementation habits, the following
precedence order applies:

1. `docs/ARCHITECTURE.md`
2. Approved ADRs created for the migration
3. This execution governance program
4. Local implementation convenience

## Governance Baseline

### Non-Negotiable Safety Rules

- No big-bang migrations.
- No destructive schema, contract, or auth cutover without a proven rollback path.
- No authority shift without parity telemetry.
- No tenant-affecting rollout without tenant isolation verification.
- No behavior hidden behind undocumented flags.
- No removal of compatibility paths before minimum support windows are met.
- No async activation before observability, replay safety, and idempotency evidence exist.
- No policy or permission refactor that introduces authorization logic into Actions.

### Approval Model

Every migration phase requires explicit sign-off from:

- Architecture Owner: confirms `ARCHITECTURE.md` compliance.
- Domain Owner: confirms business correctness and boundary ownership.
- Operations Owner: confirms telemetry, alerting, rollback readiness, and release plan.
- Frontend Owner: required when contracts, bootstrap payloads, sessions, or guard behavior affect clients.
- Security Owner: required for auth, guard, permission, session, and tenant-isolation changes.

Approval is granted only when entry criteria, test evidence, telemetry readiness, and rollback
artifacts are attached to the release record.

## 1. Execution Governance Principles

### 1.1 No Big-Bang Migrations

- Rule: all roadmap changes land as additive, reversible, slice-based migrations.
- Why: reduces blast radius, keeps tenant failures localizable, and preserves rollback options.

### 1.2 Feature Flags First

- Rule: new runtime paths must be introduced behind explicit flags before becoming default.
- Why: separates deployment from activation and allows safe containment when drift appears.

### 1.3 Compatibility Before Enforcement

- Rule: new contracts must coexist with legacy behavior for a defined compatibility window.
- Why: avoids frontend breakage, tenant-specific regressions, and cutover deadlocks.

### 1.4 Parity Telemetry Before Cutover

- Rule: old and new authority paths must run in parallel until equivalence is proven.
- Why: prevents authority transfer based on assumption rather than observed correctness.

### 1.5 Additive Reads Before Destructive Writes

- Rule: dual-read or shadow-read precedes dual-write; dual-write precedes source-of-truth change; destructive cleanup is last.
- Why: read parity is cheaper and safer than recovering from a bad write migration.

### 1.6 Observability Before Async

- Rule: events, jobs, webhooks, and listeners must be observable before production reliance.
- Why: async failures are delayed, distributed, and harder to diagnose without correlation.

### 1.7 Rollback Tested Before Activation

- Rule: every flag-driven cutover must have a documented and rehearsed rollback toggle or release reversal.
- Why: rollback plans written but not exercised are not real rollback plans.

### 1.8 Tenant Isolation Validated Before Rollout

- Rule: any change touching store resolution, membership, permissions, checkout ownership, or admin APIs must pass isolation verification first.
- Why: tenant leakage is the highest-severity platform regression.

### 1.9 Policies Remain the Enforcement Boundary

- Rule: migrations must move enforcement toward Policies and away from hidden checks in Actions, middleware, resources, or services.
- Why: the roadmap increases safety only if authorization becomes more explicit, auditable, and centralized.

### 1.10 One Source of Truth Per Cutover

- Rule: during compatibility windows, only one path is authoritative even if multiple paths execute.
- Why: dual systems without declared authority create irreconcilable support incidents.

### 1.11 Freeze Before Destruction

- Rule: before removing old paths, freeze related schema or contract changes for one release cycle.
- Why: prevents last-minute drift from corrupting removal readiness evidence.

### 1.12 Drift Prevention Is Part of Delivery

- Rule: each phase must include CI gates, forbidden-pattern checks, and ADR updates before it is considered done.
- Why: the architecture only survives execution if governance closes the loop in code review and CI.

## Wave 3A Execution Addendum — Identity Context Normalization

Wave 3A is approved only as a **limited normalization phase** before any future guard work.

### Wave 3A Allowed Scope

- explicit identity-context resolution
- route-domain ownership metadata and assertions
- merchant-only onboarding applicability isolation
- additive customer namespace under `/api/v1/storefront/account/*`
- identity telemetry expansion
- session-boundary preparation metadata
- additive customer bootstrap foundation
- identity isolation testing
- readiness evidence generation

### Wave 3A Forbidden Scope

- guard split
- cookie split
- session split
- customer-only session storage
- merchant-only session storage
- table split for customer/merchant accounts
- checkout auth rewrite
- RBAC authority cutover
- async auth flow redesign

### Wave 3A Exit Evidence

Wave 3A must not be considered complete without:

- actor classification tests for `merchant`, `customer`, and `super_admin`
- route isolation tests for merchant/admin and storefront/account domains
- onboarding bypass telemetry for customer actors
- onboarding evaluation telemetry for merchant actors
- session annotation verification
- machine-readable readiness artifact for post-Wave-3A review

## Wave 3B Execution Addendum — Session & Guard Preparation

Wave 3B is approved only as a **preparation and telemetry phase** for future session and guard separation.

### Wave 3B Allowed Scope

- explicit session ownership metadata
- guard shadow resolvers in observe-only mode
- session-domain and contamination telemetry
- logout ownership tracing
- CSRF ownership preparation metadata
- additive frontend session metadata
- machine-readable guard readiness reporting

### Wave 3B Forbidden Scope

- active guard split
- cookie split
- separate auth providers
- customer-only sessions
- merchant-only sessions
- session isolation enforcement
- account-table split
- checkout auth rewrite

### Wave 3B Exit Evidence

Wave 3B must not be considered complete without:

- session ownership resolution tests
- guard shadow resolution tests
- cross-domain contamination telemetry tests
- logout tracing tests
- CSRF preparation metadata tests
- storefront/merchant isolation telemetry tests
- machine-readable guard readiness artifact

## Wave 3C Execution Addendum — Guard Split Readiness Validation

Wave 3C is approved only as a **simulation, validation, and readiness-analysis phase**.

### Wave 3C Allowed Scope

- non-authoritative guard split simulation
- browser and session lifecycle validation
- concurrent-session readiness validation
- csrf ownership validation
- logout ownership validation
- frontend compatibility readiness analysis
- contamination stress detection and scoring
- machine-readable guard split validation artifacts

### Wave 3C Forbidden Scope

- active dual guards
- cookie split
- separate auth providers
- session isolation enforcement
- auth authority migration
- account-table split
- checkout auth rewrite

### Wave 3C Exit Evidence

Wave 3C must not be considered complete without:

- simulated guard ownership tests
- concurrent-session simulation coverage
- csrf ownership simulation coverage
- logout semantics simulation coverage
- contamination stress telemetry coverage
- frontend readiness analysis output
- machine-readable readiness scoring with explicit blockers
- operational risk analysis attached to the artifact

## 2. Phase Gate Matrix

### 2.1 Observability Rollout

- Entry Criteria: request tracing model defined; security events mapped; actor/store correlation IDs available; baseline dashboards drafted.
- Exit Criteria: all targeted requests emit structured logs, counters, latency histograms, error rates, and denial events; on-call runbook published.
- Required Telemetry: request volume, error rate, policy denials, store-context resolution failures, bootstrap latency, checkout latency, webhook failures.
- Required Tests: structured log contract tests, trace propagation tests, alert smoke tests, dashboard data integrity checks.
- Rollback Conditions: telemetry overhead increases p95 latency by more than 10 percent; log volume threatens stability; tracing corrupts request context.
- Operational Risks: noisy alerts, missing cardinality controls, PII leakage in logs.
- Release Risk Level: Medium.
- Feature Flags Required: `observability.events.enabled`, `observability.tracing.enabled`.
- Parallel Runtime Requirements: old behavior unchanged; observability runs passively.
- Migration Freeze Requirements: no async rollout, no major cutover, and no destructive cleanup until observability is verified.

### 2.2 Bootstrap Decomposition

- Entry Criteria: bootstrap schema versioning plan approved; frontend dependency map completed; parity logging defined.
- Exit Criteria: decomposed payload or sub-resolvers produce parity within threshold; bootstrap contract inventory documented; old contract remains supported.
- Required Telemetry: payload size, response time, field presence parity, active-store resolution parity, permission payload parity, frontend fallback hits.
- Required Tests: contract snapshot tests, field-level parity tests, store selection tests, no-store onboarding tests, frontend integration tests.
- Rollback Conditions: parity drift over threshold, frontend error spike, active store mismatch, onboarding redirect mismatch.
- Operational Risks: hidden frontend coupling, stale field assumptions, bootstrap N+1 regressions.
- Release Risk Level: High.
- Feature Flags Required: `bootstrap.v2.enabled`, `bootstrap.shadow_read`, `bootstrap.response_version`.
- Parallel Runtime Requirements: v1 remains authoritative while v2 is shadowed or tenant-canary only.
- Migration Freeze Requirements: no field removals during the minimum compatibility window.

### 2.3 Membership Evolution

- Entry Criteria: target membership model approved; authoritative fields defined; transition states mapped; tenant invariants codified.
- Exit Criteria: membership read/write parity proven; admin flows and store switching validated; no orphan or ambiguous memberships remain in pilot tenants.
- Required Telemetry: membership lookup parity, store access denials, membership mutation success rate, orphan membership count, owner uniqueness violations.
- Required Tests: membership lifecycle tests, invite/revoke/update tests, owner invariants, store switch tests, tenant leakage tests.
- Rollback Conditions: authorization drift, owner uniqueness violation, membership lookup failure, cross-store access anomaly.
- Operational Risks: dual-source membership reads, stale cached memberships, inconsistent owner/admin semantics.
- Release Risk Level: High.
- Feature Flags Required: `membership.dual_read`, `membership.dual_write`, `membership.v2_authority`.
- Parallel Runtime Requirements: old membership source remains authoritative until parity is sustained.
- Migration Freeze Requirements: freeze role semantics and membership-related UI changes during final cutover window.

### 2.4 Policy Normalization

- Entry Criteria: policy boundary map complete; current hidden checks inventoried; exception list approved for temporary coexistence.
- Exit Criteria: all targeted endpoints authorize through explicit policy calls; hidden checks removed or marked transitional; audit events mapped to policy decisions.
- Required Telemetry: policy invocation counts, allow/deny rates, hidden-check detector findings, unauthorized spikes by endpoint.
- Required Tests: controller authorization coverage, policy matrix tests, deny-path tests, regression tests for sensitive admin/storefront routes.
- Rollback Conditions: deny spike, unauthorized access incident, controller-path mismatch with old behavior.
- Operational Risks: duplicate authorization, policy gaps, accidental public access when removing legacy checks.
- Release Risk Level: High.
- Feature Flags Required: `policy.normalized_readiness`, `policy.enforcement_mode`.
- Parallel Runtime Requirements: legacy checks may log-only during transition, but policy result is non-authoritative until parity is proven.
- Migration Freeze Requirements: no new routes in affected domains without policy coverage.

#### 2.4.1 Wave 2.5 Safe-Domain Addendum

Wave 2.5 authorizes only a targeted safe-domain normalization slice before Wave 3.

Allowed domains in this addendum:

- `Brand`
- `Tag`
- `Category`
- `CMS Blog`
- `Dashboard` read paths

Mandatory Wave 2.5 execution rules:

- normalized controllers must call explicit domain policies
- generic `currentStore` controller authorization must be removed from normalized domains
- permission middleware may remain as a coarse compatibility gate
- hidden request/action authorization in normalized domains must be removed or replaced with explicit policy ownership
- normalized domains require tenant isolation coverage and parity telemetry before the slice is considered complete
- `Orders`, `Checkout`, membership evolution, auth topology, and guard/session changes remain blocked from this slice

Wave 2.5 completion does not unblock Wave 3 automatically.
Wave 3 remains blocked until remaining high-risk ownership drift, permission middleware drift, and fallback-path ambiguity are re-evaluated from generated artifacts.

### 2.5 RBAC Normalization

- Entry Criteria: permission taxonomy frozen; role-to-capability mapping documented; resolver parity plan approved.
- Exit Criteria: resolver output matches expected capabilities across sampled tenants; stale permissions removed from hot paths; dashboards cover denial anomalies.
- Required Telemetry: permission resolution parity, permission cache hit/miss, capability diff counts, deny spike by permission.
- Required Tests: resolver unit tests, policy-consumer tests, store-scoped permission tests, super-admin bypass tests.
- Rollback Conditions: permission drift beyond threshold, deny spike, cache corruption, capability inflation.
- Operational Risks: permission alias drift, stale caches, scope loss during mapping normalization.
- Release Risk Level: High.
- Feature Flags Required: `rbac.resolver.v2`, `rbac.snapshot_mode`, `rbac.dual_resolve`.
- Parallel Runtime Requirements: v1 and v2 resolvers run in parallel until parity target met.
- Migration Freeze Requirements: permission naming freeze once dual-resolve begins.

### 2.6 Identity Separation

- Entry Criteria: actor boundary ADR approved; route/domain ownership map complete; frontend session assumptions documented.
- Exit Criteria: merchant and customer identity contexts resolve independently without cross-context leakage; bootstrap and storefront account flows validated.
- Required Telemetry: actor-context mismatch count, cross-domain auth attempt count, login success rate by context, session confusion incidents.
- Required Tests: merchant-vs-customer flow tests, route isolation tests, bootstrap actor-context tests, storefront account isolation tests.
- Rollback Conditions: session contamination, actor misclassification, login failure spike, admin-to-storefront access bleed.
- Operational Risks: shared session assumptions, frontend cookie reuse, context misrouting.
- Release Risk Level: Critical.
- Feature Flags Required: `identity.context_split`, `identity.actor_resolution.v2`.
- Parallel Runtime Requirements: shared identity remains authoritative while split resolution runs in observe mode.
- Migration Freeze Requirements: auth route changes require coordinated freeze across frontend and backend.

### 2.7 Guard Split

- Entry Criteria: cookie/session naming plan approved; sanctum/session compatibility validated; logout and CSRF behavior tested.
- Exit Criteria: merchant and customer guards operate independently; no cookie collision; support runbook covers dual-session states.
- Required Telemetry: auth failures by guard, CSRF mismatch rate, cross-guard cookie presence, unexpected logout rate, concurrent-session anomalies.
- Required Tests: login/logout by guard, session invalidation tests, CSRF tests, remember-session tests, concurrent merchant/customer session tests.
- Rollback Conditions: auth outage, CSRF spike, mass logout event, inability to isolate session ownership.
- Operational Risks: cookie overlap, stale sessions, shared middleware assumptions.
- Release Risk Level: Critical.
- Feature Flags Required: `auth.guard_split.enabled`, `auth.customer_guard.shadow`, `auth.session_cookie.v2`.
- Parallel Runtime Requirements: dark launch first; optional read-only parallel validation of guard resolution.
- Migration Freeze Requirements: maintenance-style freeze for session config during cutover.

### 2.8 Checkout Hardening

- Entry Criteria: checkout ownership invariants approved; stock, order, webhook, and cart coupling mapped; direct model access risks logged.
- Exit Criteria: checkout ownership and payment state machine validated; duplicate completion prevented; tenant ownership parity confirmed.
- Required Telemetry: checkout create success, webhook completion success, duplicate payment handling, order-store mismatch count, stock deduction failures, cart clearing anomalies.
- Required Tests: guest checkout, authenticated checkout, webhook replay, duplicate webhook handling, order ownership tests, store isolation tests.
- Rollback Conditions: payment completion drift, order duplication, store mismatch, failed stock deduction, webhook replay incident.
- Operational Risks: webhook reprocessing, order/cart mismatch, stock corruption, cross-store ownership errors.
- Release Risk Level: Critical.
- Feature Flags Required: `checkout.hardening.enabled`, `checkout.webhook.idempotency`, `checkout.ownership_assertions`.
- Parallel Runtime Requirements: legacy checkout remains active while hardened assertions and shadow validation run.
- Migration Freeze Requirements: payment-provider config freeze and no checkout UX changes during final canary.

### 2.9 Async Adoption

- Entry Criteria: event contracts approved; idempotency keys defined; dead-letter/replay policy approved; correlation IDs flow end-to-end.
- Exit Criteria: async side effects are observable, replay-safe, idempotent, and have bounded failure handling; queue backlog SLOs stable.
- Required Telemetry: enqueue rate, success/failure rate, retry rate, dead-letter count, replay count, handler latency, side-effect duplication count.
- Required Tests: event serialization tests, listener idempotency tests, replay tests, out-of-order handling tests, poison-message tests.
- Rollback Conditions: backlog growth, duplicate side effects, replay corruption, dead-letter spike, missing correlation data.
- Operational Risks: out-of-order execution, exactly-once assumption failures, silent listener drift.
- Release Risk Level: Critical.
- Feature Flags Required: `async.domain_events.enabled`, `async.listener.<domain>.enabled`, `async.replay.pause`.
- Parallel Runtime Requirements: sync path remains authoritative until async parity and replay safety are proven.
- Migration Freeze Requirements: no destructive side-effect removal until replay tests pass in production-like conditions.

### 2.10 Enterprise Preparation

- Entry Criteria: enterprise capability list approved; audit, support, compliance, and rate-limit requirements scoped.
- Exit Criteria: enterprise-facing controls are additive, observable, and do not regress tenant isolation or core auth domains.
- Required Telemetry: audit completeness, support action traceability, admin override usage, enterprise feature uptake and failure rate.
- Required Tests: audit trail tests, support override tests, enterprise permission tests, rate-limit tests, compatibility tests with existing tenants.
- Rollback Conditions: support override leakage, audit gaps, tenant boundary regression, enterprise capability causing baseline instability.
- Operational Risks: platform/admin privilege creep, audit inconsistency, undocumented exceptions.
- Release Risk Level: Medium to High.
- Feature Flags Required: `enterprise.readiness.enabled`, `enterprise.audit.strict_mode`.
- Parallel Runtime Requirements: enterprise features remain disabled for general tenants until explicit opt-in.
- Migration Freeze Requirements: no privilege model changes without security review.

## 3. Feature Flag Strategy

### 3.1 What Must Be Flaggable

- authority shifts between old and new resolvers
- contract version selection
- shadow-read and dual-read paths
- dual-write paths
- kill switches for async listeners, checkout hardening assertions, and new bootstrap sources
- per-domain cutovers for membership, RBAC, policy enforcement, identity context, and checkout

### 3.2 What Must Never Be Flaggable

- tenant isolation invariants
- database integrity constraints once committed
- audit logging for security-sensitive mutations
- security patches for known active vulnerabilities
- core route topology requirements mandated by `ARCHITECTURE.md`

### 3.3 Flag Types

- Runtime flags: can be toggled without deploy; used for cutover, canary, rollback, shadow-read, dual-write, kill switches.
- Deploy-time flags: build or config controlled; used for static route registration, dependency wiring, and session config preparation.

### 3.4 Flag Domains

- `bootstrap.*`
- `membership.*`
- `policy.*`
- `rbac.*`
- `identity.*`
- `auth.*`
- `checkout.*`
- `async.*`
- `enterprise.*`
- `observability.*`

### 3.5 Naming Convention

- Format: `<domain>.<capability>.<mode>`
- Modes: `enabled`, `shadow`, `dual_read`, `dual_write`, `authority`, `kill`, `strict`
- Examples:
  - `bootstrap.v2.enabled`
  - `bootstrap.shadow_read`
  - `membership.dual_read`
  - `membership.v2.authority`
  - `rbac.snapshot_mode`
  - `policy.enforcement.strict`
  - `identity.context_split.enabled`
  - `auth.guard_split.enabled`
  - `checkout.ownership_assertions.enabled`
  - `async.listener.orders.kill`

### 3.6 Ownership Convention

- Every flag has one technical owner and one business/operations owner.
- Every flag has an expiry milestone and cleanup date at creation time.
- Every flag is documented in a central registry with default state, blast radius, rollback effect, and tenant targeting rules.

### 3.7 Kill-Switch Strategy

- Domain kill switches must exist for checkout hardening assertions, async listeners, and new authority paths.
- Kill switches must fail closed for writes that threaten integrity and fail open only for non-authoritative shadow paths.
- Kill switches must be tested in staging before production use.

### 3.8 Rollback Toggles

- Every authority flag must have a documented reverse toggle.
- Reverse toggles must restore the previous authoritative path without requiring schema mutation.
- If rollback requires data backfill or cache flushing, that dependency must be documented alongside the flag.

### 3.9 Anti-Explosion Rules

- No nested flag dependency trees deeper than two levels.
- No feature may require more than three runtime flags at the same time.
- Shadow and dual-write flags must be temporary and removed within one release after final cutover.
- Hidden behavior controlled by environment-only checks is forbidden.

### 3.10 Cleanup Policy

- Shadow flags removed within 1 release after parity acceptance.
- Dual-write flags removed within 2 releases after write authority stabilizes.
- Deprecated fallback flags removed only after compatibility windows and removal telemetry are satisfied.
- Flags without documented owners or expiry are blocked from release.

## 4. Compatibility Window Policy

| Migration | Minimum Window | Deprecation Notice | Frontend Coordination | Telemetry Before Removal | Fallback Behavior | Sunset Condition |
|---|---|---|---|---|---|---|
| Bootstrap payload changes | 2 releases or 30 days | versioned field notice in release notes and contract docs | required | field access usage, payload parity, client error rate | serve v1 contract or adapter response | zero unsupported-client traffic for 14 days |
| Auth/session changes | 2 releases plus dark-launch period | session/cookie change notice with support runbook | required | login failure rate, CSRF mismatch, logout anomalies | revert to prior cookie/guard authority | stable auth metrics for 14 days |
| Guard changes | 2 releases | explicit guard split notice and migration guide | required | cross-guard leakage, cookie collisions, failure spikes | disable split guard and restore prior guard resolution | no cross-guard anomalies for 14 days |
| Membership migration | 2 releases or 21 days | admin and store access behavior notice | required for dashboard flows | membership parity, access denial drift, owner uniqueness | keep legacy membership source authoritative | parity < threshold for 14 days |
| Permission resolution changes | 2 releases or 21 days | capability taxonomy freeze announcement | required for UI capability gating | resolver parity, deny spike, cache anomalies | revert to previous resolver | parity < threshold for 14 days |
| Policy normalization | 1 release minimum | no consumer-facing notice unless API behavior changes | frontend informed if deny semantics change | policy-vs-legacy drift, unauthorized spike | log-only policy mode or legacy enforcement | zero unauthorized regressions in canary and full rollout |

### Compatibility Rules

- Removal of legacy fields requires evidence that no supported frontend path still consumes them.
- Fallback behavior must be automatic for known compatible clients and explicit failure for incompatible unsupported clients.
- Compatibility windows reset if major parity drift or rollback occurs.

## 5. Rollback Doctrine

### 5.1 Decision Authority

- Incident Commander owns immediate rollback during active incidents.
- Operations Owner can execute flag rollback for approved flags.
- Architecture Owner and Security Owner must approve rollback bypasses involving auth, guard, or tenant boundaries after the incident is stabilized.

### 5.2 Rollback Triggers

- Sev-1 or Sev-2 tenant isolation incident
- unauthorized access or privilege escalation
- parity drift above critical threshold
- checkout payment integrity anomaly
- auth/login failure spike above agreed SLO
- dead-letter or replay corruption in async systems

### 5.3 Rollback Telemetry

- release marker correlation
- affected tenants and request volume
- parity drift by endpoint/domain
- auth failure and denial spikes
- webhook and async duplication anomalies
- rollback completion confirmation

### 5.4 Rollback Granularity

- per-tenant canary rollback
- per-domain flag rollback
- per-endpoint contract rollback
- per-listener async rollback
- full release rollback only when runtime rollback cannot restore safety

### 5.5 Partial Rollback Strategy

- prefer authority rollback before deployment rollback
- preserve additive schema while reverting reads/writes to prior source
- stop async consumers before replaying or restoring sync side effects
- use tenant-targeted rollback for limited blast radius when safe

### 5.6 Safe Rollback Windows

- bootstrap, policy, RBAC, and membership read-authority changes: within first 24 hours or until destructive cleanup begins
- auth and guard changes: within first 6 hours, before session migration effects become widespread
- checkout hardening: within first 4 hours, before payment backlog or webhook replay complicates state
- async adoption: within first 24 hours, before dead-letter replay or downstream compensation begins

### 5.7 Irreversible Migration Classification

| Migration | Classification | Notes |
|---|---|---|
| Observability rollout | Reversible | passive instrumentation can be disabled quickly |
| Bootstrap decomposition | Reversible | as long as v1 contract remains available |
| Membership dual-read/dual-write | Conditionally reversible | reversible until destructive cleanup or role semantic removal |
| Policy normalization | Reversible | if legacy checks remain log-only or recoverable |
| RBAC normalization | Conditionally reversible | cache/schema cleanup may reduce reversibility |
| Identity separation | Conditionally reversible | reversible before session/cookie cleanup and contract removal |
| Guard split | Operationally irreversible after broad session migration | config can revert, but user session state may need coordinated reset |
| Checkout hardening | Conditionally reversible | payment and order side effects limit safe reversal |
| Async adoption | Operationally irreversible once side effects depend solely on async consumers | requires compensation rather than simple rollback |
| Enterprise preparation | Reversible if additive | irreversible if privilege model replaces baseline without compatibility |

## 6. Parity Telemetry Program

### 6.1 Required Parity Streams

- dual-read parity logs for bootstrap resolution
- membership resolution parity
- permission resolver parity
- policy decision parity
- checkout ownership parity
- session/guard routing parity during identity work

### 6.2 Drift Thresholds

- Info threshold: drift > 0.1 percent for any non-security-critical domain over 15 minutes
- Alert threshold: drift > 0.5 percent for bootstrap, membership, or RBAC
- Critical threshold: any unauthorized allow, tenant leak, or checkout ownership mismatch above 0 percent

### 6.3 Cutover Readiness Signals

- minimum 7 consecutive days below alert thresholds for high-risk domains
- zero critical tenant-isolation or auth parity violations in that period
- stable latency within 10 percent of baseline
- support and incident channels show no unexplained tenant complaints

### 6.4 Retention Policy

- raw parity events: 30 days
- aggregated dashboards: 90 days
- release-cutover summaries and sign-off evidence: 1 year
- security-sensitive parity incidents: per audit retention policy

### 6.5 Telemetry Rules

- parity telemetry must be non-authoritative and side-effect free
- parity records must include tenant/store context, actor context, release marker, and flag state
- parity logging must avoid PII payload dumps

## 7. Tenant Isolation Verification Program

### 7.1 Critical Invariants

- no commerce query without `store_id` scope unless explicitly allowed for super-admin global analytics
- no admin route without `{store}` except approved platform routes
- no permission resolution without store scope except super-admin bypass
- no membership grant or store switch outside verified membership/ownership rules
- no checkout order, cart, payment, or webhook flow may cross store ownership boundaries
- no customer session may acquire merchant authority

### 7.2 Automated Verification

- repository scope tests for all commerce and CMS store-scoped repositories
- route topology tests for admin and storefront route families
- policy enforcement tests for store-bound actions
- membership and store-switch boundary tests
- checkout ownership and webhook ownership tests
- cross-store data leakage tests using fixture tenants with overlapping IDs and slugs

### 7.3 Runtime Isolation Assertions

- assert resolved store context matches route/store authority
- assert authenticated actor has valid membership for store-bound admin operations
- assert order/cart/store ownership alignment on checkout and webhook processing
- emit critical security events on invariant failure

### 7.4 Deployment Blockers

- failed tenant-boundary tests
- missing store context propagation in targeted paths
- any unresolved critical invariant exception in staging or canary
- policy or permission rollout without tenant-aware deny telemetry

### 7.5 Emergency Shutdown Conditions

- confirmed cross-store data exposure
- confirmed privilege escalation across tenant boundaries
- checkout creating orders in wrong store
- guard/session contamination granting wrong domain access

Emergency response is: stop rollout, disable new authority flags, freeze related deployments, preserve evidence, and escalate immediately.

## 8. Operational Readiness Checklists

### 8.1 Bootstrap Rollout

- Pre-Deploy Checks: contract diff approved, frontend adapter ready, parity dashboards live, v1 fallback verified
- Deploy Checks: flags default off, release markers emitted, error budget healthy
- Post-Deploy Checks: shadow-read enabled for canary tenants, payload size and latency monitored, field parity inspected
- Telemetry Validation: active store parity, permissions parity, onboarding parity, frontend bootstrap error rate
- Rollback Readiness Validation: `bootstrap.v2.enabled` and `bootstrap.shadow_read` reversal tested

### 8.2 Membership Rollout

- Pre-Deploy Checks: owner invariants verified, migration backfill dry-run complete, admin UX freeze active
- Deploy Checks: dual-read first, dual-write later, no authority switch on initial release
- Post-Deploy Checks: access denials sampled, invite/update/revoke flows tested on canary tenants
- Telemetry Validation: membership parity, owner uniqueness, store switch anomalies, access drift
- Rollback Readiness Validation: legacy membership authority intact, caches flushable, backfill reversibility documented

### 8.3 Guard Split Rollout

- Pre-Deploy Checks: cookie names finalized, CSRF behavior tested, support runbook published, frontend coordinated
- Deploy Checks: dark-launch mode only first, new guard non-authoritative, login smoke tests executed
- Post-Deploy Checks: concurrent merchant/customer sessions validated, logout behavior sampled, auth failure watch active
- Telemetry Validation: CSRF mismatch, login success, unexpected logout, cross-guard cookie collision
- Rollback Readiness Validation: prior guard authority restorable, session reset playbook available

### 8.4 Checkout Hardening Rollout

- Pre-Deploy Checks: webhook replay tests passed, payment provider config frozen, ownership assertions prepared
- Deploy Checks: assertions log-only first, hardened path canary only, finance/support notified
- Post-Deploy Checks: sample paid orders reconciled, duplicate webhook handling verified, stock mutation watch active
- Telemetry Validation: order-store mismatch, duplicate completion, stock deduction failure, cart clearing anomaly
- Rollback Readiness Validation: hardened authority flag reversible, manual payment reconciliation playbook ready

### 8.5 Async Rollout

- Pre-Deploy Checks: queue dashboards live, dead-letter policy active, idempotency keys enforced, replay tooling verified
- Deploy Checks: listeners disabled by default, one listener family enabled at a time, backlog SLO monitored
- Post-Deploy Checks: retries sampled, dead-letter queue inspected, side-effect duplication audit performed
- Telemetry Validation: enqueue success, retry rate, dead-letter count, replay count, handler latency
- Rollback Readiness Validation: listener kill switches verified, sync fallback still available or compensation plan approved

## 9. Architectural Drift Prevention Program

### 9.1 Architecture Review Triggers

- any change to auth, session, guards, bootstrap, permissions, policies, membership, checkout, async side effects, or route topology
- any new shared service crossing domain boundaries
- any migration introducing or removing source-of-truth fields

### 9.2 ADR Requirements

- required for all high-risk phases and any irreversible or conditionally reversible migration
- must define authority model, compatibility window, telemetry, rollback posture, and tenant-isolation impact

### 9.3 Mandatory Tests

- policy coverage tests for protected routes
- repository store-scope tests
- bootstrap contract tests
- membership lifecycle and store-switch tests
- checkout ownership and webhook replay tests
- async idempotency and replay tests

### 9.4 Code Review Gates

- explicit architecture section in PR template
- flag/rollback section mandatory for risky migrations
- no approval if new hidden authorization appears outside policies
- no approval if new store-scoped queries bypass repositories

### 9.5 Forbidden Patterns

- auth leakage into Actions, Services, or Resources
- inline permission checks outside policy boundary except approved transitional instrumentation
- bootstrap payload growth without contract review
- repository abuse via direct model queries in controllers/services
- request/session coupling inside business logic
- async side effects without idempotency or correlation IDs

### 9.6 CI Enforcement Roadmap

- Phase A: grep-based forbidden pattern checks and route topology assertions
- Phase B: policy-coverage and repository-scope test suites required in CI
- Phase C: architecture conformance checks for flag registry, ADR presence, and compatibility metadata
- Phase D: parity telemetry gate for authority-changing releases in pre-production

## 10. Release Strategy

### 10.1 Independent Releases

- observability rollout
- policy normalization when behavior-preserving
- RBAC normalization in shadow mode
- enterprise preparation if additive and disabled by default

### 10.2 Coordinated Frontend/Backend Releases

- bootstrap decomposition
- auth/session changes
- identity separation
- guard split
- permission contract changes that affect UI gating

### 10.3 Dark Launch Required

- bootstrap v2 resolution
- identity separation
- guard split
- async listeners

### 10.4 Canary Release Required

- membership authority cutover
- RBAC resolver authority cutover
- checkout hardening authority shift
- policy normalization in strict mode

### 10.5 Maintenance Window Recommended

- guard split cutover
- payment/checkout authority changes
- any migration involving session config or cookie semantics

### 10.6 Sequencing

1. observability rollout
2. bootstrap decomposition and parity
3. membership evolution
4. policy normalization
5. RBAC normalization
6. identity separation
7. guard split
8. checkout hardening
9. async adoption
10. enterprise preparation

### 10.7 Production Verification Strategy

- verify release markers and flag states immediately after deploy
- validate canary tenant flows end-to-end
- monitor parity, deny, auth, checkout, and queue dashboards for at least one steady-state business cycle
- require formal exit sign-off before expanding rollout radius

### 10.8 Rollback Timing Windows

- critical auth and checkout phases: decision in minutes, not hours
- bootstrap, membership, policy, RBAC: rollback decision within same business day
- async: rollback or listener kill within 30 minutes of uncontrolled drift

### 10.9 Deployment Freeze Triggers

- active Sev-1 or Sev-2 incident in related domain
- unresolved parity drift over alert threshold
- open tenant isolation concern
- significant auth or checkout SLO degradation

## 11. Formal Risk Register

| Migration | Risk Title | Severity | Probability | Blast Radius | Detection Difficulty | Rollback Difficulty | Mitigation Strategy | Monitoring Strategy | Contingency Strategy |
|---|---|---|---|---|---|---|---|---|---|
| Session split | Cross-context session contamination | Critical | Medium | Platform-wide | Medium | High | dark launch, cookie isolation, concurrent-session tests | auth failures, cookie collisions, actor mismatch | disable split guard, reset affected sessions |
| Membership cutover | Wrong store authority assignment | Critical | Medium | Multi-tenant | Medium | Medium | dual-read, owner invariant checks, canary tenants | access denial drift, owner uniqueness, store-switch anomalies | restore legacy authority, freeze membership writes if needed |
| Permission cutover | Capability inflation or denial drift | Critical | Medium | Multi-tenant admin | Medium | Medium | dual-resolve, taxonomy freeze, cache controls | parity diff, deny spikes, permission cache errors | revert resolver authority, flush caches |
| Bootstrap slimming | Frontend dependency breakage | High | High | Frontend-wide | Low | Low | contract inventory, versioning, fallback adapter | bootstrap errors, field access misses, latency | serve v1 contract, re-enable removed fields |
| Checkout hardening | Order/payment ownership mismatch | Critical | Medium | Revenue path | Medium | High | assertions first, canary, webhook replay tests | mismatched store/order, duplicate payment completion | revert hardening authority, manual reconciliation |
| Async replay failures | Duplicate or missing side effects | Critical | Medium | Domain-specific to platform-wide | High | High | idempotency, dead-letter, replay tooling, one listener at a time | retry/dead-letter/replay dashboards, side-effect duplication counters | kill listener, compensate manually, pause replay |
| Policy normalization | Hidden auth path removed incorrectly | High | Medium | Protected endpoints | Medium | Medium | policy parity, route audit, deny-path tests | unauthorized spike, policy invocation drift | revert to prior enforcement mode |
| Guard split | CSRF or logout instability | High | Medium | Authenticated users | Low | High | csrf tests, cookie migration rehearsal, maintenance window | csrf mismatch, login failure, logout spikes | revert guard config and notify support |

## 12. Multi-Wave Execution Program

### Wave 1 - Stabilization

- Goals: establish observability, release markers, flag registry, rollback playbooks, parity dashboards
- Dependencies: none
- Risks: low-quality telemetry causing false confidence
- Rollback Posture: fully reversible
- Expected Duration: 1 to 2 releases
- Operational Impact: low user impact, medium ops workload
- Success Metrics: dashboards live, alert noise acceptable, release checklist adopted

### Wave 2 - Boundary Normalization

- Goals: bootstrap decomposition, membership evolution in dual-read mode, policy normalization groundwork
- Dependencies: Wave 1 observability
- Risks: hidden contract and membership coupling
- Rollback Posture: reversible while legacy authority retained
- Expected Duration: 2 to 3 releases
- Operational Impact: medium; support and frontend coordination required
- Success Metrics: bootstrap parity, membership parity, zero tenant-isolation regressions

### Wave 3 - Identity Separation

- Goals: actor-context split, customer-vs-merchant isolation, guard split dark launch
- Dependencies: Wave 2 boundary clarity and telemetry
- Risks: session contamination and frontend auth regression
- Rollback Posture: conditionally reversible before broad session migration
- Expected Duration: 2 releases
- Operational Impact: high; auth-sensitive releases only
- Success Metrics: zero cross-context leakage, stable login/logout SLOs

## 13. Wave 2 Boundary Normalization Execution Notes

### 13.1 Authority Posture

Wave 2 authority posture is:

- legacy bootstrap response remains authoritative by default
- decomposed bootstrap path may run in shadow mode only
- `store_user` remains the only membership source of truth
- legacy permission outcomes remain authoritative unless explicitly flagged otherwise
- policy telemetry is observational, not authoritative

### 13.2 Required Wave 2 Runtime Flags

Wave 2 implementation uses the following runtime controls:

- `bootstrap.v2.enabled`
- `bootstrap.shadow_read`
- `bootstrap.response_version`
- `membership.dual_read`
- `policy.normalized_readiness`
- `rbac.resolver.v2`
- `rbac.dual_resolve`

Wave 2 rule:

- authority flags default to legacy-compatible behavior
- parity flags may be enabled only when side-effect free

### 13.3 Required Wave 2 Evidence

Before any Wave 3 preparation begins, the release record must include:

- bootstrap contract snapshot test evidence
- bootstrap shadow parity evidence
- membership resolver consistency test evidence
- permission resolver parity evidence
- policy decision telemetry evidence
- tenant isolation verification for bootstrap/store context paths
- drift detection output from warning-mode checks

### 13.4 Warning-Mode Drift Detection

Wave 2 drift detection is intentionally non-breaking.

Initial warning surfaces:

- hidden auth access inside `Actions`
- direct `hasPermissionTo()` usage outside policies/resolvers
- controller paths still relying on generic `currentStore` authorization
- admin routes without explicit `permission:` middleware

Governance rule:

- findings must be recorded and triaged
- findings do not authorize big-bang rewrites
- findings become migration backlog for later policy normalization work

### 13.5 Wave 2 Exit Readiness

Wave 2 is considered stable only when:

- bootstrap parity is healthy
- decomposed resolver timings are stable
- no bootstrap contract regressions are detected
- membership resolver output matches pivot authority
- permission resolver drift is zero or explicitly explained
- policy telemetry is present on targeted paths
- no tenant isolation regression is found in Wave 2 test coverage

### 13.6 Blockers Before Wave 3

Wave 3 MUST NOT begin until all of the following are true:

- bootstrap decomposition is stable in production-like traffic
- bootstrap shadow parity is healthy
- membership abstraction is stable and trusted
- warning-mode drift findings are understood
- frontend bootstrap dependency assumptions remain unchanged or documented
- no unresolved tenant-boundary anomaly remains in Wave 2 telemetry

### 13.7 Wave 2 Stabilization Hardening Evidence

Wave 2 stabilization hardening adds the following required evidence streams:

- super-admin bootstrap parity validation
- zero-store merchant/bootstrap parity validation
- zero-store customer/bootstrap parity validation
- onboarding-state bootstrap parity validation
- invalid or missing active-store bootstrap parity validation
- drift triage report with severity and category breakdown
- policy ownership visibility report
- machine-readable Wave 2 readiness artifact

These are still:

- additive
- compatibility-first
- non-authoritative
- insufficient on their own to authorize Wave 3

### 13.8 Drift Triage Doctrine

Wave 2 drift reporting must now support triage, not just raw warnings.

Required drift report characteristics:

- category per finding
- severity per finding
- stable fingerprint per finding
- allowlist support
- baseline snapshot comparison
- regression visibility for newly introduced drift
- machine-readable JSON output

Required categories:

- hidden authorization
- policy ownership drift
- permission middleware drift
- bootstrap coupling
- request/session coupling
- repository leakage

Governance rule:

- allowlists are temporary governance tools, not architecture absolution
- baseline snapshots exist to expose regression, not to normalize permanent debt
- warning-mode remains non-blocking in CI until explicit policy hard-fail approval exists

### 13.9 Wave 2 Readiness Artifact

Wave 2 readiness evidence must be exportable as a machine-readable artifact.

Required readiness sections:

- bootstrap parity health
- resolver stability
- drift counts and trend
- tenant isolation status
- policy instrumentation coverage
- observability health
- Wave 3 gate status

The readiness artifact is a gate input only.
It does not authorize Wave 3 automatically.

### Wave 4 - Authorization Maturity

- Goals: policy normalization strict mode, RBAC normalization, resolver authority cutover
- Dependencies: identity context stable
- Risks: deny spikes, permission drift, hidden authorization leftovers
- Rollback Posture: reversible by authority toggle until cleanup starts
- Expected Duration: 2 releases
- Operational Impact: high for admin domains
- Success Metrics: parity below threshold for 7 days, no unauthorized incidents

### Wave 5 - Operational Maturity

- Goals: checkout hardening, async adoption with replay safety, audit improvements
- Dependencies: Waves 1 to 4 complete enough to support reliable tracing and authority boundaries
- Risks: payment integrity incidents, async duplication, operator fatigue
- Rollback Posture: checkout conditionally reversible; async may require compensation
- Expected Duration: 2 to 3 releases
- Operational Impact: critical-path monitoring required
- Success Metrics: stable checkout completion, no duplicate side effects, bounded retries and dead letters

### Wave 6 - Enterprise Readiness

- Goals: enterprise controls, stricter auditability, support-safe overrides, compliance preparation
- Dependencies: operational maturity and stable auth/authorization boundaries
- Risks: privilege creep and audit inconsistency
- Rollback Posture: mostly reversible if additive
- Expected Duration: 1 to 2 releases
- Operational Impact: moderate, mostly admin/platform facing
- Success Metrics: enterprise capabilities additive, auditable, and non-regressive for baseline tenants

## 13. Irreversible Migration Warnings

- Guard split becomes operationally irreversible after broad session transition and old session semantics are removed.
- Async-only side effects become operationally irreversible once sync compensating paths are deleted.
- Membership and RBAC cleanup become conditionally irreversible once old authority fields, mappings, or adapters are dropped.
- Any destructive contract cleanup before compatibility telemetry is complete is treated as an unauthorized release violation.

## 14. Safe Rollback Windows

- Bootstrap and contract cutovers: first 24 hours, before field cleanup or client cache saturation
- Membership and RBAC authority shifts: first 24 hours, before role or mapping cleanup
- Auth and guard changes: first 6 hours, before large-scale session churn
- Checkout hardening: first 4 hours, before payment/manual support backlog grows
- Async listener activation: first 24 hours, before replay and compensation complexity increases

## 15. Deployment Freeze Rules

- Freeze all related deployments during active incidents in auth, checkout, tenant isolation, or permissions.
- Freeze destructive cleanup until one full stable release follows the cutover.
- Freeze permission taxonomy during RBAC dual-resolve period.
- Freeze membership schema and semantics during final membership authority cutover.
- Freeze session config, cookie naming, and CSRF middleware changes during guard split window.

## 16. Operational Escalation Rules

### Severity Triggers

- Sev-1: confirmed tenant leakage, privilege escalation, payment corruption, or platform-wide auth outage
- Sev-2: sustained parity drift over critical threshold, checkout duplication, widespread denial spike
- Sev-3: contained canary anomaly, alert noise, or rollback friction without user-facing outage

### Escalation Path

1. Incident Commander acknowledges and stops rollout.
2. Operations Owner disables affected authority or listener flags.
3. Architecture Owner assesses boundary breach and required freeze scope.
4. Security Owner joins immediately for auth, permission, guard, or tenant incidents.
5. Frontend Owner joins when contract, bootstrap, or session behavior affects clients.

### Mandatory Incident Actions

- preserve release marker, flag state, and tenant context evidence
- declare affected phases and freeze related releases
- decide within the safe rollback window whether to reverse authority, disable listeners, or revert deploy
- publish post-incident migration adjustments before resuming rollout

## Appendix: Current Execution Focus Areas

Based on the current repository state and `ARCHITECTURE.md`, execution must pay special attention to:

- bootstrap correctness and performance because `GetBootstrapAction` is a current coordination hotspot
- permission and membership consistency because the architecture document contains transitional tension between policy-only enforcement and some action-level membership expectations
- checkout hardening because current checkout orchestration is a high-risk integrity path
- auth and guard evolution because the architecture explicitly prepares for customer guard separation while the current platform remains session-based and shared-table oriented

## Appendix: Stabilization Implementation Status

The Wave 1 stabilization implementation currently permits only the following additive
foundations:

- request correlation IDs
- request-scoped trace context
- structured logging context propagation
- audit infrastructure baseline
- release version markers
- security event recording primitives

This phase does not authorize:

- bootstrap decomposition
- membership migration
- RBAC migration
- guard split
- checkout refactor
- async rollout
