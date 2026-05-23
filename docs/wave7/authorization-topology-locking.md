# Authorization Topology Locking

**Wave 7 — VERIFIED_COMPLETE**  
**Status:** Active  
**Enforcement:** CI-FAIL ON DRIFT

---

## Overview

Wave 7 freezes the authorization architecture to prevent "architecture drift". Any new authorization patterns or hidden bypasses are detected and blocked.

---

## Locking Rules

### 1. Forbidden Patterns
- **Controller-local authorization:** Manual `Gate::` calls in controllers.
- **Action-layer authorization:** Authorization logic inside Action classes.
- **Repository-layer authorization:** Authorization logic inside Repositories.
- **Middleware-only authorization:** Routes relying ONLY on middleware without policy invocation.

### 2. Approved Pattern
- All authorization MUST go through registered Policies using the `PolicyOwnershipRegistry`.

---

## Governance Tools

- `AuthorizationTopologyLocker` — Detects patterns and calculates drift.
- `architecture:authorization-topology-report` — Generates the topology audit.

---

## CI Enforcement
The `architecture:authorization-topology-report --fail-on-drift` command is integrated into the CI pipeline to block any PR that introduces undocumented authorization patterns.

---

## Audit Artifacts

- `authorization-topology-report.json` — Detailed topology stability report.
