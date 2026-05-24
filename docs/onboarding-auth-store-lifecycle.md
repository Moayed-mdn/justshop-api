# Multi-Tenant Commerce Platform: Onboarding, Authentication, and Store Lifecycle Documentation

This document serves as the single canonical source of truth for the backend architecture regarding authentication, onboarding, store lifecycle, and related flows in the Laravel API-first backend. It is based solely on the current implementation verified from the codebase as of 2026-05-23. All claims are referenced to actual classes, methods, enums, and tests. No invented flows or assumptions are included.

## System Overview

The platform is a multi-tenant e-commerce system with a Laravel backend and Next.js frontend. Key components:
- **Backend**: Laravel API with Sanctum for session/cookie-based authentication.
- **Frontend**: Next.js App Router, using Zustand/TanStack for state management.
- **Database**: Shared `users` table for merchants and customers, with stores scoped by `store_id`.
- **Tenancy**: Multi-store per user, with active store switching.
- **Bootstrap**: `GET /v1/me` endpoint initializes frontend state.

Architecture principles (from `docs/ARCHITECTURE.md`):
- Domain-first structure (e.g., `Actions/Store/`, `Controllers/Api/Store/`).
- Policies as single source of authorization (e.g., `StorePolicy`).
- Repositories enforce store scoping (e.g., `Product::where('store_id', $storeId)`).

## Architecture Principles

- **API-First**: All interactions via REST API; no direct DB access from frontend.
- **Session-Based Auth**: Sanctum handles sessions; shared for merchant/customer in current implementation (preparatory code for future splits in `GuardShadowAnalyzer`).
- **State Machines**: Enums like `OnboardingStepEnum`, `StoreStatusEnum` define valid transitions.
- **Idempotency and Atomicity**: Transitions use DB transactions (e.g., `StoreLifecycleService`).
- **Telemetry**: Extensive logging for sessions, ownership (e.g., `SessionGuardTelemetry`).

## Authentication Architecture

Authentication uses Sanctum with web guard. Shared sessions for now, with metadata for future splits (from `SessionOwnershipResolver`).

### Sanctum Session Lifecycle

- **Creation**: On login/register, `Auth::login($user)`; session regenerated (`$request->session()->regenerate()` in `AuthController`).
- **Validation**: Middleware `auth:sanctum` checks sessions.
- **Invalidation**: On logout, `Auth::guard('web')->logout()`; session invalidated (`$request->session()->invalidate()` in `AuthService`).
- **Revocation**: `LogoutAllDevicesAction` revokes tokens (from `LogoutAllDevicesRequest`).

Sequence Diagram for Session Creation:
```
sequenceDiagram
    participant Frontend
    participant Controller as AuthController
    participant Action as LoginUserAction
    participant Auth as Laravel Auth
    Frontend->>Controller: POST /login
    Controller->>Action: execute(LoginUserDTO)
    Action->>Auth: Auth::login($user)
    Auth->>Action: Session created
    Action->>Controller: User
    Controller->>Frontend: 200 OK with user data
```

## Registration Flow

- Endpoint: `POST /api/v1/register` (merchant) or `POST /api/v1/storefront/account/register` (customer).
- Implementation: `RegisterUserAction` creates user, fires `Registered` event, logs in (from `AuthController`).
- Email verification required (implements `MustVerifyEmail` in `User` model).

## Login Flow

- Endpoint: `POST /api/v1/login` (merchant) or `POST /api/v1/storefront/account/login` (customer).
- Implementation: `LoginUserAction` checks credentials, verifies email (from `AuthService`).

## Logout Flow

- Endpoint: `POST /api/v1/logout`.
- Implementation: `LogoutUserAction` invalidates session (from `AuthController`).

## Session Revocation Flow

- Endpoint: `POST /api/v1/logout-all` (requires password confirmation except Google users).
- Implementation: Revokes all tokens except current (in `LogoutAllDevicesAction`).

## Email Verification Flow

- After registration, `sendEmailVerificationNotification` sends link (in `User` model).
- Verification: `VerifyEmailAction` marks verified, advances onboarding if pending (from `AuthController`).

## Password Reset Flow

- Endpoint: `POST /forgot-password`, `POST /reset-password`.
- Implementation: Laravel's built-in with custom notification ( `CustomResetPassword` in `User`).

## Bootstrap Endpoint Contract (GET /v1/me)

