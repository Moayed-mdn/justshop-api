# Browser Auth Coexistence

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 4

## Overview

This document validates the safety and behavior of concurrent authentication states within a single browser environment.

## Coexistence Model

| Domain | Authority | Cookie | Lifecycle |
|--------|-----------|--------|-----------|
| Merchant | Shared | `ecommerce_session` | Global |
| Customer | Shared | `ecommerce_session` | Global |

## Safety Controls

1. **Session Tagging**: Prevents silent authority drift by explicitly recording the intended domain in the session.
2. **Contamination Telemetry**: Logs warnings if a user authenticated as a `merchant` attempts to access `customer` routes (and vice-versa).
3. **CSRF Domain Headers**: The `/sanctum/csrf-cookie` response now includes `X-Session-Auth-Domain` to inform the frontend of the resolved context.

## Known Risks

- Simultaneous multi-tab login (Merchant in Tab A, Customer in Tab B) results in the second login taking authority over the shared session cookie. This is a known transitional state mitigated by Wave 4 telemetry.
