# Storefront Account Bootstrap Contract

**Version:** 1.1  
**Status:** VERIFIED_COMPLETE  
**Wave:** 6

> Scope note:
> `docs/AUTH_ROUTING.md` defines the auth and route-boundary doctrine.
> This file documents the actual customer bootstrap payload returned by `/api/v1/storefront/account/bootstrap`.

## Overview

The storefront account bootstrap is intentionally smaller than the merchant bootstrap.
It returns only customer-safe identity and session information needed for the storefront account experience.

## Endpoint

- `GET /api/v1/storefront/account/bootstrap`
- middleware: `identity.route:customer_account,customer,enforce`
- authenticated with `auth:sanctum`

## Resource Payload

`StorefrontAccountBootstrapResource` returns this body shape:

| Field | Description |
|-------|-------------|
| `user.id` | Customer user id |
| `user.name` | Customer display name |
| `user.email` | Customer email |
| `user.avatar_url` | Customer avatar URL |
| `user.is_email_verified` | Email verification flag |
| `identity_context` | Resolved customer identity context |
| `session` | Session boundary metadata for the current request |

## Response Meta

The controller also returns frontend session metadata in `meta.session` through `FrontendSessionMetadataResolver`.
That meta block is additive and supports frontend session-awareness without reusing the merchant bootstrap envelope.

## What This Contract Does Not Include

Current verified omissions:

- merchant store list
- `active_store`
- merchant permissions or capabilities payloads
- merchant onboarding payloads
- merchant operational config sections

## Isolation Intent

This contract exists so customer account bootstrap remains customer-safe even while the browser still uses a shared underlying session cookie.
