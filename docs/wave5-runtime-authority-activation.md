# Wave 5 Runtime Authority Activation

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 5

## Overview

Wave 5 completes the activation of runtime authority isolation in request middleware. This document summarizes the transition from shared auth state toward isolated domains, while noting that some feature-flagged compatibility paths remain for rollback and logout behavior.

## Activation Steps

1. **Guard Resolution**: enforced request routes now resolve explicit `merchant` or `customer` guards in middleware.
2. **Enforcement**: illegal fallback is rejected on enforced non-transitional routes.
3. **Session Tagging**: Enforced on all login/register endpoints.
4. **Logout Normalization**: Guard-aware telemetry is active, but logout still retains a compatibility fallback to `web` while `auth.guard_split.enabled` remains disabled by default.
5. **Route Classification**: 100% of auth routes classified and enforced.

## Rollback Plan

If critical failures are detected:
1. Set `auth.guard_split.enforce=false`.
2. Set `auth.guard_split.enabled=false`.
3. Verify compatibility paths are restored and review `auth.guard.illegal_fallback_detected`, `auth.guard.split_mismatch_detected`, and `guard.shadow.mismatch_detected` telemetry.
