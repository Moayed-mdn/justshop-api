# CSRF Isolation Report

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 4

## Overview

This report evaluates the isolation state of CSRF lifecycles between merchant and customer domains.

## 1. Current State

- **Authority**: Shared `web` middleware.
- **Token Pool**: Global.
- **Refresh Mechanism**: Single `/sanctum/csrf-cookie` endpoint.

## 2. Preparation for Separation

| Action | Status | Impact |
|--------|--------|--------|
| Domain Headers | ACTIVE | `X-Session-Auth-Domain` added to CSRF response |
| Route Annotation| ACTIVE | CSRF endpoint annotated with `shared_transitional` |
| Trace Enrichment| ACTIVE | CSRF ownership traced in `SessionGuardTelemetry` |

## 3. Risks

- A customer tab can refresh a CSRF token that a merchant tab then uses. While functionally valid, it couples the lifecycles.
- Future guard split requires CSRF token verification against the *resolved* guard.
