# Multi-Session Authority Governance

**Wave 7 — VERIFIED_COMPLETE**  
**Status:** Active  
**Domain:** `GLOBAL`

---

## Overview

Wave 7 governs the simultaneous coexistence of multiple authorities in the same browser or device, ensuring strict isolation between actor domains.

---

## Coexistence Rules

### Safe Coexistence
- Different actor domains in separate sessions.
- Multiple tabs sharing the same actor context.

### Forbidden Coexistence (Collisions)
- `CUSTOMER` domain + `MERCHANT` actor context.
- `MERCHANT` domain + `CUSTOMER` actor context.
- `SUPPORT_AGENT` context + `NON-PLATFORM` domain (outside of active impersonation).

---

## Governance Tools

- `MultiSessionGovernanceService` — Detects concurrent sessions and collisions.
- `architecture:session-coexistence-report` — Reports on authority collisions and contamination events.

---

## Anomaly Detection

- `session.coexistence.abnormal_detected` — Logged when invalid combinations are found.
- `shared-device authority anomalies` — Tracked in the governance report.

---

## Audit Artifacts

- `session-coexistence-report.json` — Detailed report on multi-session safety.
