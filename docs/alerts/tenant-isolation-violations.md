# Tenant Isolation Violation Alerts

**Owner:** Platform Team  
**Escalation:** Security Team + CTO (immediate for Sev-1)  
**Review Frequency:** Monthly

---

## Alert: Store Context Missing

**Severity:** Critical (Sev-1)  
**Condition:** Store-scoped route without store_id in trace context  
**Threshold:** > 0 occurrences  
**Evaluation Window:** 1 minute  
**Cooldown:** 5 minutes

**Data Source:**
- Request trace context
- Metric: `store.context.coverage_rate`

**Trigger Logic:**
```
COUNT(requests WHERE route_requires_store = true AND store_id IS NULL) > 0
WHERE time_window = 1 minute
```

**Notification Channels:**
- Slack: `#alerts-critical` (immediate)
- PagerDuty: Platform Team (24/7, immediate)
- Email: Security Team + CTO

**Runbook:** `docs/runbooks/tenant-isolation-incident.md#store-context-missing`

**Immediate Actions:**
1. Identify affected route
2. Check if data was accessed without store scope
3. Freeze related deployments
4. Escalate to Security Team

**Possible Causes:**
- Middleware bypass
- Route misconfiguration
- Store context middleware failure
- New route without store scoping

---

## Alert: Store Mismatch Detected

**Severity:** Critical (Sev-1)  
**Condition:** Resolved store differs from route parameter  
**Threshold:** > 0 occurrences  
**Evaluation Window:** 1 minute  
**Cooldown:** 5 minutes

**Data Source:**
- Security event: `tenant.store_mismatch`
- Metric: `tenant.store_mismatch`

**Trigger Logic:**
```
COUNT(tenant.store_mismatch) > 0
WHERE time_window = 1 minute
```

**Notification Channels:**
- Slack: `#alerts-critical` (immediate)
- PagerDuty: Platform Team + Security Team (24/7, immediate)
- Email: CTO

**Runbook:** `docs/runbooks/tenant-isolation-incident.md#store-mismatch`

**Immediate Actions:**
1. Preserve evidence (correlation IDs, actor IDs, store IDs)
2. Determine if cross-store data was accessed
3. Freeze all deployments
4. Initiate Sev-1 incident response
5. Notify affected tenants if data exposure confirmed

**Possible Causes:**
- Store resolution logic error
- Membership lookup failure
- Session contamination
- Authorization bypass

---

## Alert: Cross-Store Access Attempt

**Severity:** High (Sev-2)  
**Condition:** Authorization denial due to store ownership mismatch  
**Threshold:** > 5 occurrences in 5 minutes  
**Evaluation Window:** 5 minutes  
**Cooldown:** 10 minutes

**Data Source:**
- Security event: `authorization.denied` (with store mismatch context)
- Metric: `authorization.cross_store_denied`

**Trigger Logic:**
```
COUNT(authorization.denied WHERE reason = 'store_mismatch') > 5
WHERE time_window = 5 minutes
```

**Notification Channels:**
- Slack: `#alerts-security`
- PagerDuty: Security Team (24/7)

**Runbook:** `docs/runbooks/tenant-isolation-incident.md#cross-store-access`

**Immediate Actions:**
1. Identify actor attempting cross-store access
2. Review actor's recent activity
3. Determine if malicious or accidental
4. Block actor if malicious pattern detected

**Possible Causes:**
- Malicious actor probing
- Frontend store-switching bug
- Membership cache staleness
- Policy misconfiguration

---

## Alert: Membership Resolution Failure Spike

**Severity:** High  
**Condition:** Membership resolution failures > 10 per minute  
**Threshold:** > 10 failures/minute  
**Evaluation Window:** 5 minutes  
**Cooldown:** 15 minutes

**Data Source:**
- Store context enrichment failures
- Metric: `membership.resolution.failed`

**Trigger Logic:**
```
COUNT(membership.resolution.failed) > 10
WHERE time_window = 1 minute
```

**Notification Channels:**
- Slack: `#alerts-platform`
- PagerDuty: Platform Team (business hours)

**Runbook:** `docs/runbooks/membership-investigation.md`

**Possible Causes:**
- Database connectivity issue
- Membership cache failure
- store_user pivot table corruption
- High load on membership queries

