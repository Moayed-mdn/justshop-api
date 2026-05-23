# Browser Coexistence Report

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 4

## Overview

This report evaluates the safety of concurrent merchant and customer sessions within a single browser.

## 1. Concurrency Analysis

| Scenario | Behavior | Risk Level | Mitigation |
|----------|----------|------------|------------|
| Merchant + Customer Tabs | Shared session cookie. Last login wins authority. | High | Session tagging + contamination telemetry |
| Merchant Logout | Invalidates global session. Logs out customer too. | Low | Backward compatible; safe for now |
| CSRF Refresh | Shared token. Valid for both domains. | Medium | Domain-specific headers in CSRF response |

## 2. Collision Safety

- **Cookies**: No collisions. Both use `ecommerce_session`.
- **Tokens**: No collisions. Strictly session-based SPA auth.
- **Social Auth**: Return paths are domain-agnostic; `SocialAuthService` resolves user before login.

## 3. Recommendations for Wave 5

- Activate separate session cookies (`merchant_session`, `customer_session`).
- Enable hard guard enforcement.
