# Impersonation Containment Model

**Wave 7 — VERIFIED_COMPLETE**  
**Status:** Active  
**Security Level:** CRITICAL

---

## Overview

Impersonation is treated as a **CONTROLLED TEMPORARY ESCALATION**. Wave 7 enforces strict containment boundaries to prevent authority corruption.

---

## Containment Rules

### 1. Actor-Domain Restrictions
- `SUPPORT_AGENT` can impersonate `MERCHANT` and `CUSTOMER`.
- `MERCHANT` cannot impersonate `CUSTOMER`.
- `CUSTOMER` cannot impersonate `MERCHANT`.

### 2. Escalation Prevention
- **No Nested Impersonation:** An actor cannot initiate impersonation while already impersonating.
- **No Silent Escalation:** Every impersonation activation is logged as a security event.

### 3. Session Governance
- Every impersonation session is tagged with a unique `correlation_id`.
- Session data is explicitly cleared upon termination.

---

## Governance Tools

- `ImpersonationGovernanceService` — Enforces validation and containment.
- `architecture:impersonation-audit-report` — Audits all impersonation events and detects violations.

---

## Security Telemetry

- `impersonation.started`
- `impersonation.ended`
- `impersonation.denied`
- `impersonation.escalation_blocked`

---

## Audit Artifacts

- `impersonation-audit-report.json` — Detailed audit of all impersonation activity.
