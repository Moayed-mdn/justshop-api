# HTTP Request Telemetry Dashboard

**Owner:** Platform Team  
**Operational Priority:** High  
**Update Frequency:** Real-time  
**Retention:** 90 days

---

## Purpose

Monitor HTTP request health, performance, and error patterns across all API domains.

---

## Data Sources

**Verified Telemetry:**
- Request correlation IDs (via `X-Correlation-ID` header)
- Request trace context (via `RequestTraceContext`)
- Structured log context (via `Log::withContext()`)
- Security event logs (via security log channel)

**Log Channels:**
- `stack` (default application logs)
- `security` (security-specific events)

---

## Metrics

### Request Volume
- **Metric:** `http.requests.total`
- **Source:** Application logs with correlation IDs
- **Dimensions:** 
  - `api_domain` (merchant, storefront, admin, platform)
  - `route_domain` (from trace context)
  - `http_method`
  - `status_code`
- **Aggregation:** Count per minute

### Response Time
- **Metric:** `http.requests.duration_ms`
- **Source:** Request lifecycle timing
- **Dimensions:**
  - `api_domain`
  - `route_domain`
  - `endpoint`
- **Aggregations:**
  - p50, p95, p99 latency
  - Average response time
  - Max response time

### Error Rate
- **Metric:** `http.requests.errors`
- **Source:** Exception logs with correlation IDs
- **Dimensions:**
  - `status_code` (4xx, 5xx)
  - `error_code` (from ErrorCode enum)
  - `api_domain`
  - `route_domain`
- **Aggregation:** Error rate percentage per minute

### Request Context Distribution
- **Metric:** `http.requests.by_context`
- **Source:** Request trace context
- **Dimensions:**
  - `actor_type` (merchant, customer, super_admin, guest)
  - `auth_domain` (merchant, customer, platform)
  - `operational_context`
  - `store_id` (cardinality-controlled)
- **Aggregation:** Distribution percentage

---

## Panels

### 1. Request Volume Overview
- **Type:** Time series line chart
- **Metrics:** Total requests per minute
- **Breakdown:** By API domain
- **Time Range:** Last 24 hours (default)

### 2. Response Time Percentiles
- **Type:** Time series multi-line chart
- **Metrics:** p50, p95, p99 latency
- **Breakdown:** By API domain
- **Time Range:** Last 24 hours (default)

### 3. Error Rate
- **Type:** Time series area chart
- **Metrics:** 4xx and 5xx error rates
- **Breakdown:** By status code category
- **Time Range:** Last 24 hours (default)

### 4. Top Errors by Code
- **Type:** Table
- **Metrics:** Error count by `error_code`
- **Columns:** Error Code, Count, Last Occurrence, Sample Message
- **Time Range:** Last 1 hour (default)

### 5. Actor Type Distribution
- **Type:** Pie chart
- **Metrics:** Request count by `actor_type`
- **Time Range:** Last 1 hour (default)

### 6. Correlation ID Coverage
- **Type:** Single stat
- **Metrics:** Percentage of requests with valid correlation IDs
- **Threshold:** Alert if < 99%

---

## Filters

- **Time Range:** Adjustable (1h, 6h, 24h, 7d, 30d)
- **API Domain:** merchant, storefront, admin, platform
- **Actor Type:** merchant, customer, super_admin, guest
- **Store ID:** (for tenant-specific investigation)
- **Correlation ID:** (for request tracing)

---

## Alerts

See: `docs/alerts/http-request-anomalies.md`

---

## Implementation Notes

**Platform-Neutral:**
This specification can be implemented in:
- Grafana (via Loki or Elasticsearch data source)
- Kibana (via Elasticsearch)
- CloudWatch Dashboards
- Custom observability platforms

**Query Patterns:**
Queries should filter logs by:
- Presence of `correlation_id` field
- Structured context fields from `RequestTraceContext`
- Log level and channel

**Cardinality Controls:**
- Limit `store_id` cardinality in high-volume aggregations
- Use sampling for detailed trace analysis
- Aggregate by domain before drilling into specific stores

---

## Operational Runbook

See: `docs/runbooks/http-request-investigation.md`
