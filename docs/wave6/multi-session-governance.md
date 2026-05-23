# Multi-Session Governance

**Wave 6 — VERIFIED_COMPLETE**  
**Status:** Governance model active. Device lineage in preparation.

---

## Overview

Wave 6 prepares safe coexistence for multiple concurrent sessions across different actor domains, browser tabs, and devices — without contamination.

---

## Session Coexistence Model

`App\Services\Auth\MultiSessionGovernanceService` governs:

### Safe Coexistence

The following session combinations are SAFE:
- Merchant session in browser A + Customer session in browser B (different browsers)
- Multiple merchant tabs in same browser (same session, same domain)
- Support session isolated from merchant/customer sessions

### Forbidden Combinations

The following combinations are IMPOSSIBLE and trigger anomaly detection:

| Combination | Risk Level | Detection |
|---|---|---|
| `auth_domain=customer` + `actor_type=merchant` | `high_impossible_combination` | `session.coexistence.abnormal_detected` |
| `auth_domain=merchant` + `actor_type=customer` | `high_impossible_combination` | `session.coexistence.abnormal_detected` |
| `actor_type=support_agent` + `auth_domain≠platform` | `medium_impersonation_remnant` | `session.coexistence.abnormal_detected` |

---

## Anomaly Detection

`detectAbnormalCoexistence(request)` checks session metadata and emits `session.coexistence.abnormal_detected` at `error` level when impossible combinations are detected.

Detection data includes:
- `risk_level`
- `session_id`
- `auth_domain`
- `actor_type`
- `actor_id`
- `ip`
- `user_agent`

---

## Device Session Ownership

`getDeviceSessionOwnership(request)` returns:
- `device_fingerprint` — SHA-256 of IP + User-Agent
- `session_id`
- `ip`
- `user_agent`

`App\Services\Auth\DeviceTrustManager` tracks device trust records in `device_trust_records` table.

---

## Session Lineage Tracker

`App\Services\Auth\SessionLineageTracker` prepares session lineage tracking:

- `trackSessionCreation(request, metadata)` — logs session creation with source and parent
- `trackSessionTransition(request, fromDomain, toDomain)` — logs domain transitions
- `trackSessionTermination(request, reason)` — logs session end

**Status:** Telemetry-only. Persistence not yet activated.

---

## Device Trust Records

`device_trust_records` table:

| Column | Description |
|---|---|
| `user_id` | FK → users |
| `device_id` | Device identifier (from `X-Device-ID` header) |
| `device_type` | User-Agent string |
| `ip_address` | Last known IP |
| `last_active_at` | Last activity timestamp |
| `is_trusted` | Whether device is explicitly trusted |
| `metadata` | JSON metadata |

---

## Concurrent Session Governance

`getConcurrentSessionGovernance(userId)` returns governance metadata.

**Current state:** Concurrent session tracking is NOT yet activated. The infrastructure is prepared for future enforcement.

---

## Feature Flag

`features.multi_session.governance.enabled` (default: `true`) enables coexistence detection and anomaly telemetry.

---

## Rollback

Disabling `multi_session.governance.enabled` disables anomaly detection telemetry. No functional impact on authentication.
