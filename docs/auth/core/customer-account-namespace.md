# Customer Account Namespace

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 3

> Scope note:
> `docs/AUTH_ROUTING.md` defines the route-domain doctrine.
> This file is a narrow reference for the customer-facing `/api/v1/storefront/account/*` surface and its isolation rules.

## Overview

The Customer Account Namespace (`/api/v1/storefront/account/*`) is an isolated route family dedicated to customer actors. It provides a clean boundary from the merchant-authoritative `/api/v1/users/auth/*` routes.

## Routes

| Path | Purpose | Enforcement |
|------|---------|-------------|
| `/register` | Customer registration | `customer,enforce` |
| `/login` | Customer login | `customer,enforce` |
| `/logout` | Session termination | `customer,enforce` |
| `/me` | Identity resolution | `customer,enforce` |
| `/bootstrap` | Initial state | `customer,enforce` |

## Isolation Rules

1. **No Merchant Escalation:** Customers cannot access merchant-scoped routes (e.g., `/api/v1/stores`).
2. **Explicit Onboarding Bypass:** Customers are never subjected to merchant onboarding steps.
3. **Actor-Aware Session:** Sessions created via this namespace are tagged with `customer` domain metadata.
