# Production Hardening Checklist

This document is the canonical engineering checklist for stabilizing the auth, onboarding, store, provisioning, and bootstrap lifecycles. It is derived from previous audits and verified against the codebase as of 2026-05-23. Each item includes severity (Low/Medium/High/Critical), affected systems, code references, reproduction scenario, production impact, recommended fix, implementation difficulty (Easy/Medium/Hard), and regression risk (Low/Medium/High).

## 1. Critical Risks

- **Shared Session Contamination**: Severity: Critical. Affected: Auth, Session. Code: `AuthService::logout`, `SessionOwnershipResolver`. Reproduction: Login as merchant in one tab, customer in another; logout merchant affects customer. Impact: Unauthorized access or session loss. Fix: Add defensive logging in logout; defer to future guard split. Difficulty: Medium. Regression Risk: Medium.
- **Onboarding Race Conditions**: Severity: High. Affected: Onboarding, Store Creation. Code: `CreateStoreAction`. Reproduction: Concurrent POST /stores from same user. Impact: Duplicate stores or inconsistent onboarding state. Fix: Implement cache lock. Difficulty: Easy. Regression Risk: Low.
- **Provisioning Failure Recovery**: Severity: High. Affected: Provisioning. Code: `BootstrapStoreJob::failed`. Reproduction: Job fails permanently (e.g., DB error). Impact: Stuck provisioning without notification. Fix: Add status rollback and logging. Difficulty: Medium. Regression Risk: Low.
- **Permission Escalation**: Severity: Medium. Affected: Policies. Code: `StorePolicy::before`. Reproduction: Super admin accesses without audit. Impact: Untracked privileged actions. Fix: Add logging. Difficulty: Easy. Regression Risk: Low.
- **Error Code Inconsistencies**: Severity: Medium. Affected: Exceptions. Code: `InvalidStoreLifecycleTransitionException`. Reproduction: Throw custom exception without enum code. Impact: Frontend parsing errors. Fix: Normalize to use ErrorCode. Difficulty: Easy. Regression Risk: Medium.

## 2. Contract Drift

- **Bootstrap Payload Nullable Fields**: Severity: Medium. Affected: Bootstrap. Code: `BootstrapOnboardingDTO`. Reproduction: Call /v1/me pre-store creation; store_id null. Impact: Frontend null errors. Fix: Add contract tests for nullables. Difficulty: Easy. Regression Risk: Low.
- **Redirect Rules Mismatch**: Severity: Medium. Affected: Middleware, Exceptions. Code: `EnsureOnboardingIsCompleted`. Reproduction: Incomplete onboarding throws 403; no server redirect. Impact: Frontend must handle. Fix: Document in tests. Difficulty: Easy. Regression Risk: Low.
- **State Transition Differences**: Severity: High. Affected: Store Lifecycle. Code: `StoreStatusEnum::allowedTransitions()`. Reproduction: Direct to ACTIVE without provisioning. Impact: Inconsistent states. Fix: Add transition guards. Difficulty: Medium. Regression Risk: Medium.

## 3. Missing Guarantees

- **Idempotency in Transitions**: Severity: Medium. Affected: Onboarding, Store. Code: `OnboardingTransitionService`. Reproduction: Repeat transition to same state. Impact: Unnecessary DB writes. Fix: Add idempotency checks. Difficulty: Easy. Regression Risk: Low.
- **Retry Hazards in Jobs**: Severity: Medium. Affected: Provisioning. Code: `BootstrapStoreJob`. Reproduction: Job retries on transient error. Impact: Partial state. Fix: Ensure idempotent steps. Difficulty: Medium. Regression Risk: Medium.

## 4. Concurrency Risks

- **Concurrent Logins**: Severity: High. Affected: Auth. Code: `AuthController::login`. Reproduction: Parallel logins from different devices. Impact: Session overwrite. Fix: Add logging for detection. Difficulty: Easy. Regression Risk: Low.
- **Multi-Tab Behavior**: Severity: High. Affected: Session. Code: `SessionOwnershipResolver`. Reproduction: Actions in multiple tabs. Impact: Contamination. Fix: Document risks with TODOs. Difficulty: Easy. Regression Risk: Low.

