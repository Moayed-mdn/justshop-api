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
| Web guard fallback debt present | 30 | `auth.guard_split.enforce.default = false` still leaves compatibility paths and analyzer debt, even though enforced routes now resolve explicit guards in middleware |
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

`AUTH_GUARD_SPLIT_ENFORCE=false` still registers transitional debt in the analyzer and leaves compatibility behavior in places like logout, but enforced request routes already resolve explicit guards and reject illegal fallback in `ApplyIdentityRouteContext`.

**Migration path:** Normalize the remaining flag-governed compatibility paths so the feature-flag state matches the already-active route middleware behavior.

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
- `auth.guard_split.enforce` still governs transitional compatibility paths, but request-route guard enforcement is already active in middleware
- `platform.authority.enabled` can be disabled to remove platform enforcement
- All telemetry is preserved regardless of enforcement state

---

## Debt Reduction Roadmap

| Wave | Action | Expected Debt Reduction |
|---|---|---|
| Guard-split normalization | Align feature-flag state with active middleware enforcement and remove remaining compatibility fallbacks | -30 points |
| Wave 6 admin migration | Add `platform.authority` to admin routes | -5 points |
| Wave 7 | Provider separation preparation | -10 points |
| Wave 8 | CSRF ownership finalization | -5 points |
