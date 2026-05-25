# Browser Auth Coexistence Verification

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 5

## Overview

Concurrent merchant and customer authentication within a single browser session is only **partially hardened** today. Route ownership checks and session tagging are active, but cookie/session persistence and logout are still shared.

## Verification Results

| Scenario | Result | Status |
|----------|--------|--------|
| Parallel Tabs | Mixed. Route-domain checks can deny contaminated requests, but both tabs still contend for the same shared browser session cookie. | VERIFIED |
| Cross-Tab Logout| Shared logout. `LogoutUserAction` still falls back to the `web` guard by default and `SessionOwnershipManager::invalidate()` invalidates the whole Laravel session. | VERIFIED |
| CSRF Safety | Transitional. Shared CSRF bootstrap still exists; ownership metadata is added, but cookie isolation is not finished. | VERIFIED |

## Contamination Control

The "Last Login Wins" risk is only partially mitigated. If a shared browser session is overwritten by another actor login, the middleware can detect the domain mismatch and terminate access to the wrong route family, but it does not create independent browser cookies or per-actor logout isolation yet.
