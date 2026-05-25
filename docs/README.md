# Documentation Index

This directory is the canonical home for project documentation.

## Core Contracts

- [ARCHITECTURE.md](./ARCHITECTURE.md) - Project-wide architecture rules and implementation contract.
- [AUTH_ROUTING.md](./AUTH_ROUTING.md) - Auth routing, identity context, and route ownership doctrine.
- [CMS_MARKETING_ARCHITECTURE.md](./CMS_MARKETING_ARCHITECTURE.md) - CMS and marketing architecture contract.
- [EXECUTION_GOVERNANCE.md](./EXECUTION_GOVERNANCE.md) - Execution and rollout governance rules.
- [OBSERVABILITY.md](./OBSERVABILITY.md) - Observability and telemetry guidance.
- [exception-system.md](./exception-system.md) - Exception and API error handling standards.

## Topical Guides

- `auth/guides/frontend-dashboard-auth-onboarding-guide.md` - Frontend integration contract for dashboard auth and onboarding.
- `auth/guides/onboarding-auth-store-lifecycle.md` - Backend lifecycle reference for auth, onboarding, and store flows.
- `production-hardening-checklist.md` - Production readiness checklist.
- `admin-api.md` - Admin API overview.

## Organized Areas

- `architecture/` - Supporting architecture documents and historical architecture notes.
- `auth/` - Canonical auth, identity, session, browser, governance, report, and draft docs.
- `audits/` - Read-only audits and verification reports.
- `plans/` - Execution plans and staged implementation planning docs.
- `reference/` - Technical reference material such as route inventories.
- `security/` - Security guarantees, audits, patterns, and test strategy.
- `runbooks/` - Operational incident and response procedures.
- `dashboards/` - Dashboard definitions and telemetry views.
- `alerts/` - Alert definitions and monitoring thresholds.
- `adr/` - Architecture decision records.
- `wave2/`, `wave6/`, `wave7/` - Wave-specific governance and rollout documentation.

## Recently Reorganized

- `audits/documentation-tenancy-audit-report.md`
- `architecture/history/cms-stabilization-summary.md`
- `architecture/history/wave6-enterprise-authority-foundations.md`
- `auth/README.md`
- `auth/core/`
- `auth/sessions/`
- `auth/browser/`
- `auth/governance/`
- `auth/reports/`
- `auth/drafts/`
- `auth/guides/`
- `plans/marketing-pages-execution-plan.md`
- `reference/routes.md`

## Conflict Resolution Notes

- Auth documentation is now grouped by topic so core contracts, reports, drafts, and guides no longer compete in one flat directory.
- The historical architecture copy of Wave 6 foundations now lives under `architecture/history/`; the active Wave 6 track remains under `wave6/`.
- Reports and drafts support decision-making but do not define the canonical architecture unless a core contract explicitly promotes them.
