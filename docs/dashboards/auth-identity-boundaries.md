# Auth & Identity Boundaries Dashboard

**Owner:** Auth Team  
**Operational Priority:** Critical  
**Update Frequency:** Real-time  
**Retention:** 90 days

---

## Purpose

Monitor authentication health, identity context resolution, and authorization boundary enforcement.

---

## Data Sources

**Verified Telemetry:**
- Security event logs (via security log channel)
- Identity telemetry (via `IdentityTelemetry` service)
- Session guard telemetry (via `SessionGuardTelemetry` service)
- Request trace context with auth metadata
- Audit logs (via `audit_logs` table)

**Log Events:**
- `auth.login.failed`
- `auth.guard.mismatch`
- `auth.onboarding.denied`
- `identity.customer_route.accessed`
- `identity.actor_domain.mismatch`
- `identity.onboarding.evaluated`
- `identity.onboarding.bypassed`
- `authorization.denied`

---

## Metrics

### Login Success Rate
- **Metric:** `auth.login.success_rate`
- **Source:** Security event logs
- **Dimensions:**
  - `auth_domain` (merchant, customer)
  - `actor_type`
- **Aggregation:** Success rate percentage per 5 minutes

### Failed Login Attempts
- **Metric:** `auth.login.failed`
- **Source:** Security event `auth.login.failed`
- **Dimensions:**
  - `auth_domain`
  - `ip_address` (cardinality-controlled)
  - `reason`
- **Aggregation:** Count per minute

### Onboarding Gate Denials
- **Metric:** `auth.onboarding.denied`
- **Source:** Security event `auth.onboarding.denied`
- **Dimensions:**
  - `actor_id`
  - `store_id`
  - `onboarding_step`
- **Aggregation:** Count per minute

### Authorization Denials
- **Metric:** `authorization.denied`
- **Source:** Security event `authorization.denied`
- **Dimensions:**
  - `policy`
  - `ability`
  - `actor_type`
  - `store_id`
- **Aggregation:** Count per minute

### Identity Context Mismatches
- **Metric:** `identity.actor_domain.mismatch`
- **Source:** Identity telemetry event
- **Dimensions:**
  - `expected_domain`
  - `actual_domain`
  - `route_domain`
- **Aggregation:** Count per minute

### Guard Shadow Anomalies (Wave 3B)
- **Metric:** `guard.shadow.mismatch`
- **Source:** Session guard telemetry
- **Dimensions:**
  - `current_guard`
  - `intended_guard_future`
  - `route_domain`
- **Aggregation:** Count per minute
- **Status:** DRAFT (Wave 3B telemetry)

---

## Panels

### 1. Login Success Rate
- **Type:** Time series line chart
- **Metrics:** Login success rate by auth domain
- **Threshold:** Alert if < 95%
- **Time Range:** Last 24 hours (default)

### 2. Failed Login Attempts
- **Type:** Time series bar chart
- **Metrics:** Failed login count
- **Breakdown:** By reason
- **Time Range:** Last 6 hours (default)

### 3. Onboarding Denials
- **Type:** Time series line chart
- **Metrics:** Onboarding denial count
- **Breakdown:** By onboarding step
- **Time Range:** Last 24 hours (default)

### 4. Authorization Denials by Policy
- **Type:** Table
- **Metrics:** Denial count by policy and ability
- **Columns:** Policy, Ability, Count, Last Occurrence
- **Time Range:** Last 1 hour (default)

### 5. Actor Type Distribution
- **Type:** Stacked bar chart
- **Metrics:** Request count by actor type
- **Breakdown:** By route domain
- **Time Range:** Last 1 hour (default)

### 6. Identity Context Health
- **Type:** Single stat panel
- **Metrics:** Identity mismatch rate
- **Threshold:** Alert if > 0.1%
- **Time Range:** Last 1 hour (default)

### 7. Top Denied Actors
- **Type:** Table
- **Metrics:** Authorization denials by actor
- **Columns:** Actor ID, Actor Type, Denial Count, Policies Denied
- **Time Range:** Last 1 hour (default)
- **Privacy:** Anonymize actor IDs in production

---

## Filters

- **Time Range:** Adjustable
- **Auth Domain:** merchant, customer, platform
- **Actor Type:** merchant, customer, super_admin, guest
- **Store ID:** (for tenant-specific investigation)
- **Policy:** (for authorization investigation)

---

## Alerts

See: `docs/alerts/auth-anomalies.md`

---

## Implementation Notes

**Privacy Considerations:**
- Actor IDs should be hashed or anonymized in production dashboards
- IP addresses should be aggregated, not displayed individually
- Failed login details should not expose user enumeration vectors

**Cardinality Controls:**
- Limit actor_id cardinality in aggregations
- Use sampling for detailed actor investigation
- Aggregate by domain and policy before drilling into actors

**Wave 3 Preparation:**
Guard shadow metrics are marked DRAFT and will become authoritative in Wave 3B.

---

## Operational Runbook

See: `docs/runbooks/auth-incident-response.md`