## 5. Missing Tests

- **Onboarding Resume**: Severity: High. Affected: Onboarding. Code: `OnboardingTransitionService`. Reproduction: Interrupt and resume. Impact: Unverified recovery. Fix: Add feature tests. Difficulty: Medium. Regression Risk: Low.
- **Provisioning Polling**: Severity: Medium. Affected: Provisioning. Code: `BootstrapStoreJob`. Reproduction: Poll during failure. Impact: Unhandled states. Fix: Add integration tests. Difficulty: Medium. Regression Risk: Medium.
- **Concurrent Sessions**: Severity: High. Affected: Session. Code: Absent tests. Reproduction: Multi-tab actions. Impact: Undetected contamination. Fix: Add tests. Difficulty: Hard. Regression Risk: High.

## 6. Security Gaps

- **Unlogged Super Admin Bypass**: Severity: Medium. Affected: Policies. Code: `StorePolicy::before`. Reproduction: Super admin access. Impact: Audit hole. Fix: Add security event logging. Difficulty: Easy. Regression Risk: Low.
- **Rate Limiting Gaps**: Severity: Low. Affected: Auth. Code: `AppServiceProvider` (login limiter). Reproduction: Brute force other endpoints. Impact: Potential abuse. Fix: Extend limiting. Difficulty: Medium. Regression Risk: Medium.

## 7. Frontend Contract Risks

- **Payload Shape Drift**: Severity: High. Affected: Bootstrap. Code: `BootstrapPayloadSerializer`. Reproduction: Add new field without test. Impact: Frontend breaks. Fix: Add contract tests. Difficulty: Medium. Regression Risk: Low.
- **Error Handling Mismatch**: Severity: Medium. Affected: Exceptions. Code: `ExceptionRegistrar`. Reproduction: Validation error without code. Impact: Inconsistent parsing. Fix: Normalize responses. Difficulty: Easy. Regression Risk: Low.

## 8. State Machine Risks

- **Illegal Transitions**: Severity: High. Affected: Store Lifecycle. Code: `StoreLifecycleService`. Reproduction: Force invalid transition. Impact: Invalid states. Fix: Strengthen validation tests. Difficulty: Easy. Regression Risk: Low.
- **Silent Transitions**: Severity: Medium. Affected: Onboarding. Code: `OnboardingStepEnum`. Reproduction: Idempotent no-op. Impact: Unlogged. Fix: Add logging. Difficulty: Easy. Regression Risk: Low.

## 9. Session Isolation Risks

- **Contamination in Shared Model**: Severity: Critical. Affected: Session. Code: `Sanctum`. Reproduction: Cross-domain actions. Impact: Security breach. Fix: Add logging and TODOs for split. Difficulty: Medium. Regression Risk: High.

## 10. Operational Risks

- **Job Failure Without Notification**: Severity: High. Affected: Provisioning. Code: `BootstrapStoreJob`. Reproduction: Exhaust retries. Impact: Stuck stores. Fix: Add failed handling. Difficulty: Medium. Regression Risk: Medium.

## 11. Immediate Fixes

- **Store Creation Locking**: Severity: High. Affected: Store. Code: `CreateStoreAction`. Reproduction: Concurrent creates. Impact: Duplicates. Fix: Cache lock. Difficulty: Easy. Regression Risk: Low.
- **Provisioning Rollback**: Severity: High. Affected: Jobs. Code: `BootstrapStoreJob`. Reproduction: Failure. Impact: Inconsistent state. Fix: Add rollback. Difficulty: Medium. Regression Risk: Medium.

## 12. Deferred Improvements

- **Guard Split**: Severity: Critical. Affected: Auth. Code: `GuardShadowAnalyzer`. Reproduction: N/A. Impact: Isolation limits. Fix: Future implementation. Difficulty: Hard. Regression Risk: High.

## 13. Non-Goals

- Redesign auth model.
- Change API shapes.

## 14. Migration Safety Rules

- Dual-read/write for changes.
- Feature flags for rollouts.
- Contract tests pre-deploy.
