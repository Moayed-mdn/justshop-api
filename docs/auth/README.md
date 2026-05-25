# Auth Documentation Index

This directory is the canonical home for authentication, identity, session, browser coexistence, and authority-governance documentation.

## Canonical Reading Order

1. [../AUTH_ROUTING.md](../AUTH_ROUTING.md) - Primary route ownership and identity routing contract.
2. [core/identity-context-model.md](./core/identity-context-model.md) - Authoritative actor classification model.
3. [core/merchant-customer-runtime-boundaries.md](./core/merchant-customer-runtime-boundaries.md) - Runtime separation between merchant and customer surfaces.
4. [core/storefront-account-bootstrap-contract.md](./core/storefront-account-bootstrap-contract.md) - Storefront-safe bootstrap contract.
5. [sessions/actor-bound-session-ownership.md](./sessions/actor-bound-session-ownership.md) - Session ownership tagging model.
6. [governance/sanctum-authority-governance.md](./governance/sanctum-authority-governance.md) - Sanctum governance and transitional guard posture.

## Topic Areas

- `core/` - Canonical identity, namespace, bootstrap, and runtime-boundary docs.
- `sessions/` - Session ownership, session lifecycle, and guard-isolation preparation.
- `browser/` - Browser coexistence behavior and validation docs.
- `governance/` - Authority governance, transitional rules, rollback, and dependency mapping.
- `reports/` - Verification artifacts and report-style documents that support, but do not define, architecture.
- `drafts/` - Future-state proposals that are explicitly non-canonical.
- `guides/` - Integration and lifecycle guides for frontend and backend consumers.

## Canonical Vs Supporting

- Canonical rules live in `AUTH_ROUTING.md`, `core/`, `sessions/`, and selected `governance/` docs.
- Reports in `reports/` validate behavior and document incidents or verification outcomes.
- Drafts in `drafts/` are exploratory and must not be treated as active architecture commitments.
- Guides in `guides/` explain how to consume the current system, but they do not replace the core contracts.
