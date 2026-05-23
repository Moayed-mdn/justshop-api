# DRAFT — Cross-Tenant Support Governance

**Status: DRAFT — NOT ACTIVATED**  
**Target Wave:** Wave 8+  
**Prerequisite:** Wave 6 impersonation governance, Wave 7 organization hierarchy

---

## Overview

This document describes the planned governance model for support agents operating across multiple tenants (stores/organizations). This is a DRAFT.

---

## Problem Statement

A support agent may need to assist users across multiple stores. The current model requires a separate impersonation request per store. Cross-tenant support governance would allow a support agent to operate across a defined set of tenants within a single governed session.

---

## Planned Architecture

### Cross-Tenant Support Session

A `SupportSession` model would track:
- `support_agent_id`
- `tenant_scope` (array of store IDs or organization IDs)
- `reason`
- `expires_at`
- `status`

### Authority Boundaries

Cross-tenant support MUST:
- Be explicitly scoped to a defined set of tenants
- Require explicit reason per tenant scope
- Emit telemetry for every cross-tenant action
- Expire automatically
- Be revocable

Cross-tenant support MUST NOT:
- Grant access to tenants outside the defined scope
- Persist beyond expiration
- Allow data modification without explicit per-action audit

---

## Governance Requirements

1. Cross-tenant scope must be explicitly approved
2. Every cross-tenant action must be logged with tenant context
3. Support agents cannot self-escalate cross-tenant scope
4. Platform admins must approve cross-tenant scope expansion

---

## Blockers

- Wave 6 impersonation governance must be fully activated
- Organization hierarchy must be implemented
- Per-tenant audit logging must be in place
