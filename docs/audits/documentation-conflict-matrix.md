# Documentation Conflict Matrix

**Date:** 2026-05-25  
**Type:** Documentation conflict audit  
**Scope:** Auth, CMS, and wave/governance documentation overlaps

---

## Purpose

This file identifies documentation conflicts where two or more files:

- describe the same concept as if each is canonical
- disagree about current runtime behavior
- preserve older architecture states without enough warning
- mix current-state contracts with future-state or transitional wording

For each conflict, this matrix names:

- the conflicting files
- the conflict type
- the preferred canonical document
- the recommended cleanup action

---

## Conflict Severity

- **Critical:** Documents materially disagree about current runtime behavior.
- **High:** Multiple documents compete as the source of truth for the same concept.
- **Medium:** Historical or plan documents can be mistaken for the current contract.
- **Low:** Overlap exists, but the documents are mostly complementary.

---

## Conflict Matrix

| ID | Severity | Topic | Conflicting Docs | Conflict Summary | Canonical Winner | Recommended Action |
|----|----------|-------|------------------|------------------|------------------|--------------------|
| AUTH-001 | Resolved | Logout semantics | `docs/auth/sessions/actor-bound-session-ownership.md` vs `docs/auth/sessions/actor-owned-session-lifecycle.md` | **Resolved:** Verified that logout still invalidates the global session. Both docs updated. | `docs/auth/sessions/actor-owned-session-lifecycle.md` | Marked as resolved. |
| AUTH-002 | Resolved | Guard activation state | `docs/auth/sessions/runtime-guard-isolation.md` vs `docs/auth/governance/transitional-guard-resolution.md` vs `docs/auth/governance/auth-surface-classification.md` | **Resolved:** Verified that route-level guard selection is active for annotated routes. Docs updated. | `docs/auth/governance/transitional-guard-resolution.md` | Marked as resolved. |
| AUTH-003 | Resolved | Sanctum runtime authority | `docs/auth/governance/sanctum-authority-runtime-model.md` vs `docs/auth/governance/sanctum-authority-governance.md` vs `docs/auth/browser/browser-auth-coexistence.md` | **Resolved:** Verified multi-guard configuration in `sanctum.php` and active resolver. Docs updated. | `docs/auth/governance/sanctum-authority-governance.md` | Marked as resolved. |
| AUTH-010 | Resolved | Legacy platform middleware | `docs/AUTH_ROUTING.md` vs `docs/security/authority-boundaries-inventory.md` | **Resolved:** Updated `AUTH_ROUTING.md` to reflect that legacy routes rely on standard auth/role middleware. | `docs/security/authority-boundaries-inventory.md` | Marked as resolved. |
| AUTH-011 | Resolved | Policy bypass state | `docs/security/authority-boundaries-inventory.md` vs `app/Policies/TagPolicy.php` | **Resolved:** Inventory updated to reflect that policies have been normalized and `before()` bypasses removed. | Code (`app/Policies/TagPolicy.php`) | Marked as resolved. |
| AUTH-012 | Resolved | Membership semantic gap | `docs/wave2/membership-semantic-governance.md` vs `docs/wave6/enterprise-membership-authority-model.md` | **Resolved:** Added a banner to the Wave 2 doc pointing to the Wave 6 enterprise model. | `docs/wave6/enterprise-membership-authority-model.md` | Marked as resolved. |
| AUTH-004 | Resolved | Auth source of truth | `docs/auth/guides/onboarding-auth-store-lifecycle.md` vs `docs/AUTH_ROUTING.md` vs `docs/auth/README.md` | **Resolved:** Scope notes added to all docs pointing to `AUTH_ROUTING.md` as the canonical doctrine. | `docs/AUTH_ROUTING.md` | Marked as resolved. |
| AUTH-005 | Resolved | Identity doctrine | `docs/AUTH_ROUTING.md` vs `docs/auth/core/identity-context-model.md` | **Resolved:** `AUTH_ROUTING.md` confirmed as the primary doctrine via scope notes. | `docs/AUTH_ROUTING.md` | Marked as resolved. |
| AUTH-006 | Resolved | Customer auth boundary | `docs/AUTH_ROUTING.md` vs `docs/auth/core/customer-account-namespace.md` vs `docs/auth/core/storefront-account-bootstrap-contract.md` | **Resolved:** Hierarchy established via scope notes and purpose banners. | `docs/AUTH_ROUTING.md` | Marked as resolved. |
| AUTH-007 | Resolved | Runtime boundary posture | `docs/auth/core/merchant-customer-runtime-boundaries.md` vs `docs/auth/governance/auth-surface-classification.md` | **Resolved:** Clarified active enforcement vs transitional state in both docs. | `docs/auth/governance/auth-surface-classification.md` | Marked as resolved. |
| AUTH-008 | Resolved | Session and contamination model | `docs/auth/browser/browser-auth-coexistence.md` vs `docs/auth/reports/session-contamination-report.md` vs `docs/auth/sessions/runtime-guard-isolation.md` | **Resolved:** Verified active contamination detection and shadow simulation in code. | `docs/auth/browser/browser-auth-coexistence.md` | Marked as resolved. |
| CMS-001 | Resolved | Marketing split state | `docs/CMS_MARKETING_ARCHITECTURE.md` vs `docs/plans/marketing-pages-execution-plan.md` | **Resolved:** Target architecture vs rollout plan clarified in both docs. | `docs/CMS_MARKETING_ARCHITECTURE.md` | Marked as resolved. |
| CMS-002 | Resolved | Historical CMS terminology | `docs/CMS_MARKETING_ARCHITECTURE.md` vs `docs/architecture/cms-stabilization-report.md` vs `docs/architecture/history/cms-stabilization-summary.md` | **Resolved:** Historical labels added to legacy reports. | `docs/CMS_MARKETING_ARCHITECTURE.md` | Marked as resolved. |
| WAVE-001 | Resolved | Wave 6 duplicate coverage | `docs/wave6/wave6-enterprise-authority-foundations.md` vs `docs/architecture/history/wave6-enterprise-authority-foundations.md` | **Resolved:** Historical copy marked and kept for archival context. | `docs/wave6/wave6-enterprise-authority-foundations.md` | Marked as resolved. |
| AUTH-009 | Resolved | Session ownership overlap | `docs/auth/sessions/actor-bound-session-ownership.md` vs `docs/auth/sessions/guard-shadow-parity-system.md` | **Resolved:** Manageable overlap; cross-linked for clarity. | Keep both | Marked as resolved. |
| CMS-003 | Resolved | CMS ownership vs architecture | `docs/CMS_MARKETING_ARCHITECTURE.md` vs `docs/architecture/cms-domain-ownership.md` | **Resolved:** Hierarchy established with "sub-document" wording. | Keep both | Marked as resolved. |

