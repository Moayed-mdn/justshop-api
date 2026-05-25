# Sanctum Authority Runtime Model

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 5

## Overview

Sanctum has been normalized to act as an explicit actor-domain authority transport rather than a shared authenticated browser actor.

## Multi-Guard Resolution

Sanctum is configured to support `merchant`, `customer`, and `web` guards. The **[SanctumAuthorityResolver.php](file:///home/leader/projects/laravel/laratenant-backend/app/Services/Auth/Sanctum/SanctumAuthorityResolver.php)** provides the runtime mapping:

- **Stateful Auth**: Still uses cookies but resolves the user against the domain-specific guard.
- **Token Auth**: (Future) Tokens will be bound to specific actor-domain scopes.

## SPA Compatibility

The SPA authentication flow remains unchanged for the frontend, preserving backward compatibility with the existing Next.js and Vue implementations while providing hardened authority on the backend.
