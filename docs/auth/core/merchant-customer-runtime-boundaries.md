# Merchant-Customer Runtime Boundaries

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 5

> Scope note:
> This file summarizes the merchant/customer boundary model at a high level.
> For current-state authority posture and route classification, prefer `docs/auth/governance/auth-surface-classification.md` and `docs/AUTH_ROUTING.md`.

## Overview

This document summarizes the enforced and intended boundaries between merchant and customer runtime environments. Some layers are actively enforced today, while others remain part of the transitional shared-session model.

## Boundary Definition

| Boundary | Enforcement Mechanism | Failure Mode |
|----------|-----------------------|--------------|
| Route Domain | `identity.route` middleware | 403 Forbidden |
| Actor Context| `IdentityContextResolver` | 403 Forbidden |
| Guard Authority| `TransitionalGuardResolver` | 403 Forbidden |
| Session Domain| `SessionOwnershipManager` | 403 Forbidden |

## Policy Enforcement

Policies now use `isMerchant()` and `isCustomer()` helpers to ensure that even if a user is authenticated, they cannot perform actions outside their resolved actor domain.
