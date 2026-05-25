# Multi-Tenant Commerce Platform: Onboarding, Authentication, and Store Lifecycle Documentation

> Guide note:
> This file is an implementation guide and architecture reference for onboarding, auth, and store lifecycle behavior.
> It is not the sole auth doctrine. For auth routing and boundary rules, prefer `docs/AUTH_ROUTING.md`. For navigation across auth documents, prefer `docs/auth/README.md`.

This document serves as a backend implementation guide for authentication, onboarding, store lifecycle, and related flows in the Laravel API-first backend. It is based solely on the current implementation verified from the codebase as of 2026-05-23. All claims are referenced to actual classes, methods, enums, and tests. No invented flows or assumptions are included.

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

Authentication still enters through `auth:sanctum` and a shared Laravel browser session, but route ownership and intended guard selection are now explicit. Merchant login/register in `AuthController` tag the regenerated session as `merchant`; customer login/register in `StorefrontAccountController` tag it as `customer`. `ApplyIdentityRouteContext` resolves and applies the intended `merchant` or `customer` guard on annotated routes, while browser-session persistence and logout remain shared by default.

### Sanctum Session Lifecycle

- **Creation**: On merchant or customer login/register, `Auth::login($user)` creates the session and the controller regenerates it.
- **Tagging**: `SessionOwnershipManager` stores `auth_domain`, `actor_type`, and `actor_id` in the session.
- **Validation**: `auth:sanctum` authenticates the request and `identity.route` enforces route ownership.
- **Guard Selection**: `ApplyIdentityRouteContext` calls `Auth::shouldUse()` with the intended guard on annotated routes.
- **Invalidation**: `LogoutUserAction` still invalidates the full Laravel session by default because guard-split logout is not enabled by default.
- **Revocation**: `LogoutAllDevicesAction` deletes other rows from the `sessions` table for the authenticated user.

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

- Merchant endpoint: `POST /api/v1/users/auth/register`.
- Customer endpoint: `POST /api/v1/storefront/account/register`.
- Implementation: merchant registration goes through `AuthController`; customer registration goes through `StorefrontAccountController`. Both create the user, log them in, regenerate the session, and tag the session domain.

## Login Flow

- Merchant endpoint: `POST /api/v1/users/auth/login`.
- Customer endpoint: `POST /api/v1/storefront/account/login`.
- Implementation: merchant login uses `LoginUserAction`; customer login uses `LoginCustomerAction`. Both controllers call `Auth::login($user)`, regenerate the session, and tag the actor domain.

## Logout Flow

- Merchant endpoint: `POST /api/v1/users/auth/logout`.
- Customer endpoint: `POST /api/v1/storefront/account/logout`.
- Implementation: both surfaces use `LogoutUserAction`, which resolves actor/session metadata first and then invalidates the full Laravel session by default.

## Session Revocation Flow

- Endpoint: `DELETE /api/v1/users/sessions` (password confirmation enforced by the request layer).
- Implementation: `LogoutAllDevicesAction` deletes the authenticated user's other session rows and preserves the current session when requested.

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
