# [DRAFT] Enterprise Inheritance Model

**Wave:** 8 (Planned)  
**Status:** DRAFT

## Overview

Proposal for complex authority inheritance within enterprise organizations.

## Proposed Model

- **Organization Node:** Root authority for a group of stores.
- **Inherited Roles:** Roles assigned at the organization level inherit down to all stores.
- **Scoped Overrides:** Ability to override inherited roles at specific store nodes.
- **Validation:** All inheritance must be resolved by `AuthorityInheritanceModel`.
