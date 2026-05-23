# DRAFT — Customer Provider Cutover

**Status: DRAFT — NOT ACTIVATED**  
**Target Wave:** Wave 7+  
**Prerequisite:** Wave 5 guard split fully enforced, Wave 6 provider readiness confirmed

---

## Overview

This document describes the planned cutover from the shared `users` provider to a dedicated customer identity provider. This is a DRAFT. No implementation has been started.

---

## Planned Architecture

### Current State
```
config/auth.php:
  guards.customer.provider = 'users'
  providers.users.model = App\Models\User
```

### Target State
```
config/auth.php:
  guards.customer.provider = 'customers'
  providers.customers.model = App\Models\Customer (or App\Models\User with discriminator)
```

---

## Cutover Steps (Planned)

1. **Discriminator column** — Add `actor_type` discriminator to `users` table OR create separate `customers` table
2. **Customer model** — Create `App\Models\Customer` extending `Authenticatable` OR add scoped queries to `User`
3. **Customer provider** — Register `customers` Eloquent provider in `config/auth.php`
4. **Password reset broker** — The `customers` broker in `config/auth.php` already points to `users` provider — update to `customers` provider
5. **Email verification** — Create customer-specific `VerifyEmail` notification
6. **Password reset notification** — Create customer-specific `CustomResetPassword` notification
7. **Session isolation** — Ensure customer sessions use `customer` guard exclusively
8. **Token isolation** — Namespace customer tokens in `personal_access_tokens`
9. **Dual-read period** — Run both providers in parallel, compare results
10. **Cutover** — Switch `guards.customer.provider` to `customers`
11. **Cleanup** — Remove shared provider fallbacks

---

## Blockers

- Wave 5 guard split must be fully enforced
- Session isolation must be proven stable
- Customer registration flow must be updated
- All customer-facing routes must use `auth:customer` guard explicitly

---

## Risk Assessment

**Blast radius:** Critical — affects all customer authentication  
**Rollback:** Revert `guards.customer.provider` to `users`  
**Data risk:** None if discriminator approach used (no data migration)
