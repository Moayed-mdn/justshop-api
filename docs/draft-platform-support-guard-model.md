# [DRAFT] Platform/Support Guard Model

**Wave:** 6 (Planned)  
**Status:** DRAFT

## Overview

Proposal for an isolated `platform` guard to handle super-admin and support agent authority.

## Proposed Architecture

- **Guard**: `platform`
- **Provider**: `users` (filtered by role)
- **Authority**: Explicit MFA required for all sessions.
- **Isolation**: Cannot be used to access merchant or customer routes without explicit impersonation metadata.
