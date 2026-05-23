# Transitional Route Governance

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 5

## Overview

Transitional routes are explicitly identified surfaces that require shared authority for compatibility or operational reasons.

## Registry

| Route | Purpose | Authority |
|-------|---------|-----------|
| `/sanctum/csrf-cookie` | CSRF Setup | Shared (`web`) |
| `/api/stripe/webhook` | External Payment Webhooks | Stateless |
| `/api/v1/users/checkout/status` | Guest Checkout | Shared |

## Governance Rules

1. **No Mixed Logic**: Transitional routes must not contain domain-specific business logic.
2. **Telemetry Mandatory**: Every access to a transitional route must be logged in the `shared_transitional` domain.
3. **CI Enforcement**: New transitional routes require architectural approval.
