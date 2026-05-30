# AGENTS.md

## Purpose

This file defines the mandatory operating rules for AI agents and automation working in `/home/leader/projects/laravel/tenant/laratenant-backend`.

Use this file together with:

- `docs/ARCHITECTURE.md` - **ABSOLUTE AUTHORITY** for implementation doctrine.
- `docs/EXECUTION_GOVERNANCE.md` - Mandatory safety and rollout rules.
- `docs/security/forbidden-patterns.md` - Critical security guardrails.
- `README.md` - Project overview and setup.

## Repository Facts You Must Preserve

Current code-backed architecture:

- **Laravel 12.0** with **PHP 8.2+**.
- **Domain-Driven Layers**: Every feature MUST be grouped by domain (e.g., `app/Actions/Store/`, `app/DTOs/Cart/`).
- **DTO-First Actions**: Every Action MUST receive a strictly typed DTO.
- **Policies as Truth**: Authorization MUST live in Policies. Actions assume authorization has already passed.
- **Store Scoping**: All commerce queries MUST be scoped by `store_id` in Repositories.
- **PHP Enums**: Domain states MUST use PHP Enums. Database-level enums are STRICTLY FORBIDDEN.
- **API Standard**: All responses MUST use `ApiResponserTrait` and `app/Enums/ErrorCode.php`.

## Required Workflow

1. **Inspect Before Editing**: Read the relevant domain files across all layers (Action, DTO, Request, Resource, Policy, Repository).
2. **Follow The Doctrine**: Treat `docs/ARCHITECTURE.md` as the controlling contract. Do not introduce technical debt or "quick fixes" that bypass these layers.
3. **Domain Grouping**: Never create flat files in `app/Actions`, `app/DTOs`, `app/Http/Requests`, etc. Always use a domain subfolder.
4. **Authorization Boundary**: NEVER add role/permission checks or `auth()->user()` calls inside Actions. Use Policies.
5. **Multi-Store DTOs**: Commerce DTOs MUST include `public int $storeId` as the first constructor parameter.
6. **Error Handling**: Use `BaseApiException` and `ErrorCode` enum. Never return raw arrays or manual `response()->json()`.
7. **Security Awareness**: Consult `docs/security/forbidden-patterns.md` before touching auth, session, or tenant-isolation logic.

## Maintenance Cadence

- **Sync All Layers**: When modifying a feature, update the Request, DTO, Action, and Resource to maintain consistency.
- **Update Docs**: If you change API contracts, DTO shapes, or domain boundaries, update the relevant files in `docs/`.
- **Wave Governance**: Follow the active Wave execution plan (e.g., `docs/wave6/`, `docs/wave7/`).
- **Diagnostics**: Run `php artisan test` or similar verification after substantive changes.

## Accuracy Rules

- **Code Over Assumptions**: Prefer live code implementation over stale comments or historical docs.
- **No Guesses**: If a domain boundary is unclear, inspect `docs/README.md` to find the owner document.
- **Exact Terminology**: Use `Action`, `DTO`, `Repository`, `Policy`, `Resource`, `FormRequest`.

## Security Rules (CRITICAL)

- **Tenant Isolation**: Never perform unscoped queries in Repositories. Use `scopedQuery()` or explicit `where('store_id', ...)`.
- **Identity Context**: Respect the `identity.route` middleware and `IdentityContext` model.
- **Secrets**: Never commit `.env` values or hardcoded keys.
- **Forbidden Patterns**: Direct `hasRole` or `hasPermissionTo` checks outside of Policies/Resolvers are forbidden.

## Handoff Requirements

When you finish a task, provide:

- Files created or updated (grouped by domain).
- Any discrepancy found between `ARCHITECTURE.md` and live code.
- Any unresolved security or isolation concerns.
- Next recommended steps in the active Wave plan.
