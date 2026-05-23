# Provider Separation Readiness

**Wave 6 — VERIFIED_COMPLETE**  
**Status:** Preparation only. Provider split NOT activated.

---

## Current State

All actors (merchant, customer, platform) share a single identity provider backed by the `users` table. This is the `IdentityProviderEnum::SHARED` state.

```
config/auth.php:
  providers:
    users:
      driver: eloquent
      model: App\Models\User
```

All guards (`web`, `merchant`, `customer`) use the same `users` provider.

---

## Shared Assumptions Detected

The following shared assumptions are documented and must be resolved before provider separation:

| Assumption | Location | Status |
|---|---|---|
| Password reset flow | `config/auth.php` → `passwords.users` | Shared — `customers` broker still uses `users` provider |
| Email verification flow | `App\Notifications\VerifyEmail` | Shared — single notification class |
| Password reset notification | `App\Notifications\CustomResetPassword` | Shared — single notification class |
| Token storage | `personal_access_tokens` table | Shared — single table |
| Session storage | `sessions` table | Shared — single table |
| User model | `App\Models\User` | Shared — single model for all actors |

---

## Provider Governance Layer

`App\Services\Auth\ProviderGovernanceService` provides:

- `resolveProvider(User)` → `IdentityProviderEnum::SHARED` (current)
- `resolveProviderForAuthDomain(AuthDomainEnum)` → `IdentityProviderEnum::SHARED` (current)
- `resolveProviderForActorContext(ActorContextEnum)` → `IdentityProviderEnum::SHARED` (current)
- `isProviderSeparationReady()` → `false` (current)
- `getProviderReadinessReport()` → full readiness report

`App\Services\Auth\ProviderOwnershipRegistry` tracks provider metadata per auth domain.

`App\Services\Auth\ProviderTelemetry` emits:
- `auth.provider.resolved`
- `auth.provider.assumption_detected`
- `auth.provider.readiness_checked`

---

## Migration Blockers

| Blocker | Description |
|---|---|
| `shared_user_table` | All actors in single `users` table — requires table split or discriminator column |
| `shared_password_resets_table` | Single `password_reset_tokens` table |
| `shared_sessions_table` | Single session store |
| `shared_personal_access_tokens_table` | Single Sanctum tokens table |

---

## Readiness Criteria (Future)

Provider separation will be ready when:
1. Actor-specific user models or discriminator columns are in place
2. Separate password reset brokers are configured and tested
3. Separate email verification flows are implemented
4. Session isolation is fully enforced (Wave 5 completion)
5. Token namespacing is implemented

---

## Feature Flag

`features.provider.separation.preparation` (default: `true`) enables provider governance telemetry without activating any split.

---

## Compatibility Preservation

The `config/auth.php` `customers` password broker is already prepared:
```php
'customers' => [
    'provider' => 'users', // Preparation: still uses 'users' table/model for now
    'table' => 'password_reset_tokens',
    ...
]
```

This is the preparation hook for future customer provider separation. It MUST NOT be removed.
