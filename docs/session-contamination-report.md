# Session Contamination Report

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 4

## Overview

This report tracks session contamination risks where a session authenticated for one actor domain is used to access routes belonging to another.

## 1. Contamination Hotspots

| Area | Risk Type | Detection | Severity |
|------|-----------|-----------|----------|
| Bootstrap | Merchant payload leakage to customer | `BootstrapDependencyProfiler` | High |
| Storefront | Merchant accessing checkout via customer session | `identity.route` telemetry | Medium |
| Admin | Customer attempting escalation to admin | `identity.route` enforcement | Low (Enforced) |

## 2. Telemetry Coverage

- **Shadow Mismatches**: `GuardSplitSimulationService` logs every instance where the authoritative `web` guard differs from the intended `merchant` or `customer` guard.
- **Contamination Signals**: `SessionGuardTelemetry` scores every request based on cross-domain indicators.

## 3. Current Statistics (Simulated)

- **Total Simulations**: 100% of requests
- **Mismatch Ratio**: 0% (Since all still use `web`)
- **Contamination Severity**: Measured via `severityScore` in `SessionGuardTelemetry`.
