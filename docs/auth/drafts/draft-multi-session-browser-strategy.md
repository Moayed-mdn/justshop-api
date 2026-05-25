# [DRAFT] Multi-Session Browser Strategy

**Wave:** 6 (Planned)  
**Status:** DRAFT

## Overview

Proposal for supporting true multi-session browser coexistence using isolated cookies.

## Proposed Strategy

- **Merchant Cookie**: `merchant_session`
- **Customer Cookie**: `customer_session`
- **CSRF**: Domain-bound CSRF tokens.
- **Benefits**: Allows a user to be logged in as a merchant and a customer simultaneously in the same browser without session hijacking risks.
