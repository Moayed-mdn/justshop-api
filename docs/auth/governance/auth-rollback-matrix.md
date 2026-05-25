# Auth Authority Rollback Matrix

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 4

## Overview

This matrix defines the rollback procedures for various authentication and authority changes introduced in Wave 4.

## 1. Rollback Procedures

| Component | Trigger | Action | Impact |
|-----------|---------|--------|--------|
| Guard Split | High mismatch ratio | Set `AUTH_GUARD_SPLIT_SHADOW=false` | Disables telemetry; no functional change |
| Session Tagging| Session corruption | Revert `SessionOwnershipManager` usage | Sessions lose actor context; legacy behavior restored |
| Route Enforcement| Unexpected lockouts | Set route enforcement to `observe` | Restores access; restores telemetry |

## 2. Failure Scenarios

### Scenario A: Global Session Corruption
- **Indicator**: Mass 419 errors or session data loss.
- **Rollback**: Disable all session tagging and clear Redis/Cookie session store.

### Scenario B: Guard Split Mismatch
- **Indicator**: High volume of `auth.guard.split_mismatch_detected` logs.
- **Rollback**: Adjust `TransitionalGuardResolver` logic to align with legacy assumptions.

## 3. Dark-Launch Rollback Plan

1. Disable `auth.guard_split.shadow` flag.
2. Disable `auth.guard_split.enabled` flag.
3. Verify `web` guard remains authoritative via telemetry.
