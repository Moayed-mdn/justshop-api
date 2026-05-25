# Marketing Pages Execution Plan

## Scope
- This document is a strict execution plan only.
- This document covers backend migration from the legacy unified marketing pages flow to the new split architecture.
- This document prioritizes platform marketing pages first.
- This document defers full store marketing frontend integration until later.

## Constraints
- Do not require a large frontend refactor.
- Allow only small frontend changes, such as API route updates and small request/response wiring updates.
- Preserve the current frontend rendering shape for platform marketing pages as much as possible.
- Avoid introducing store marketing frontend contracts before the store frontend is ready.
- Do not continue building new features on the legacy unified `MarketingPage` path unless required for temporary compatibility.

## Goals
- Make `platform_marketing_pages` the source of truth for platform marketing content.
- Deliver backend CRUD for platform marketing pages.
- Keep public platform marketing page consumption stable for the current frontend.
- Prepare store marketing pages as backend foundation only.
- Retire the legacy platform marketing flow after validation.

## Non-Goals
- No large frontend redesign.
- No full store marketing frontend rollout.
- No redesign of the current platform marketing page rendering system.
- No immediate removal of the legacy unified marketing flow before compatibility is verified.

## Decision Rules
- Platform marketing pages are the first delivery target.
- Store marketing pages remain backend-only until frontend requirements are defined.
- Public platform marketing responses must remain compatible with the current frontend contract wherever possible.
- Backend compatibility layers are preferred over frontend rewrites.
- Breaking changes to payload shape are not allowed unless explicitly approved.

## Phase 0: Baseline And Contract Freeze
### Objective
- Freeze the current platform frontend contract before backend migration.

### Tasks
- Inventory the currently used public platform marketing endpoints.
- Inventory the currently used response payload fields for platform marketing pages.
- Inventory the current SEO-related response fields consumed by the frontend.
- Inventory slug behavior, locale behavior, and publish-state behavior.
- Record any frontend assumptions about page identifiers, sections, and page shape.
- Mark the legacy `MarketingPage`-based public contract as the temporary compatibility contract.

### Deliverables
- A written contract snapshot for current platform marketing page reads.
- A list of fields that must remain stable during migration.
- A list of fields that may change with minor frontend updates if necessary.

### Exit Criteria
- The team has a clear list of frontend-sensitive fields and routes.
- The team agrees that the backend will preserve these fields during migration.

## Phase 1: Platform Backend Foundation
### Objective
- Introduce a complete platform-only backend path based on `platform_marketing_pages`.

### Tasks
- Create or complete dedicated platform repositories/services/actions for:
  - list
  - show
  - create
  - update
  - delete
  - publish
- Create or complete request validation dedicated to platform marketing pages.
- Ensure slug validation is correct for localized platform slugs.
- Ensure publish, draft, and scheduled states are handled consistently.
- Ensure section/content storage is supported by the platform table/model structure.
- Register and wire platform-specific authorization correctly.
- Remove dependency on the legacy unified `MarketingPage` path for new platform admin writes.

### API Plan
- Add dedicated admin platform endpoints:
  - `GET /api/v1/admin/cms/platform/pages`
  - `POST /api/v1/admin/cms/platform/pages`
  - `GET /api/v1/admin/cms/platform/pages/{id}`
  - `PUT /api/v1/admin/cms/platform/pages/{id}`
  - `DELETE /api/v1/admin/cms/platform/pages/{id}`
  - `POST /api/v1/admin/cms/platform/pages/{id}/publish`

### Deliverables
- Platform admin CRUD endpoints backed by `platform_marketing_pages`.
- Platform policy registration and permission enforcement.
- Platform request validation and resource formatting.

### Exit Criteria
- Platform pages can be created and managed entirely without the legacy unified `MarketingPage` admin flow.

## Phase 2: Public Read Compatibility Layer
### Objective
- Keep the current platform frontend working with minimal or no rendering changes.

### Tasks
- Implement a public read path that resolves platform pages from `platform_marketing_pages`.
- Keep the current public response shape compatible with the frontend contract from Phase 0.
- Introduce a backend response adapter if the new platform entity shape differs from the legacy shape.
- Keep locale fallback behavior unchanged unless a small approved frontend adjustment is required.
- Keep slug lookup behavior unchanged unless a route-only frontend update is approved.