---

## Alert: Store Context Coverage Drop

**Severity:** Critical  
**Condition:** Store context coverage < 100% for store-scoped routes  
**Threshold:** < 100% coverage  
**Evaluation Window:** 5 minutes  
**Cooldown:** 10 minutes

**Data Source:**
- Request trace context
- Metric: `store.context.coverage_rate`

**Trigger Logic:**
```
(requests_with_store_id / total_store_scoped_requests) < 1.0
WHERE time_window = 5 minutes
AND total_store_scoped_requests > 10
```

**Notification Channels:**
- Slack: `#alerts-critical`
- PagerDuty: Platform Team (24/7)

**Runbook:** `docs/runbooks/tenant-isolation-incident.md#coverage-drop`

**Immediate Actions:**
1. Identify routes with missing store context
2. Check StoreContext middleware health
3. Review recent deployments
4. Freeze related deployments if widespread

**Possible Causes:**
- Middleware configuration error
- Route registration without middleware
- Store resolution service failure
- Recent deployment regression

---

## Alert: Repository Store Scope Violation (Wave 2 - DRAFT)

**Severity:** Critical  
**Condition:** Commerce query without store_id scope  
**Threshold:** > 0 violations  
**Evaluation Window:** 5 minutes  
**Cooldown:** 10 minutes  
**Status:** DRAFT (requires Wave 2 repository telemetry)

**Data Source:**
- Repository instrumentation (Wave 2)
- Metric: `repository.store_scope.violation`

**Trigger Logic:**
```
COUNT(repository_queries WHERE requires_store_scope = true AND store_id IS NULL) > 0
WHERE time_window = 5 minutes
```

**Notification Channels:**
- Slack: `#alerts-critical`
- PagerDuty: Platform Team (24/7)

**Runbook:** `docs/runbooks/repository-scope-violation.md` (DRAFT)

**Possible Causes:**
- Direct model query bypassing repository
- Repository method missing store scope
- Super-admin query incorrectly scoped

---

## Emergency Response Protocol

### Sev-1 Tenant Isolation Incident

**Trigger Conditions:**
- Store context missing
- Store mismatch detected
- Confirmed cross-store data access

**Immediate Actions (within 5 minutes):**
1. **Preserve Evidence:**
   - Capture correlation IDs
   - Capture actor IDs and store IDs
   - Capture request traces
   - Capture audit log entries

2. **Contain Incident:**
   - Freeze all deployments
   - Disable affected feature flags if applicable
   - Block malicious actors if identified

3. **Assess Impact:**
   - Determine which stores were affected
   - Determine what data was accessed
   - Determine if data was modified

4. **Escalate:**
   - Notify Security Team
   - Notify CTO
   - Initiate Sev-1 incident response

5. **Communicate:**
   - Internal: Engineering leadership
   - External: Affected tenants (if data exposure confirmed)
   - Compliance: Legal/compliance team (if required)

**Post-Incident (within 24 hours):**
- Root cause analysis
- Remediation plan
- Tenant notification (if required)
- Incident report
- Architecture review

---

## Alert Tuning

**Zero-Tolerance Policy:**
Tenant isolation alerts have zero-tolerance thresholds. Any non-zero count is a critical incident.

**No False Positives:**
These alerts must not produce false positives. Any false positive indicates a monitoring or instrumentation issue that must be fixed immediately.

**Threshold Review:**
Thresholds are reviewed monthly but should remain at zero-tolerance for isolation violations.

---

## Escalation Matrix

| Alert | Initial Response | Immediate Escalation |
|-------|------------------|----------------------|
| Store Context Missing | On-call engineer | Security Team + CTO |
| Store Mismatch | On-call engineer | Security Team + CTO |
| Cross-Store Access | Security Team | CTO (if malicious) |
| Membership Failure | Platform Team | Security Team (if sustained) |
| Coverage Drop | Platform Team | Security Team + CTO |

---

## Related Documentation

- Dashboard: `docs/dashboards/tenant-isolation-health.md`
- Runbook: `docs/runbooks/tenant-isolation-incident.md`
- Architecture: `docs/ARCHITECTURE.md` (Tenant Isolation Doctrine)
- Governance: `docs/EXECUTION_GOVERNANCE.md` (Tenant Isolation Verification)
