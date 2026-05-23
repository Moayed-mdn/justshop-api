# Sanctum Authority Governance

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 4

## Overview

Sanctum authority has been normalized to support multi-guard resolution while preserving its stateful SPA cookie authentication model.

## Configuration

**[sanctum.php](file:///home/leader/projects/laravel/laratenant-backend/config/sanctum.php)** has been updated to include the new transitional guards:
- `web` (Legacy fallback)
- `merchant` (Intended for admin/user surfaces)
- `customer` (Intended for storefront account surfaces)

## Authority Resolution

The **[SanctumAuthorityResolver.php](file:///home/leader/projects/laravel/laratenant-backend/app/Services/Auth/Sanctum/SanctumAuthorityResolver.php)** provides explicit governance over how Sanctum resolves the authenticated user, logging the resolved domain and actor type for every request.

## Token Safety

Since the platform uses strictly session-based auth for the SPA, token safety is guaranteed by the session ownership isolation established in Phase 3.