---

## Cleanup Status

1. Critical auth runtime contradictions were reconciled in the live docs by a code-verified cleanup pass.
2. Competing auth source-of-truth wording was reduced so doctrine, guides, and focused references no longer claim the same scope.
3. Historical-labeling follow-up remains relevant for `CMS-002` and `WAVE-001`, but the highest-risk auth contradictions are no longer left unresolved in the current docs.

---

## Proposed Canonical Hierarchy

### Auth

- `docs/AUTH_ROUTING.md` - doctrine and routing authority
- `docs/auth/README.md` - navigation and category ownership
- `docs/auth/core/*.md` - narrow concept references
- `docs/auth/sessions/*.md` - session-specific implementation references
- `docs/auth/governance/*.md` - transitional and rollout governance
- `docs/auth/reports/*.md` - verification outputs, not architecture law
- `docs/auth/drafts/*.md` - future-state proposals only
- `docs/auth/guides/*.md` - consumption and onboarding guides

### CMS

- `docs/CMS_MARKETING_ARCHITECTURE.md` - current CMS architecture contract
- `docs/architecture/*.md` - supporting focused architecture references
- `docs/plans/*.md` - execution sequencing only
- `docs/architecture/history/*.md` - historical state only

### Waves

- `docs/wave*/` - active wave documentation
- `docs/architecture/history/` - archived architectural copies and summaries

---

## Notes

- This matrix records the conflicts that were identified before the 2026-05-25 code-verified cleanup pass.
- `AUTH-001`, `AUTH-002`, and `AUTH-003` were resolved in the live docs by aligning them to the current runtime: explicit route-level guard selection plus shared browser-session and shared logout constraints.
- `AUTH-004`, `AUTH-005`, and `AUTH-006` were reduced by clarifying doctrine-vs-guide-vs-reference scope.
- This file now serves mainly as audit history; the updated docs are the current source of truth.
