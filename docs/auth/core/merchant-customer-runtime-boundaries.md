# Merchant-Customer Runtime Boundaries

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 5

## Overview

This document defines the hardened boundaries between merchant and customer runtime environments.

## Boundary Definition

| Boundary | Enforcement Mechanism | Failure Mode |
|----------|-----------------------|--------------|
| Route Domain | `identity.route` middleware | 403 Forbidden |
| Actor Context| `IdentityContextResolver` | 403 Forbidden |
| Guard Authority| `TransitionalGuardResolver` | 403 Forbidden |
| Session Domain| `SessionOwnershipManager` | 403 Forbidden |

## Policy Enforcement

Policies now use `isMerchant()` and `isCustomer()` helpers to ensure that even if a user is authenticated, they cannot perform actions outside their resolved actor domain.
