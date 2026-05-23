# DRAFT — Device Trust Governance

**Status: DRAFT — Infrastructure prepared, enforcement NOT activated**  
**Target Wave:** Wave 7+  
**Prerequisite:** Wave 6 multi-session governance, device tracking active

---

## Overview

This document describes the planned device trust governance model. The `device_trust_records` table and `DeviceTrustManager` service are already created in Wave 6. Enforcement is not yet activated.

---

## Current State

`DeviceTrustManager` tracks devices via `X-Device-ID` header:
- Creates/updates `device_trust_records` on each request
- `isTrusted(user, deviceId)` checks trust status
- Trust is NOT enforced — tracking only

---

## Planned Architecture

### Device Trust Levels

| Level | Description |
|---|---|
| `unknown` | Device seen but not evaluated |
| `trusted` | Explicitly trusted by user or platform |
| `suspicious` | Anomalous behavior detected |
| `blocked` | Explicitly blocked |

### Trust Establishment

1. User logs in from new device → `unknown` status
2. User confirms device (email/SMS) → `trusted` status
3. Anomaly detected → `suspicious` status, require re-authentication
4. Platform blocks device → `blocked` status, deny all access

### Actor-Bound Devices

Future: Devices can be bound to specific actor types:
- A device trusted for merchant access is NOT automatically trusted for customer access
- Support devices require additional verification

---

## Planned Enforcement

When `device_trust.enforcement.enabled = true`:
1. Check device trust status on authentication
2. `unknown` → allow but flag for trust establishment
3. `suspicious` → require step-up authentication
4. `blocked` → deny access

---

## Blockers

- Device ID header standardization across clients
- Trust establishment flow (email/SMS confirmation)
- Step-up authentication implementation
- Cross-device session governance