### Compatibility Rule
- Prefer backend transformation over frontend refactoring.

### Routing Rule
- Use one of the following approaches:
  - keep the current public route unchanged and swap its backend source, or
  - introduce a small route change and keep the payload shape unchanged

### Deliverables
- Public platform page reads backed by the new platform table.
- Stable frontend-facing payload compatibility.

### Exit Criteria
- The current frontend can consume public platform marketing pages with no major code changes.

## Phase 3: Temporary Legacy Fallback
### Objective
- De-risk rollout while migrating existing platform content.

### Tasks
- Implement fallback resolution order for public platform reads:
  - primary source: `platform_marketing_pages`
  - temporary fallback: legacy unified `MarketingPage`
- Add logging or telemetry for fallback hits.
- Use the fallback only for public reads during migration.
- Do not allow new platform admin writes to continue targeting the legacy unified marketing table.

### Deliverables
- Temporary dual-read public compatibility layer.
- Visibility into unresolved or fallback-served slugs.

### Exit Criteria
- Fallback is active only as a migration safety net.

## Phase 4: Platform Data Migration
### Objective
- Move existing platform content from the legacy unified table into the new platform table.

### Tasks
- Map legacy platform page fields to the new platform schema.
- Migrate localized slugs, titles, excerpts, content/sections, SEO metadata, status, timestamps, and audit fields.
- Identify how legacy `sections` data maps into the new platform content structure.
- Preserve page publish state and publication dates.
- Preserve localized slug behavior.
- Verify each migrated slug resolves correctly in public reads.
- Verify each migrated page can be read and managed through the new platform backend path.

### Migration Rule
- Run migration in an idempotent way if possible.

### Verification Checklist
- Every known platform slug resolves.
- SEO fields still resolve.
- Localized routes still resolve.
- Published pages remain published.
- Draft and scheduled pages preserve state.

### Deliverables
- Migrated platform data in `platform_marketing_pages`.
- Validation report for migrated slugs and pages.

### Exit Criteria
- All active platform content exists and resolves from the new platform source.

## Phase 5: Switch Primary Public Source
### Objective
- Make `platform_marketing_pages` the primary and intended runtime source for public platform marketing reads.

### Tasks
- Switch the public marketing controller/repository logic to use the new platform source by default.
- Keep the fallback enabled temporarily for safety.
- Update platform SEO resolution to read from the new platform entity.
- Update platform sitemap generation to read from the new platform entity.
- Confirm the frontend still works with the compatibility payload.

### Deliverables
- Public platform reads, sitemap, and SEO all backed by the platform-specific source.

### Exit Criteria
- All public platform reads run correctly from the new platform source.

## Phase 6: Remove Legacy Platform Dependency
### Objective
- Retire the legacy unified marketing path for platform pages after validation.

### Tasks
- Remove fallback reads after migration validation completes.
- Stop using legacy platform admin controllers/actions/resources for platform pages.
- Remove or deprecate legacy platform-specific logic in the unified repository/controller flow.
- Keep removal scoped to platform usage only if the legacy unified model still serves some other temporary purpose.
- Update internal documentation to mark the old path deprecated or removed.

### Deliverables
- Platform flow no longer depends on the legacy unified `MarketingPage` runtime path.

### Exit Criteria
- Platform reads and writes operate entirely on the new platform architecture.

## Phase 7: Store Backend Foundation Only
### Objective
- Prepare store marketing pages without forcing frontend work.

### Tasks
- Add or complete the store marketing page model layer.
- Add or complete store repositories/services/actions.
- Add or complete tenant-aware slug validation scoped by `store_id`.
- Register and wire store policies and permissions.
- Define store admin endpoints if needed for internal testing only.
- Keep store public routes disabled or feature-flagged until the frontend is ready.
- Keep store response contracts provisional until frontend requirements are finalized.