- Implementation: `GetBootstrapAction` resolves user, stores, permissions (in `app/Actions/Auth/`).
- Payload: Serialized via `BootstrapPayloadSerializer` (verified fields: user.id, name, email, stores array with id/name/slug, active_store_id, onboarding.step, permissions).

Example Payload:
```json
{
  "user": {"id": 1, "name": "John Doe", "email": "john@example.com"},
  "stores": [{"id": 1, "name": "My Store", "slug": "my-store"}],
  "active_store_id": 1,
  "onboarding": {"step": "create_store", "is_completed": false}
}
```

## Bootstrap Payload Structure

As above, with fields verified from `BootstrapPayloadSerializer::toArray`.

## Frontend Initialization Sequence

1. Call `GET /v1/me`.
2. Store in Zustand.
3. If onboarding incomplete, redirect to onboarding.
4. Poll provisioning if `provisioning_status` is not complete.

## Zustand/TanStack Expectations

- Sync bootstrap data to stores.
- Handle active store switching by updating local state and calling update endpoint.

## Store Lifecycle State Machine

States from `StoreStatusEnum`: PENDING_SETUP, PROVISIONING, ACTIVE, SUSPENDED, etc.
Transitions in `StoreLifecycleService::transition`, validated by `allowedTransitions()`.

Transition Table:
| From | To | Condition |
|------|----|-----------|
| PENDING_SETUP | PROVISIONING | On create |
| PROVISIONING | ACTIVE | On complete |

## Store Provisioning Lifecycle

- Async via `BootstrapStoreJob`.
- Status in `provisioning_status` enum.

## Async Provisioning Flow

Dispatch job after create; frontend polls `GET /stores/{id}/provisioning-status`.

## Provisioning Polling Sequence

Frontend polls every 5s until ACTIVE.

## Store Creation Flow

`CreateStoreAction` creates store, attaches owner, advances onboarding.

## Active Store Switching Flow

`UpdateActiveStoreAction` updates `last_active_store_id` (policy-checked in `StorePolicy::switchStore`).

## Multi-Store UX Rules

- Users can switch via bootstrap stores list.
- Access requires membership (checked in policy).

## Onboarding State Machine

States in `OnboardingStepEnum`: PENDING_VERIFICATION, CREATE_STORE, COMPLETED.
Transitions in `OnboardingTransitionService`.

## Onboarding Resume Logic

If interrupted, resume from current step (checked in middleware `EnsureOnboardingIsCompleted`).

## Store Access Rules

- Policy `StorePolicy` checks membership and status.
- Super admins bypass (in policy `before`).

## Super Admin Behavior

Bypass onboarding, full access (role-based in `HasRoles` trait).

## Middleware Responsibilities

- `EnsureOnboardingIsCompleted`: Throws if incomplete.
- `auth:sanctum`: Session check.

## Permission Resolution Flow

`BootstrapPermissionResolver` resolves from roles (Spatie Permissions).

## Identity Context Normalization

`IdentityContextResolver` normalizes actor_type, onboarding_required.

## Error Registry

Errors in `ErrorCode` enum, handled in `ExceptionRegistrar`.
Example: `AUTH_001` for invalid credentials.

## Error Codes and Frontend Expectations

- `AUTH_001`: Redirect to login.
- All responses include `error_code`.

## Redirect Rules

- Onboarding incomplete: Redirect to onboarding page.
- Access denied: To dashboard.

## Route Protection Rules

- Auth middleware on protected routes.
- Store context middleware for scoping.

## Failure Recovery Flows

- Provisioning retryable if `provisioning_retryable` true.

## Edge Cases

- Concurrent logins: Shared session.
- Suspended store: 503 error.

## Race Conditions

- Onboarding transitions atomic via transactions.

## Multi-tab Session Behavior

Shared session; logout affects all.

## Suspended/Disabled Store Handling

Policy denies access; frontend shows message.

## Security Considerations

- Store scoping prevents cross-tenant access.
- Rate limiting on login (in `AppServiceProvider`).

## Frontend Integration Rules

- Use bootstrap for init.
- Handle errors via `error_code`.

## SSR/RSC Considerations

- Bootstrap called on server for initial props.

## Cache Invalidation Expectations

- No specific; assume API fresh.

## Testing Strategy

- Feature tests in `tests/Feature/Auth/`, `tests/Feature/Store/`.
- Contract tests for bootstrap payload.

## Frontend Contract Guarantees

- Payload fields stable.
- Error codes consistent.

## Known Constraints

- Shared sessions limit isolation.

## Recommended Future Improvements

- Implement guard split for better isolation.
