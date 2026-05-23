# Queue Health Dashboard (DRAFT)

**Owner:** Platform Team  
**Operational Priority:** High  
**Update Frequency:** Real-time  
**Retention:** 90 days  
**Status:** DRAFT - Wave 1 Foundations Only

---

## Purpose

Monitor queue health, job processing, failures, and retry patterns.

**Wave 1 Status:** Foundation telemetry only. Full queue observability requires Wave 5.

---

## Data Sources

**Verified Telemetry (Wave 1):**
- Queue telemetry events (via `QueueTelemetry` class)
- Correlation ID propagation
- Basic job lifecycle events

**Events:**
- `queue.job.enqueued`
- `queue.job.processing`
- `queue.job.processed`
- `queue.job.failed`
- `queue.job.retry`

**Wave 5 Additions (DRAFT):**
- Dead-letter queue metrics
- Replay telemetry
- Idempotency tracking
- Side-effect duplication detection

---

## Metrics (Wave 1 Foundations)

### Job Enqueue Rate
- **Metric:** `queue.jobs.enqueued`
- **Source:** `queue.job.enqueued` event
- **Dimensions:**
  - `job_class`
  - `queue_domain`
- **Aggregation:** Count per minute

### Job Processing Rate
- **Metric:** `queue.jobs.processed`
- **Source:** `queue.job.processed` event
- **Dimensions:**
  - `job_class`
  - `queue_domain`
- **Aggregation:** Count per minute

### Job Failure Rate
- **Metric:** `queue.jobs.failed`
- **Source:** `queue.job.failed` event
- **Dimensions:**
  - `job_class`
  - `queue_domain`
  - `error`
- **Aggregation:** Count per minute

### Job Processing Duration
- **Metric:** `queue.jobs.duration_ms`
- **Source:** `queue.job.processed` event
- **Dimensions:**
  - `job_class`
  - `queue_domain`
- **Aggregations:** p50, p95, p99

### Job Retry Rate
- **Metric:** `queue.jobs.retries`
- **Source:** `queue.job.retry` event
- **Dimensions:**
  - `job_class`
  - `queue_domain`
  - `attempt`
- **Aggregation:** Count per minute

### Correlation Continuity
- **Metric:** `queue.correlation.coverage`
- **Source:** Queue telemetry context
- **Calculation:** Percentage of jobs with correlation IDs
- **Threshold:** Should be 100%

---

## Panels (Wave 1 Foundations)

### 1. Job Enqueue Rate
- **Type:** Time series line chart
- **Metrics:** Jobs enqueued per minute
- **Breakdown:** By queue domain
- **Time Range:** Last 24 hours (default)

### 2. Job Processing Rate
- **Type:** Time series line chart
- **Metrics:** Jobs processed per minute
- **Breakdown:** By queue domain
- **Time Range:** Last 24 hours (default)

### 3. Job Failure Rate
- **Type:** Time series area chart
- **Metrics:** Job failures per minute
- **Breakdown:** By queue domain
- **Time Range:** Last 24 hours (default)

### 4. Job Processing Duration
- **Type:** Time series multi-line chart
- **Metrics:** p50, p95, p99 processing duration
- **Breakdown:** By queue domain
- **Time Range:** Last 24 hours (default)

### 5. Top Failed Jobs
- **Type:** Table
- **Metrics:** Failure count by job class
- **Columns:** Job Class, Failures, Last Error, Last Occurrence
- **Time Range:** Last 1 hour (default)

### 6. Retry Distribution
- **Type:** Bar chart
- **Metrics:** Retry count by attempt number
- **Time Range:** Last 6 hours (default)

### 7. Correlation Coverage
- **Type:** Single stat
- **Metrics:** Percentage of jobs with correlation IDs
- **Threshold:** Alert if < 100%

---

## Wave 5 Additions (DRAFT)

The following panels require Wave 5 async adoption:

### 8. Dead-Letter Queue Size (DRAFT)
- **Status:** Requires Wave 5 dead-letter infrastructure

### 9. Replay Operations (DRAFT)
- **Status:** Requires Wave 5 replay tooling

### 10. Idempotency Violations (DRAFT)
- **Status:** Requires Wave 5 idempotency tracking

### 11. Side-Effect Duplication (DRAFT)
- **Status:** Requires Wave 5 side-effect instrumentation

---

## Filters

- **Time Range:** Adjustable
- **Queue Domain:** order, payment, notification, etc.
- **Job Class:** (for specific job investigation)
- **Correlation ID:** (for request tracing)

---

## Alerts

See: `docs/alerts/queue-health.md` (DRAFT)

---

## Implementation Notes

**Wave 1 Limitations:**
- Foundation telemetry only
- No dead-letter queue monitoring
- No replay tracking
- No idempotency enforcement
- No side-effect duplication detection

**Wave 5 Requirements:**
Full queue observability requires:
- Dead-letter queue infrastructure
- Replay tooling
- Idempotency keys
- Side-effect instrumentation
- Async listener telemetry

**Correlation Continuity:**
Wave 1 provides correlation ID propagation to maintain traceability across async boundaries.

---

## Operational Runbook

See: `docs/runbooks/queue-investigation.md` (DRAFT)

---

## Related Documentation

- Queue Telemetry: `app/Support/Queue/QueueTelemetry.php`
- Governance: `docs/EXECUTION_GOVERNANCE.md` (Wave 5 - Async Adoption)
- Architecture: `docs/ARCHITECTURE.md` (Async Doctrine - Wave 5)
