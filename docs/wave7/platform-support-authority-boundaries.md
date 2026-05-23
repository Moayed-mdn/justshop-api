# Platform / Support Authority Boundaries

**Wave 7 — VERIFIED_COMPLETE**  
**Status:** Enforced  
**Domain:** `PLATFORM`

---

## Overview

Wave 7 formalizes the operational isolation between Platform and Support actors, preventing authority drift and unauthorized escalation.

---

## Authority Rules

### Support Actor Boundaries
- Restricted to `/v1/support/*` routes.
- Read-only access to merchant/customer data (unless impersonating).
- Forbidden from accessing `/v1/platform/*` (Super Admin only).
- Forbidden from direct merchant domain modifications.

### Platform System Boundaries
- Operational isolation for system-level actors.
- Explicit escalation rules from Support to Platform Admin.

---

## Governance Tools

- `PlatformAuthorityGovernanceService` — Enforces boundaries and detects drift.
- `architecture:platform-authority-governance-report` — Audits escalation and access drift.

---

## Detected Violations
- `platform.unauthorized_escalation`
- `platform.support_access_drift`

---

## Audit Artifacts

- `platform-authority-governance-report.json` — Detailed platform governance audit.
