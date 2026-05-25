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
| AUTH-001 | Critical | Logout semantics | `docs/auth/sessions/actor-bound-session-ownership.md` vs `docs/auth/sessions/actor-owned-session-lifecycle.md` | One document says logout still invalidates the global session for compatibility, while the other says logout is already guard-aware and avoids global invalidation. | `docs/auth/sessions/actor-bound-session-ownership.md` unless code proves otherwise | Verify runtime behavior against implementation, then merge or demote the losing doc. |
| AUTH-002 | Critical | Guard activation state | `docs/auth/sessions/runtime-guard-isolation.md` vs `docs/auth/governance/transitional-guard-resolution.md` vs `docs/auth/governance/auth-surface-classification.md` | `runtime-guard-isolation.md` reads as if explicit actor guards are active authority, while the governance docs still describe `web` as the current shared authority with transitional fallback. | `docs/auth/governance/transitional-guard-resolution.md` plus `docs/auth/governance/auth-surface-classification.md` for current state | Reframe `runtime-guard-isolation.md` as future-state or conditional activation, or move it to drafts/history if not currently active. |
| AUTH-003 | Critical | Sanctum runtime authority | `docs/auth/governance/sanctum-authority-runtime-model.md` vs `docs/auth/governance/sanctum-authority-governance.md` vs `docs/auth/browser/browser-auth-coexistence.md` | `sanctum-authority-runtime-model.md` reads as if Sanctum already acts as explicit actor-domain authority, while the other docs still describe a shared-cookie transitional model. | `docs/auth/governance/sanctum-authority-governance.md` for current state | Rewrite `sanctum-authority-runtime-model.md` to clearly distinguish current state from future target state, or merge it into governance. |
| AUTH-004 | High | Auth source of truth | `docs/auth/guides/onboarding-auth-store-lifecycle.md` vs `docs/AUTH_ROUTING.md` vs `docs/auth/README.md` | `onboarding-auth-store-lifecycle.md` claims to be the single canonical backend source of truth, but `AUTH_ROUTING.md` defines the auth doctrine and `docs/auth/README.md` defines the canonical reading order. | `docs/AUTH_ROUTING.md` for doctrine, `docs/auth/README.md` for navigation | Remove the "single canonical source of truth" claim from the guide and describe it as an implementation guide or architecture reference. |
| AUTH-005 | High | Identity doctrine | `docs/AUTH_ROUTING.md` vs `docs/auth/core/identity-context-model.md` | Both define actor types, route-domain enforcement, and telemetry, so readers can treat either file as the main identity contract. | `docs/AUTH_ROUTING.md` | Narrow `identity-context-model.md` to model/reference scope and add a banner pointing to `AUTH_ROUTING.md` as doctrine. |
| AUTH-006 | High | Customer auth boundary | `docs/AUTH_ROUTING.md` vs `docs/auth/core/customer-account-namespace.md` vs `docs/auth/core/storefront-account-bootstrap-contract.md` | All three define customer namespace isolation and customer-safe behavior; the scope boundaries between route doctrine, namespace surface, and bootstrap payload are not explicit enough. | `docs/AUTH_ROUTING.md` for routing, `docs/auth/core/storefront-account-bootstrap-contract.md` for payload | Add purpose banners to the two narrower docs and remove repeated doctrine text from them. |
| AUTH-007 | High | Runtime boundary posture | `docs/auth/core/merchant-customer-runtime-boundaries.md` vs `docs/auth/governance/auth-surface-classification.md` | One file presents merchant/customer boundaries as hardened active enforcement, while the other still describes surfaces as shared-authority transitional state. | `docs/auth/governance/auth-surface-classification.md` for current state | Clarify whether `merchant-customer-runtime-boundaries.md` is describing current enforcement, intended target, or partial enforcement only. |
| AUTH-008 | Medium | Session and contamination model | `docs/auth/browser/browser-auth-coexistence.md` vs `docs/auth/reports/session-contamination-report.md` vs `docs/auth/sessions/runtime-guard-isolation.md` | The browser and report docs still describe the shared `web` authority model, while `runtime-guard-isolation.md` implies the split is already active. | `docs/auth/browser/browser-auth-coexistence.md` plus `docs/auth/reports/session-contamination-report.md` for observed behavior | Mark `runtime-guard-isolation.md` as conditional/future unless activation is verified in code. |
| CMS-001 | Medium | Marketing split state | `docs/CMS_MARKETING_ARCHITECTURE.md` vs `docs/plans/marketing-pages-execution-plan.md` | The architecture doc presents the platform/store marketing split as the architecture contract, while the plan frames the same split as an active migration from the legacy unified path. | `docs/CMS_MARKETING_ARCHITECTURE.md` for target architecture, `docs/plans/marketing-pages-execution-plan.md` for rollout plan | Add a short "current runtime vs target architecture" note in both docs to align timing language. |
| CMS-002 | Medium | Historical CMS terminology | `docs/CMS_MARKETING_ARCHITECTURE.md` vs `docs/architecture/cms-stabilization-report.md` vs `docs/architecture/history/cms-stabilization-summary.md` | The historical docs preserve older policy names and prior stabilization framing, which can still be mistaken for the active contract if opened directly. | `docs/CMS_MARKETING_ARCHITECTURE.md` | Keep the historical docs, but add stronger superseded-by banners and avoid current-tense wording in summaries. |
| WAVE-001 | Medium | Wave 6 duplicate coverage | `docs/wave6/wave6-enterprise-authority-foundations.md` vs `docs/architecture/history/wave6-enterprise-authority-foundations.md` | Two copies of the same Wave 6 topic exist; the history copy is now marked, but duplication still exists physically. | `docs/wave6/wave6-enterprise-authority-foundations.md` | Keep the history copy only if needed for archival context; otherwise replace it with a short pointer file. |
| AUTH-009 | Low | Session ownership overlap | `docs/auth/sessions/actor-bound-session-ownership.md` vs `docs/auth/sessions/guard-shadow-parity-system.md` | Both discuss session/guard preparation, but one focuses on ownership tagging and the other on shadow evaluation. The overlap is manageable but still repetitive. | Keep both | Trim repeated background sections and cross-link the two docs. |
| CMS-003 | Low | CMS ownership vs architecture | `docs/CMS_MARKETING_ARCHITECTURE.md` vs `docs/architecture/cms-domain-ownership.md` | Both cover ownership, but one is the full CMS architecture contract and the other is a focused ownership reference. | Keep both | No merge required; add "sub-document" wording to `cms-domain-ownership.md`. |

---

## Immediate Cleanup Priorities

1. Resolve contradictory auth runtime claims:
   - `AUTH-001`
   - `AUTH-002`
   - `AUTH-003`
2. Remove competing "source of truth" claims:
   - `AUTH-004`
   - `AUTH-005`
   - `AUTH-006`
3. Strengthen historical labeling:
   - `CMS-002`
   - `WAVE-001`

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

- This matrix does not yet prove which document is correct in every contradiction.
- Where behavior conflicts exist, the final winner should be confirmed against the codebase before removing or rewriting documents.
- The most likely next step is a code-verified conflict resolution pass for `AUTH-001`, `AUTH-002`, and `AUTH-003`.
