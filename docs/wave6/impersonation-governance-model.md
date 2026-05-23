# Impersonation Governance Model

**Wave 6 — VERIFIED_COMPLETE**  
**Status:** Infrastructure complete. Activation gated by `features.platform.impersonation.enabled`.

---

## Principle

Impersonation in this platform is **GOVERNED**, not unrestricted.

Forbidden:
- Session swapping hacks
- Silent guard replacement
- Hidden support elevation
- Middleware bypass impersonation
- Impersonation without audit trail

Required:
- Explicit initiator identity
- Explicit target identity
- Reason tracking
- Expiration
- Revocation capability
- Full audit persistence

---

## Lifecycle States

```
PENDING → ACTIVE → TERMINATED
                 → EXPIRED
PENDING → DENIED
```

| State | Description |
|---|---|
| `pending` | Request created, awaiting activation |
| `active` | Impersonation session is live |
| `terminated` | Manually terminated by initiator or platform |
| `expired` | Automatic expiration reached |
| `denied` | Request denied (future: approval workflow) |

---

## Database Schema

**`impersonations` table:**

| Column | Type | Description |
|---|---|---|
| `id` | bigint PK | |
| `initiator_id` | FK → users | Support agent or super admin |
| `target_id` | FK → users | User being impersonated |
| `reason` | text | Required reason for impersonation |
| `status` | string | `ImpersonationStatusEnum` value |
| `requested_at` | timestamp | When request was created |
| `activated_at` | timestamp | When session became active |
| `terminated_at` | timestamp | When session ended |
| `expires_at` | timestamp | Automatic expiration time |
| `termination_reason` | string | Why session ended |
| `approval_token` | string | Future: approval workflow token |
| `session_id` | string | Laravel session ID bound to impersonation |

Indexes: `(initiator_id, status)`, `(target_id, status)`, `(session_id, status)`, `expires_at`

---

## Lifecycle Manager

`App\Services\Platform\Impersonation\ImpersonationLifecycleManager`

### `request(initiator, target, reason, durationMinutes, approvalToken)`

Creates a `PENDING` impersonation record. Emits `platform.impersonation.requested` telemetry.

### `activate(request, impersonation)`

Transitions `PENDING → ACTIVE`. Binds session ID. Emits `platform.impersonation.activated` telemetry.

### `terminate(request, impersonation, reason)`

Transitions `ACTIVE → TERMINATED`. Records termination reason. Emits `platform.impersonation.terminated` telemetry.

### `expire(impersonation)`

Transitions `ACTIVE → EXPIRED`. Called by scheduled job (future). Emits `platform.impersonation.expired` telemetry.

### `getActive(initiator)`

Returns active impersonation for initiator, or null.

### `hasActiveImpersonation(request)`

Checks if current session has an active impersonation.

---

## Telemetry Events

Every impersonation action emits structured telemetry via `ImpersonationTelemetry`:

| Event | Level | Data |
|---|---|---|
| `platform.impersonation.requested` | warning | initiator_id, target_id, reason, duration_minutes |
| `platform.impersonation.activated` | warning | initiator_id, target_id, session_id, ip, user_agent |
| `platform.impersonation.terminated` | info | initiator_id, target_id, reason, session_id |
| `platform.impersonation.expired` | info | initiator_id, target_id |
| `platform.impersonation.route_accessed` | info | initiator_id, target_id, route, method, session_id |
| `platform.impersonation.violation` | error | violation_type, route, session_id, ip |

---

## API Endpoints

| Endpoint | Method | Description |
|---|---|---|
| `/v1/support/impersonation/request` | POST | Create impersonation request |
| `/v1/support/impersonation/active` | GET | Get active impersonation for current actor |
| `/v1/support/impersonation/terminate` | DELETE | Terminate active impersonation |

---

## Activation Gate

Impersonation is gated by `features.platform.impersonation.enabled` (default: `false`).

When disabled, impersonation endpoints return stub responses. No impersonation sessions can be created.

---

## Future: Approval Workflow

The `approval_token` column and `DENIED` status are reserved for a future approval workflow where impersonation requests require explicit approval from a second platform actor before activation.

This is NOT implemented in Wave 6. Wave 6 only prepares the infrastructure.
