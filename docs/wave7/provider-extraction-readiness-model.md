# Provider Extraction Readiness Model

**Wave 7 — VERIFIED_COMPLETE**  
**Status:** Audit Active. Extraction NOT activated.

---

## Overview

Wave 7 validates the platform's readiness for extracting authentication providers into separate services. This is a **PREPARATION ONLY** phase to identify blockers and seams.

---

## Readiness Audit

### Shared Assumptions (Audited)
- **Shared Model Assumptions:** Use of `App\Models\User` across all domains.
- **Shared Provider Assumptions:** Single Eloquent provider in `config/auth.php`.
- **Shared Notification Assumptions:** Common tables and templates.
- **Shared Password Broker Assumptions:** Single broker for all users.
- **Shared Auth Event Assumptions:** Generic events shared across domains.

### Blockers Identified
- **Hard Blockers:** Polymorphic relations to the `users` table.
- **Soft Blockers:** Common session driver and cookie domain.
- **Hidden Coupling:** `store_user` pivot table coupling domains.

---

## Governance Tools

- `ProviderGovernanceService` — The audit engine for extraction readiness.
- `architecture:provider-extraction-readiness-report` — Detailed readiness audit.

---

## Migration Seams
- `ActorContextEnum`
- `AuthDomainEnum`

---

## Audit Artifacts

- `provider-extraction-readiness-report.json` — Detailed extraction readiness audit.
