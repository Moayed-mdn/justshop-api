# Tenant Isolation Health Dashboard

**Owner:** Platform Team  
**Operational Priority:** Critical  
**Update Frequency:** Real-time  
**Retention:** 90 days

---

## Purpose

Monitor tenant isolation integrity and detect cross-store data leakage or access violations.

---

## Data Sources

**Verified Telemetry:**
- Security event logs
- Store context enrichment (via `StoreContext` middleware)
- Request trace context with `store_id` and `membership_id`
- Audit logs with store ownership metadata

**Security Events:**
- `tenant.store_mismatch`
- `authorization.denied` (with store context)
- `store.context.enriched`

---

## Metrics

### Store Context Coverage
- **Metric:** `store.context.coverage_rate`
- **Source:** Request trace context
- **Calculation:** Percentage of store-scoped requests with valid `store_id`
- **Threshold:** Must be 100% for store-scoped routes
- **Aggregation:** Per minute

### Store Mismatch Incidents
- **Metric:** `tenant.store_mismatch`
- **Source:** Security event `tenant.store_mismatch`
- **Dimensions:**
  - `expected_store_id`
  - `actual_store_id`
  - `route`
  - `actor_id`
- **Aggregation:** Count per minute
- **Severity:** CRITICAL

### Cross-Store Access Attempts
- **Metric:** `authorization.cross_store_denied`
- **Source:** Authorization denial events with store mismatch
- **Dimensions:**
  - `actor_store_id`
  - `requested_store_id`
  - `policy`
- **Aggregation:** Count per minute
- **Severity:** HIGH

### Membership Resolution Failures
- **Metric:** `membership.resolution.failed`
- **Source:** Store context enrichment failures
- **Dimensions:**
  - `actor_id`
  - `store_id`
  - `reason`
- **Aggregation:** Count per minute

### Store-Scoped Query Coverage (DRAFT)
- **Metric:** `repository.store_scope.coverage`
- **Source:** Repository instrumentation (Wave 2)
- **Status:** DRAFT - requires repository telemetry
- **Calculation:** Percentage of commerce queries with store_id scope

---

## Panels

### 1. Store Context Coverage
- **Type:** Single stat with gauge
- **Metrics:** Store context coverage rate
- **Threshold:** Alert if < 100%
- **Color:** Green (100%), Red (< 100%)
- **Time Range:** Last 1 hour (default)

### 2. Store Mismatch Incidents
- **Type:** Time series line chart
- **Metrics:** Store mismatch count
- **Threshold:** Alert if > 0
- **Time Range:** Last 24 hours (default)

### 3. Cross-Store Access Attempts
- **Type:** Time series bar chart
- **Metrics:** Cross-store denial count
- **Breakdown:** By policy
- **Time Range:** Last 6 hours (default)

### 4. Store Mismatch Details
- **Type:** Table
- **Metrics:** Recent store mismatch incidents
- **Columns:** Timestamp, Actor ID, Expected Store, Actual Store, Route, Correlation ID
- **Time Range:** Last 24 hours (default)
- **Privacy:** Hash actor IDs in production

### 5. Membership Resolution Health
- **Type:** Time series line chart
- **Metrics:** Membership resolution success rate
- **Threshold:** Alert if < 99%
- **Time Range:** Last 24 hours (default)

### 6. Requests by Store
- **Type:** Bar chart
- **Metrics:** Request count by store_id
- **Limit:** Top 20 stores
- **Time Range:** Last 1 hour (default)

### 7. Tenant Isolation Violations (Critical)
- **Type:** Alert panel
- **Metrics:** Any store mismatch or cross-store access in last 5 minutes
- **Display:** Count with severity indicator
- **Auto-refresh:** Every 30 seconds

---

## Filters

- **Time Range:** Adjustable
- **Store ID:** (for tenant-specific investigation)
- **Actor ID:** (for user-specific investigation)
- **Route Domain:** merchant, admin, storefront
- **Correlation ID:** (for request tracing)

---

## Alerts

See: `docs/alerts/tenant-isolation-violations.md`

---

## Critical Invariants

The following conditions MUST trigger immediate alerts:

1. **Store Context Missing:** Any store-scoped route without `store_id` in trace context
2. **Store Mismatch:** Any request where resolved store differs from route parameter
3. **Cross-Store Access:** Any authorization denial due to store ownership mismatch
4. **Membership Violation:** Any admin operation without valid membership for target store

---

## Implementation Notes

**Zero-Tolerance Policy:**
Tenant isolation violations are Sev-1 incidents. Any non-zero count requires immediate investigation.

**Cardinality Controls:**
- Store IDs should be aggregated for high-volume metrics
- Actor IDs must be hashed in production dashboards
- Use correlation IDs for detailed incident investigation

**Privacy:**
- Never display raw actor IDs or store names in shared dashboards
- Use anonymized identifiers for production monitoring
- Detailed investigation requires secure access controls

---

## Operational Runbook

See: `docs/runbooks/tenant-isolation-incident.md`
