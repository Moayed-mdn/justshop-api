# Wave 5 Runtime Authority Activation

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 5

## Overview

Wave 5 completes the activation of runtime authority isolation. This document summarizes the final transition from shared auth state to isolated domains.

## Activation Steps

1. **Guard Split**: `auth.guard_split.enabled` activated.
2. **Enforcement**: `auth.guard_split.enforce` activated.
3. **Session Tagging**: Enforced on all login/register endpoints.
4. **Logout Normalization**: Guard-aware logout enabled.
5. **Route Classification**: 100% of auth routes classified and enforced.

## Rollback Plan

If critical failures are detected:
1. Set `auth.guard_split.enforce=false`.
2. Set `auth.guard_split.enabled=false`.
3. System reverts to shared `web` guard authority with shadow telemetry.
