# Policy Governance Enforcement

**Wave 7 — VERIFIED_COMPLETE**  
**Status:** Active  
**Enforcement Mode:** STRICT

---

## Overview

Wave 7 introduces strict enforcement of the `PolicyOwnershipRegistry`. It is no longer sufficient for policies to merely exist; they must be registered, actor-aware, and free of implicit escalation paths.

---

## Governance Rules

### 1. Mandatory Registration
All policies in `app/Policies` MUST be registered in `PolicyOwnershipRegistry` (via `AppServiceProvider`). Unregistered policies are detected by `architecture:policy-governance-report`.

### 2. Actor-Aware Logic
Policies MUST NOT be actor-blind. Every policy decision must explicitly check the actor context (Merchant, Customer, or Platform) as declared in the registry.

### 3. Escalation Control
Implicit escalation paths in `before()` methods are forbidden. Only `SUPER_ADMIN` bypasses are allowed unless explicitly registered as an escalation rule.

### 4. Direct Gate Usage
Direct `Gate::allowIf` or `Gate::authorize` calls in controllers are flagged if they bypass the policy registry domain ownership.

---

## Enforcement Tools

- `architecture:policy-governance-report` — Detects violations and registry drift.
- `PolicyGovernanceEnforcer` — The engine behind the audit.

---

## Audit Artifacts

- `policy-governance-report.json` — Detailed report of all policy violations.
