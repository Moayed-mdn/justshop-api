# [DRAFT] Provider Split Activation Plan

**Wave:** 8 (Planned)  
**Status:** DRAFT

## Overview

Plan for activating separate authentication providers for Merchant, Customer, and Platform domains.

## Proposed Strategy

1. **Database Split:** Separate `users` table into domain-specific tables.
2. **Provider Configuration:** Define separate `auth.providers` in `config/auth.php`.
3. **Migration Path:**
   - Phase 1: Dual-read (Shared + New Domain Provider).
   - Phase 2: Dual-write.
   - Phase 3: Cutover to Domain Provider.
4. **Impact:** Requires updating all polymorphic relations and auth guards.
