# Auth Anomalies Alert Specifications

**Owner:** Auth Team  
**Escalation:** Security Team (for Sev-1/Sev-2)  
**Review Frequency:** Quarterly

---

## Alert: High Failed Login Rate

**Severity:** High  
**Condition:** Failed login rate > 10% over 5 minutes  
**Threshold:** > 10% failure rate  
**Evaluation Window:** 5 minutes  
**Cooldown:** 15 minutes

**Data Source:**
- Security event: `auth.login.failed`
- Metric: `auth.login.success_rate`

**Trigger Logic:**
```
(failed_logins / total_login_attempts) > 0.10
WHERE time_window = 5 minutes
```

**Notification Channels:**
- Slack: `#alerts-auth`
- PagerDuty: Auth Team (business hours)

**Runbook:** `docs/runbooks/auth-incident-response.md#high-failed-login-rate`

**Possible Causes:**
- Credential stuffing attack
- Password reset wave
- Auth service degradation
- Frontend integration issue

---

## Alert: Login Success Rate Drop

**Severity:** Critical  
**Condition:** Login success rate < 95% over 10 minutes  
**Threshold:** < 95% success rate  
**Evaluation Window:** 10 minutes  
**Cooldown:** 10 minutes

**Data Source:**
- Security event: `auth.login.failed`
- Metric: `auth.login.success_rate`

**Trigger Logic:**
```
(successful_logins / total_login_attempts) < 0.95
WHERE time_window = 10 minutes
AND total_login_attempts > 10
```

**Notification Channels:**
- Slack: `#alerts-critical`
- PagerDuty: Auth Team (24/7)
- Email: On-call engineer

**Runbook:** `docs/runbooks/auth-incident-response.md#login-outage`

**Possible Causes:**
- Auth service outage
- Database connectivity issue
- Session storage failure
- Guard configuration error

---

## Alert: Onboarding Gate Denial Spike

**Severity:** Medium  
**Condition:** Onboarding denials > 20 per minute  
**Threshold:** > 20 denials/minute  
**Evaluation Window:** 5 minutes  
**Cooldown:** 30 minutes

**Data Source:**
- Security event: `auth.onboarding.denied`
- Metric: `auth.onboarding.denied`

**Trigger Logic:**
```
COUNT(auth.onboarding.denied) > 20
WHERE time_window = 1 minute
```

**Notification Channels:**
- Slack: `#alerts-product`

**Runbook:** `docs/runbooks/onboarding-investigation.md`

**Possible Causes:**
- Onboarding flow regression
- Merchant signup wave
- Onboarding step misconfiguration

---

## Alert: Authorization Denial Spike

**Severity:** High  
**Condition:** Authorization denials > 100 per minute  
**Threshold:** > 100 denials/minute  
**Evaluation Window:** 5 minutes  
**Cooldown:** 15 minutes

**Data Source:**
- Security event: `authorization.denied`
- Metric: `authorization.denied`

**Trigger Logic:**
```
COUNT(authorization.denied) > 100
WHERE time_window = 1 minute
```

**Notification Channels:**
- Slack: `#alerts-auth`
- PagerDuty: Platform Team (business hours)

**Runbook:** `docs/runbooks/authorization-investigation.md`

**Possible Causes:**
- Policy regression
- Permission resolution failure
- RBAC configuration error
- Frontend permission check mismatch

---

## Alert: Identity Context Mismatch

**Severity:** Critical  
**Condition:** Any identity context mismatch detected  
**Threshold:** > 0 mismatches  
**Evaluation Window:** 5 minutes  
**Cooldown:** 5 minutes

**Data Source:**
- Identity telemetry: `identity.actor_domain.mismatch`
- Metric: `identity.actor_domain.mismatch`

**Trigger Logic:**
```
COUNT(identity.actor_domain.mismatch) > 0
WHERE time_window = 5 minutes
```

**Notification Channels:**
- Slack: `#alerts-critical`
- PagerDuty: Security Team (24/7)

**Runbook:** `docs/runbooks/identity-mismatch-incident.md`

**Possible Causes:**
- Session contamination
- Actor classification error
- Route domain misconfiguration
- Guard split preparation issue (Wave 3)

---

## Alert: Guard Shadow Anomaly (Wave 3B - DRAFT)

**Severity:** Medium  
**Condition:** Guard shadow mismatch rate > 1%  
**Threshold:** > 1% mismatch rate  
**Evaluation Window:** 10 minutes  
**Cooldown:** 30 minutes  
**Status:** DRAFT (Wave 3B)

**Data Source:**
- Guard split simulation: `auth.guard.split_mismatch_detected`
- Session guard telemetry: `guard.shadow.mismatch_detected`
- Metric: `guard.shadow.mismatch`

**Trigger Logic:**
```
(guard_mismatches / total_requests) > 0.01
WHERE time_window = 10 minutes
AND guard.shadow.enabled = true
```

**Notification Channels:**
- Slack: `#alerts-wave3`

**Runbook:** `docs/runbooks/guard-split-readiness.md` (DRAFT)

**Possible Causes:**
- Guard split readiness issue
- Session ownership ambiguity
- Route domain classification error

---

## Alert Maintenance

**Review Schedule:** Quarterly  
**Threshold Tuning:** Based on 30-day baseline  
**False Positive Target:** < 5% per alert

**Tuning Process:**
1. Collect 30 days of baseline data
2. Calculate p95 and p99 thresholds
3. Adjust thresholds to balance sensitivity and noise
4. Document threshold changes in alert history

---

## Escalation Matrix

| Severity | Initial Response | Escalation (15 min) | Escalation (30 min) |
|----------|------------------|---------------------|---------------------|
| Critical | On-call engineer | Security Team Lead | CTO |
| High | Auth Team | Platform Team Lead | Engineering Manager |
| Medium | Auth Team | - | - |
| Low | Slack notification | - | - |

---

## Related Documentation

- Dashboard: `docs/dashboards/auth-identity-boundaries.md`
- Runbooks: `docs/runbooks/auth-incident-response.md`
- Architecture: `docs/ARCHITECTURE.md` (Identity Context Doctrine)
