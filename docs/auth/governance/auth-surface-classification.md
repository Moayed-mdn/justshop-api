# Auth Surface Classification

**Version:** 1.1  
**Status:** VERIFIED_COMPLETE  
**Wave:** 6

## Overview

This document classifies auth surfaces by their route owner and their current runtime posture.
The important distinction is that route-level guard selection is active, while browser-session persistence and logout are still shared.

## 1. Merchant Auth Surface

Routes: `/api/v1/users/auth/*`, `/api/v1/users/*`

| Endpoint | Purpose | Current Runtime State |
|----------|---------|-----------------------|
| `/login` | Merchant login | Creates a shared Laravel session, then tags it as `merchant`. |
| `/register` | Merchant register | Creates a shared Laravel session, then tags it as `merchant`. |
| `/logout` | Merchant logout | Protected inside the merchant route domain, but logout still invalidates the full session by default. |
| `/me` | Merchant identity/bootstrap alias | Authenticated with `auth:sanctum`; merchant route ownership is enforced. |
| `/bootstrap` | Merchant state bootstrap | Merchant route ownership is enforced and the merchant payload remains authoritative. |

## 2. Customer Account Surface

Routes: `/api/v1/storefront/account/*`

| Endpoint | Purpose | Current Runtime State |
|----------|---------|-----------------------|
| `/login` | Customer login | Creates a shared Laravel session, then tags it as `customer`. |
| `/register` | Customer register | Creates a shared Laravel session, then tags it as `customer`. |
| `/logout` | Customer logout | Protected inside the customer route domain, but logout still invalidates the full session by default. |
| `/me` | Customer identity | Authenticated with `auth:sanctum`; customer route ownership is enforced. |
| `/bootstrap` | Customer-safe bootstrap | Uses the dedicated storefront bootstrap resource, not the merchant bootstrap payload. |

## 3. Merchant Admin Surface

Routes: `/api/v1/admin/stores/{store}/*`

| Surface | Intended Actor | Current Runtime State |
|---------|----------------|-----------------------|
| Store management | Merchant | Merchant route ownership is enforced and intended guard resolution is explicit. |
| Tenant-scoped admin APIs | Merchant | `auth:sanctum` remains the auth entrypoint, with shared browser session underneath. |

## 4. Platform Surface

Routes: `/api/v1/platform/*` and legacy platform-owned admin CMS/leads routes

| Surface | Intended Actor | Current Runtime State |
|---------|----------------|-----------------------|
| Platform management | `super_admin`, `support_agent` | Platform ownership is enforced; additional platform-authority middleware is required. |
| Legacy platform CMS/leads under `/api/v1/admin/*` | `super_admin` | Platform ownership is enforced even though the URL still lives under `/api/v1/admin/*`. |

## 5. Shared Transitional Surface

| Surface | Purpose | Current Runtime State |
|---------|---------|-----------------------|
| `/sanctum/csrf-cookie` | CSRF setup | Shared transitional surface; strict ownership fallback is intentionally skipped here. |
| `/api/stripe/webhook` | External webhook | Stateless/signature-based surface, not actor-authenticated. |
| `/api/v1/public/*` | Public content | Guest/public surface with no authenticated actor requirement. |

## Current Conclusion

Current auth posture is not accurately described as simple `Shared (Web)` everywhere.
The live runtime is better described as:

- shared browser session and shared logout semantics
- explicit route ownership enforcement
- explicit intended guard selection on annotated routes
- customer and merchant login flows that tag the shared session with actor-domain metadata
