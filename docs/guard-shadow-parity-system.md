# Guard Shadow Parity System

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 4

## Overview

The Guard Shadow Parity System is a "Dark Launch" infrastructure that evaluates future guard separation in real-time without impacting user traffic.

## Components

- **[GuardSplitSimulationService.php](file:///home/leader/projects/laravel/laratenant-backend/app/Services/Auth/GuardSplitSimulationService.php)**: Compares the legacy `web` guard against the intended `merchant`/`customer` guards.
- **[ApplyIdentityRouteContext.php](file:///home/leader/projects/laravel/laratenant-backend/app/Http/Middleware/ApplyIdentityRouteContext.php)**: Executes the simulation on every authenticated request.

## Parity Telemetry

Mismatches are logged with `auth.guard.split_mismatch_detected`. A "mismatch" occurs if the intended guard for a route does not align with the current shared authority model.

## Success Criteria

Wave 4 is considered successful when the "Mismatch Ratio" reported by the shadow system drops to 0% across all production-like traffic patterns, indicating that the guard split logic is functionally identical to the shared authority for existing flows.
