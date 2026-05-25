# Storefront Account Bootstrap Contract

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 3

## Overview

The Storefront Account Bootstrap contract defines the initial payload provided to the frontend when a customer logs in. It is designed to be "customer-safe," containing only the data necessary for commerce flows.

## Payload Structure

| Field | Description | Actor Coupling |
|-------|-------------|----------------|
| `user` | Basic customer profile | Customer-safe |
| `stores` | Empty for customers | Merchant-coupled (isolated) |
| `active_store` | Null for customers | Merchant-coupled (isolated) |
| `permissions` | Customer-specific capabilities | Context-aware |
| `onboarding` | Disabled for customers | Merchant-coupled (isolated) |
| `config` | Public platform configuration | Shared |
| `actor_context` | Explicitly `customer` | Explicit |

## Readiness Telemetry

Bootstrap resolution includes dependency profiling to detect:
- `actor_coupling_anomaly`: When a customer payload contains merchant-coupled data.
- `payload_size_growth`: Monitoring the impact of additive metadata.
- `resolver_timing`: Performance impact of decomposed authority resolution.
