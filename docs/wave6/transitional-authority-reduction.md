# Transitional Authority Reduction

**Wave 6 — VERIFIED_COMPLETE**  
**Status:** Measurement active. Normalization in progress.

---

## Overview

Wave 6 measures and reduces transitional compatibility debt. Isolation must become normalized, not temporary.

---

## Transitional Debt Measurement

`App\Services\Auth\TransitionalDependencyAnalyzer` provides:

- `analyze()` — full dependency analysis
- `getTransitionalDebtScore()` — 0–100 score (lower is better)

### Debt Score Calculation

| Factor | Max Points | Condition |
|---|---|---|
| Web guard fallback enabled | 30 | `auth.guard_split.enforce.default = false` |
| Shared transitional routes | 25 | 5 points per route (max 5 routes) |
| Shadow-only mode | 20 | Shadow enabled but split not enabled |
| Legacy dependencies | 25 | 6 points per active legacy dependency |

---

## Current Transitional Domains

### 1. Shared Transitional Routes

Routes using `identity.route:shared_transitional,merchant,observe`:
- `GET /sanctum/csrf-cookie` — CSRF preparation endpoint

These routes cannot be fully isolated until the CSRF ownership model is finalized.

### 2. Legacy Platform Routes (Transitional)

Routes using `identity.route:platform,platform,enforce` WITHOUT explicit `platform.authority` middleware:
- `/v1/admin/stores/{store}/*` — merchant admin routes
- `/v1/admin/leads` — lead management
- `/v1/admin/cms/*` — CMS management

**Migration path:** Add `platform.authority:platform_admin` middleware to these route groups. Blocked by: admin route refactoring to use explicit platform authority.

### 3. Guard Split Not Enforced

`AUTH_GUARD_SPLIT_ENFORCE=false` — the web guard fallback is still active. This is the largest single debt item (30 points).

**Migration path:** Wave 5 completion — activate `AUTH_GUARD_SPLIT_ENFORCE=true` after telemetry proves readiness.

### 4. Shared User Provider

All guards use the `users` Eloquent provider. This is tracked in `provider-separation-readiness.md`.

---

## Normalization Candidates

Domains ready for normalization (telemetry proven):

| Domain | Status | Blocker |
|---|---|---|
| Merchant route enforcement | `identity.route:merchant_users,merchant,enforce` active | None — already enforced |
| Customer account enforcement | `identity.route:customer_account,customer,enforce` active | None — already enforced |
| Platform route enforcement | `identity.route:platform,platform,enforce` active | Legacy admin routes still transitional |
| Support route enforcement | `identity.route:support,platform,enforce` active | None — already enforced |

---

## Rollback Preservation

Even normalized domains remain reversible:
- Feature flags provide kill switches for all enforcement modes
- `auth.guard_split.enforce` can be disabled to revert to shadow mode
- `platform.authority.enabled` can be disabled to remove platform enforcement
- All telemetry is preserved regardless of enforcement state

---

## Debt Reduction Roadmap

| Wave | Action | Expected Debt Reduction |
|---|---|---|
| Wave 5 completion | Activate `AUTH_GUARD_SPLIT_ENFORCE=true` | -30 points |
| Wave 6 admin migration | Add `platform.authority` to admin routes | -5 points |
| Wave 7 | Provider separation preparation | -10 points |
| Wave 8 | CSRF ownership finalization | -5 points |
