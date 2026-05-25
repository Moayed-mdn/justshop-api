# [DRAFT] Customer Provider Extraction

**Wave:** 8 (Planned)  
**Status:** DRAFT

## Overview

Moving customer identities from the shared `users` table to an isolated `customers` table/provider.

## Rationale

- **Performance**: Isolating merchant admins from millions of customer records.
- **Security**: Hard physical separation of PII.
- **Scaling**: Independent scaling of customer auth infrastructure.
