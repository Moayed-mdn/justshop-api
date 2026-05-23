# [DRAFT] Platform Automation Authority

**Wave:** 9 (Planned)  
**Status:** DRAFT

## Overview

Governance for automated platform system actors (bots, workers, schedulers).

## Proposed Model

- **System Actor Domain:** Separate domain for non-human actors.
- **Service Accounts:** Actor-bound API keys for system operations.
- **Limited Scope:** System actors are restricted to specific platform domains.
- **Audit:** All automation actions must include a `source_automation_id`.
