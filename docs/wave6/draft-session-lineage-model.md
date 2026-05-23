# DRAFT — Session Lineage Model

**Status: DRAFT — Telemetry active, persistence NOT activated**  
**Target Wave:** Wave 7+  
**Prerequisite:** Wave 6 session governance, Wave 5 guard split enforced

---

## Overview

Session lineage tracks the full lifecycle and ancestry of authentication sessions. This enables forensic analysis of session contamination, impersonation chains, and abnormal coexistence patterns.

---

## Current State

`SessionLineageTracker` emits telemetry for:
- `session.lineage.created` — session creation with source and parent
- `session.lineage.transition` — domain transitions
- `session.lineage.terminated` — session end

These are log-only. No persistence layer exists yet.

---

## Planned Architecture

### Session Lineage Record

```sql
session_lineage (
  id,
  session_id,
  parent_session_id,
  auth_domain,
  actor_type,
  actor_id,
  creation_source,  -- login, impersonation, delegation, refresh
  created_at,
  terminated_at,
  termination_reason
)

session_lineage_events (
  id,
  session_id,
  event_type,  -- created, domain_transition, contamination_detected, terminated
  from_domain,
  to_domain,
  metadata,
  created_at
)
```

### Lineage Queries

- "Show all sessions created from impersonation of user X"
- "Show all domain transitions in the last 24 hours"
- "Show all sessions with contamination events"
- "Show session ancestry chain for session Y"

---

## Use Cases

1. **Impersonation audit** — trace all actions taken during an impersonation session
2. **Contamination forensics** — identify how a session became contaminated
3. **Compliance reporting** — full session history for audit requirements
4. **Anomaly investigation** — trace suspicious cross-device escalations

---

## Blockers

- Session persistence layer design
- Performance impact assessment (high-volume sessions)
- Retention policy (how long to keep lineage records)
- Privacy compliance (GDPR session data retention)
