# Observability Foundations

## Purpose

This document describes the low-risk observability infrastructure introduced during the
stabilization phase.

It is intentionally additive. It does not change API contracts, authorization behavior,
bootstrap payloads, membership rules, guard behavior, or checkout logic.

## Scope

Implemented foundations:

- request correlation IDs
- request-scoped trace context
- structured logging context propagation
- database-backed audit infrastructure
- security event logging primitives
- release version tagging

Explicitly out of scope:

- bootstrap decomposition
- membership migration
- RBAC migration
- guard split
- checkout refactor
- queue or async observability
- external tracing vendors

## Correlation Lifecycle

### Header Contract

- Incoming header: `X-Correlation-ID`
- Outgoing header: `X-Correlation-ID`

### Rules

- If a valid UUID-like correlation ID is provided, it is preserved.
- If no valid correlation ID is provided, a new UUID is generated.
- The same correlation ID survives the full request lifecycle.
- The correlation ID is available in:
  - request trace context
  - structured log context
  - audit log rows
  - security event logs
  - exception responses via response headers

## Trace Context Structure

The request-scoped context is represented by `RequestTraceContext`.

Fields:

- `correlationId`
- `actorId`
- `actorType`
- `membershipId`
- `storeId`
- `apiDomain`
- `releaseVersion`

### Enrichment Model

- `InitializeRequestTraceContext` initializes correlation ID, API domain, release version, and actor context when available.
- `StoreContext` enriches the trace with `storeId` and `membershipId`.
- Exception handling and loggers consume the same context without mutating business behavior.

### Important Boundary

Trace context is observability-only.

It MUST NOT:

- authorize requests
- decide store membership
- alter policies
- change controller responses
- become a hidden business dependency

## Middleware Responsibilities

### `InitializeRequestTraceContext`

- runs on API requests
- initializes request-scoped trace context
- preserves or generates correlation IDs
- sets structured log context via `Log::withContext()`
- adds `X-Correlation-ID` to successful responses

### `StoreContext`

- continues validating active store resolution
- continues binding `storeId` and `currentStore`
- additionally enriches trace context with `storeId` and `membershipId`

### `EnsureOnboardingIsCompleted`

- keeps existing onboarding gate behavior unchanged
- records a structured security event when onboarding access is denied

## Logging Integration

Structured request context is propagated through Laravel logging using `Log::withContext()`.

Included fields:

- `correlation_id`
- `actor_id`
- `actor_type`
- `membership_id`
- `store_id`
- `api_domain`
- `release_version`

This is propagation infrastructure only.

It does not rewrite existing log statements or replace the global logger.

## Audit Infrastructure

### Storage

Audit events are persisted in the `audit_logs` table.

Columns:

- `id`
- `event`
- `actor_id`
- `actor_type`
- `membership_id`
- `store_id`
- `correlation_id`
- `ip_address`
- `user_agent`
- `metadata`
- `created_at`

### Rules

- append-only
- no updates
- no deletes
- safe metadata only
- no passwords, tokens, authorization headers, cookies, or secrets in metadata

### Usage Boundary

The audit system is available through `AuditLoggerInterface`.

This phase provides infrastructure only. It does not automatically audit every operation.

## Security Event Foundations

Supported event types:

- `auth.login.failed`
- `auth.guard.mismatch`
- `auth.onboarding.denied`
- `tenant.store_mismatch`
- `authorization.denied`

Current safe integrations:

- onboarding denial
- invalid credentials
- store context mismatch / unauthorized store access
- authorization denial exceptions

Security events are logged with safe metadata and request trace context. They are not yet
wired to alerting, dashboards, or SIEM integrations.

## Release Marker Support

Release version is sourced from `config/observability.php` via `APP_RELEASE_VERSION`.

It is attached to:

- trace context
- structured logs
- security events
- audit records

## Future Integration Points

These foundations are intended to support later roadmap phases without rework:

- parity telemetry for bootstrap decomposition
- migration cutover auditing
- tenant isolation incident correlation
- authorization drift detection
- rollback and release impact analysis
