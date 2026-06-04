# Technical Debt Baseline

## Methodology

Every item was identified through:
1. Automated analysis (dead code detection, grep for TODO/FIXME)
2. Test suite results (14 pre-existing failures)
3. Code review (architecture violations, type safety issues)
4. Architecture document comparison (drift from `docs/ARCHITECTURE.md`)

Items are scored on a 5-dimension scale (1-5 each, higher = worse):

| Dimension | Meaning |
|-----------|---------|
| **Severity** | Impact on correctness or production behavior |
| **Scope** | Number of files/features affected |
| **Fix Effort** | Estimated developer-hours to remediate |
| **Risk** | Likelihood of causing a production incident |
| **Awareness** | How hidden/unknown the debt is |

---

## Ownership & Identity Domain

| # | Item | Severity | Scope | Effort | Risk | Awareness | Score | Category |
|---|------|----------|-------|--------|------|-----------|-------|----------|
| 1 | `GuardResolutionResult::$authDomain` typed `string` but nullable | 3 | 2 | 1 | 2 | 1 | 9 | Type Safety |
| 2 | `TransitionalGuardResolver` constructor params unused (`$tenantGuard`, `$boundaryGuard`) | 1 | 1 | 1 | 1 | 3 | 7 | Dead Code |
| 3 | No metrics infrastructure (log-based only) | 2 | 5 | 4 | 1 | 5 | 17 | Observability |
| 4 | 6 dead ownership stubs (SanctumAuthorityResolver, CmsOwnershipEnum, etc.) | 2 | 6 | 3 | 1 | 4 | 16 | Dead Code |
| 5 | `BoundaryGuard` defined but unreachable | 2 | 2 | 1 | 1 | 3 | 9 | Dead Code |
| 6 | No per-domain CSRF enforcement (telemetry-only) | 4 | 3 | 5 | 3 | 5 | 20 | Security |
| 7 | Session coupling prevents queue/CLI ownership resolution | 3 | 4 | 3 | 2 | 4 | 16 | Architecture |
| 8 | No fallback for unresolved ownership in middleware | 3 | 1 | 1 | 3 | 3 | 11 | Robustness |

## Testing

| # | Item | Severity | Scope | Effort | Risk | Awareness | Score | Category |
|---|------|----------|-------|--------|------|-----------|-------|----------|
| 9 | 14 pre-existing test failures | 3 | 5 | 8 | 3 | 5 | 24 | Test Debt |
| 10 | `EchoBroadcastOutputHandler` warnings in test output | 1 | 1 | 1 | 1 | 4 | 8 | Test Noise |
| 11 | Missing integration tests for full ownership pipeline | 2 | 3 | 4 | 2 | 4 | 15 | Coverage Gap |
| 12 | No property type tests for DTOs | 2 | 10 | 3 | 2 | 3 | 20 | Coverage Gap |

## Architecture Drift

| # | Item | Severity | Scope | Effort | Risk | Awareness | Score | Category |
|---|------|----------|-------|--------|------|-----------|-------|----------|
| 13 | Multi-cookie split not implemented (governance blocked) | 4 | 2 | 5 | 3 | 5 | 19 | Governance |
| 14 | BlogModule: 403 auth failures suggest policy drift | 3 | 1 | 2 | 2 | 3 | 11 | Policy Drift |
| 15 | ExceptionRenderingTest: response format contract mismatch | 3 | 1 | 1 | 3 | 4 | 12 | Contract Drift |
| 16 | StorefrontRuntimeTest: routing/URL config drift | 2 | 1 | 1 | 2 | 4 | 10 | Config Drift |
| 17 | Lead tests: delete is soft vs hard mismatch | 2 | 1 | 1 | 2 | 3 | 9 | Contract Drift |

## Code Quality

| # | Item | Severity | Scope | Effort | Risk | Awareness | Score | Category |
|---|------|----------|-------|--------|------|-----------|-------|----------|
| 18 | `MetricService` contains stubs with TODO comments | 1 | 1 | 2 | 1 | 2 | 7 | Stub Debt |
| 19 | PHPDoc missing on key ownership contracts | 1 | 3 | 1 | 1 | 3 | 9 | Documentation |
| 20 | Orphaned factory classes per module | 1 | 4 | 1 | 1 | 2 | 9 | Dead Code |

---

## Debt Heat Map

```
Score 20+   ████████████████   Critical (3 items)
Score 15-19 █████████████      High (5 items)
Score 10-14 █████████          Medium (4 items)
Score 1-9   ██████             Low (8 items)
```

### Critical Items (Score ≥ 20)

| # | Item | Score | Action Required |
|---|------|-------|-----------------|
| 6 | No per-domain CSRF enforcement | 20 | Governance waiver for cookie split |
| 9 | 14 pre-existing test failures | 24 | Fix or quarantine with `@group('known-failure')` |
| 12 | No property type tests for DTOs | 20 | Add PHPStan/Psalm or property-level tests |

### High Items (Score 15-19)

| # | Item | Score | Action Required |
|---|------|-------|-----------------|
| 3 | No metrics infrastructure | 17 | Evaluate Prometheus/StatsD integration |
| 4 | 6 dead ownership stubs | 16 | Remove or complete |
| 7 | Session coupling for ownership | 16 | Add API-token-based ownership resolution |
| 11 | Missing integration tests | 15 | Add 1-2 end-to-end ownership tests |
| 13 | Multi-cookie split blocked | 19 | Governance decision |

---

## Debt Reduction Roadmap

### Immediate (Wave 9 scope, 0-2 days)
1. Create this baseline (✅ done)
2. Write test-failure-analysis.md with per-failure remediation guidance (✅ done)
3. Categorize all failures by cause and effort (✅ done)

### Short-term (Next sprint, 2-5 days)
4. Fix ExceptionRenderingTest — align expected JSON with actual handler output
5. Fix BlogModuleTest auth failures — update permissions/policy
6. Fix StorefrontRuntimeTest canonical URL expectations
7. Fix LeadTest delete behavior (soft vs hard)
8. Clean up `TransitionalGuardResolver` unused constructor params

### Medium-term (1-2 sprints)
9. Add ownership DTO property type tests
10. Remove or implement the 6 dead ownership stubs
11. Evaluate metrics infrastructure (Prometheus counters)
12. Begin API-token-based ownership resolution for queue/CLI

### Long-term (Governance-dependent)
13. Obtain cookie split waiver and implement per-domain CSRF enforcement
14. Revisit BoundaryGuard implementation or removal
15. Complete MetricService implementation

---

## Debt Trend

| Wave | New Debt | Retired Debt | Net Change | Total Score |
|------|----------|--------------|------------|-------------|
| Wave 6 | Unknown | Unknown | Unknown | Unknown |
| Wave 7 | Unknown | Unknown | Unknown | Unknown |
| Wave 8 | 0 | 9 items (33→0) | -33 | 0 (CSRF ownership debt fully retired) |
| Wave 9 | Baseline | 0 | +Baseline | See items above |

**Key point:** Wave 8 retired 100% of its target debt (CSRF ownership pipeline). The items in this baseline are pre-existing and not attributable to recent work.