### API Plan
- If internal backend testing is needed, use dedicated store-scoped admin routes such as:
  - `GET /api/v1/admin/stores/{store}/cms/pages`
  - `POST /api/v1/admin/stores/{store}/cms/pages`
  - `GET /api/v1/admin/stores/{store}/cms/pages/{id}`
  - `PUT /api/v1/admin/stores/{store}/cms/pages/{id}`
  - `DELETE /api/v1/admin/stores/{store}/cms/pages/{id}`
  - `POST /api/v1/admin/stores/{store}/cms/pages/{id}/publish`

### Deliverables
- Store marketing backend foundation.
- No required frontend changes.

### Exit Criteria
- Store marketing is technically ready for future integration but not yet part of the public frontend contract.

## Phase 8: Frontend Change Policy
### Objective
- Limit frontend work to the smallest possible surface.

### Allowed Frontend Changes
- API route path updates.
- Small request parameter updates.
- Small response field wiring updates if the backend cannot preserve exact legacy names.
- Small admin integration updates for platform CRUD.

### Disallowed Frontend Changes
- Rewriting marketing page rendering architecture.
- Rebuilding platform marketing components around a new data model.
- Introducing store marketing rendering before its backend contract is finalized.

### Rule
- If a backend compatibility layer can avoid a frontend rewrite, use the backend compatibility layer.

## Phase 9: Testing Plan
### Objective
- Validate migration and compatibility without broad speculative testing.

### Required Tests
- Platform public page fetch by slug.
- Platform locale fallback behavior.
- Platform draft/published/scheduled visibility rules.
- Platform admin CRUD flow.
- Platform publish action.
- Platform slug uniqueness validation.
- Platform sitemap entry generation.
- Platform SEO resolution.
- Migration validation for existing slugs.

### Deferred Tests
- Store public rendering tests.
- Store frontend integration tests.

### Exit Criteria
- Platform regression risk is controlled.
- Store backend foundation is validated at the API and policy layer only.

## Phase 10: Rollout Sequence
### Step 1
- Freeze the current frontend contract for platform marketing reads.

### Step 2
- Build platform-specific admin CRUD on the new platform architecture.

### Step 3
- Add the public read compatibility layer for platform pages.

### Step 4
- Migrate legacy platform content into `platform_marketing_pages`.

### Step 5
- Switch public platform read, sitemap, and SEO to the new source.

### Step 6
- Validate production-equivalent behavior with the current frontend.

### Step 7
- Remove legacy platform dependency after validation.

### Step 8
- Prepare store backend foundation only.

## File-Level Work Buckets
### Platform Routing
- Add new admin route files or route groups for platform marketing pages.
- Update public marketing routes only if a small route change is necessary.

### Platform Controllers
- Add platform admin controller(s).
- Update or replace public marketing controller behavior for platform reads.

### Platform Domain Layer
- Add or complete platform actions, DTOs, repositories, requests, and resources.

### Auth Layer
- Register platform marketing policy mapping.
- Confirm platform permission enforcement is active.

### SEO And Sitemap
- Update sitemap repository/service usage for platform pages.
- Update SEO resolution inputs for platform pages.

### Data Migration
- Add migration script, command, or seeder-style migration utility for legacy platform data transfer.

### Store Foundation
- Add missing store marketing model/repository/action/policy wiring.

## Risks
- Payload drift between legacy and new platform responses.
- Slug resolution mismatch during migration.
- SEO field regression after switching sources.
- Section/content structure mismatch between legacy and new storage models.
- Policy registration gaps preventing admin CRUD access.
- Store scope being implemented too early and causing avoidable complexity.

## Mitigations
- Freeze and document the current platform frontend contract first.
- Use backend adapters for compatibility.
- Add temporary public read fallback during migration.
- Validate every active platform slug after migration.
- Keep store marketing frontend out of scope for now.

## Definition Of Done
- Platform admin CRUD is implemented on the new platform architecture.
- Public platform marketing reads remain compatible with the current frontend.
- Platform data is migrated to `platform_marketing_pages`.
- Platform SEO and sitemap read from the new platform source.
- Legacy unified platform dependency is removed or explicitly deprecated.
- Store marketing backend foundation exists without requiring frontend rollout.

## Handoff Rule For Another AI
- Execute phases in order.
- Do not redesign the frontend contract unless explicitly approved.
- Prefer backend compatibility over frontend changes.
- Do not start full store marketing frontend implementation.
- Do not remove legacy fallback until migrated platform data is validated.
