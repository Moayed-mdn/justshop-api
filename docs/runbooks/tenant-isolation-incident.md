# Tenant Isolation Incident Response Runbook

**Owner:** Platform Team  
**Severity:** Sev-1 (Critical)  
**Last Updated:** 2026-05-23  
**Status:** VERIFIED

---

## Purpose

This runbook provides step-by-step procedures for responding to tenant isolation violations.

**Critical Rule:** Tenant isolation incidents are always Sev-1. No exceptions.

---

## Incident Classification

### Sev-1: Confirmed Isolation Breach
- Store context missing on store-scoped route
- Store mismatch detected
- Cross-store data access confirmed
- Repository query without store scope

### Sev-2: Potential Isolation Risk
- Multiple cross-store access attempts
- Membership resolution failures
- Store context coverage drop

---

## Incident Response Procedures

### Procedure 1: Store Context Missing {#store-context-missing}

**Trigger:** Alert `Store Context Missing`  
**Severity:** Sev-1

#### Step 1: Immediate Evidence Preservation (0-2 minutes)

```bash
# Capture correlation ID from alert
CORRELATION_ID="<from_alert>"

# Search logs for the incident
grep "$CORRELATION_ID" storage/logs/laravel.log > /tmp/incident-${CORRELATION_ID}.log
grep "$CORRELATION_ID" storage/logs/security.log >> /tmp/incident-${CORRELATION_ID}.log

# Capture request trace context
grep "correlation_id.*${CORRELATION_ID}" storage/logs/*.log
```

**Capture:**
- Correlation ID
- Route accessed
- Actor ID (if available)
- Timestamp
- Request method and path

#### Step 2: Assess Impact (2-5 minutes)

**Questions to answer:**
1. Which route was accessed without store context?
2. Was any data queried or modified?
3. Which actor made the request?
4. Is this a single incident or pattern?

```bash
# Check if route is store-scoped
php artisan route:list | grep "<route_from_incident>"

# Check recent requests to same route
grep "<route_path>" storage/logs/laravel.log | tail -20

# Check actor's recent activity
grep "actor_id.*<actor_id>" storage/logs/security.log | tail -50
```

#### Step 3: Contain (5-10 minutes)

**If data access occurred:**
```bash
# Freeze all deployments
# Contact deployment team immediately

# Disable affected route if possible (emergency only)
# This requires code change - coordinate with team
```

**If no data access:**
```bash
# Identify root cause
# Fix middleware configuration
# Deploy fix with priority
```

#### Step 4: Escalate (Immediate)

**Notify:**
- Security Team (immediate)
- CTO (immediate)
- Engineering Manager

**Communication Template:**
```
TENANT ISOLATION INCIDENT - SEV-1

Correlation ID: <correlation_id>
Route: <route>
Actor: <actor_id>
Store Context: MISSING
Data Access: <YES/NO/UNKNOWN>
Affected Stores: <store_ids or UNKNOWN>

Actions Taken:
- Evidence preserved
- Deployments frozen
- Investigation in progress

Next Steps:
- Root cause analysis
- Impact assessment
- Remediation plan
```

#### Step 5: Root Cause Analysis (10-30 minutes)

**Common Causes:**

1. **Middleware Bypass:**
   - Check route registration
   - Verify middleware stack
   - Review recent route changes

2. **Middleware Failure:**
   - Check StoreContext middleware logs
   - Verify store resolution service
   - Check database connectivity

3. **New Route Without Middleware:**
   - Review recent commits
   - Check route definition
   - Verify middleware assignment

#### Step 6: Remediation

**Immediate Fix:**
- Add missing middleware
- Fix store resolution logic
- Deploy with priority

**Verification:**
```bash
# Test the fixed route
curl -H "Authorization: Bearer <token>" \
     https://api.example.com/api/v1/stores/123/<route>

# Verify store context in logs
grep "store_id" storage/logs/laravel.log | tail -5
```

#### Step 7: Post-Incident

- Document root cause
- Update route governance checks
- Add CI detection if applicable
- Review similar routes
- Tenant notification (if data exposure confirmed)

---

### Procedure 2: Store Mismatch Detected {#store-mismatch}

**Trigger:** Alert `Store Mismatch Detected`  
**Severity:** Sev-1

#### Step 1: Immediate Evidence Preservation (0-2 minutes)

```bash
# Capture incident details from security log
CORRELATION_ID="<from_alert>"
grep "tenant.store_mismatch.*${CORRELATION_ID}" storage/logs/security.log

# Capture full request context
grep "$CORRELATION_ID" storage/logs/*.log > /tmp/store-mismatch-${CORRELATION_ID}.log
```

**Capture:**
- Expected store ID
- Actual store ID
- Actor ID
- Route accessed
- Membership ID
- Correlation ID

#### Step 2: Assess Data Exposure (2-5 minutes)

**Critical Questions:**
1. Was data from the wrong store accessed?
2. Was data modified?
3. Which stores are affected?
4. Is this isolated or pattern?

```bash
# Check audit logs for data access
php artisan tinker
>>> \App\Models\AuditLog::where('correlation_id', '<correlation_id>')->get();

# Check if actor has membership in both stores
php artisan tinker
>>> $actor = \App\Models\User::find(<actor_id>);
>>> $actor->stores()->pluck('id');
```

#### Step 3: Contain (Immediate)

```bash
# FREEZE ALL DEPLOYMENTS
# Contact deployment team immediately

# If malicious actor suspected, block immediately
php artisan tinker
>>> $user = \App\Models\User::find(<actor_id>);
>>> $user->update(['is_active' => false]);
```

#### Step 4: Escalate (Immediate)

**Notify:**
- Security Team (immediate)
- CTO (immediate)
- Legal/Compliance (if data exposure confirmed)

**Incident Declaration:**
```
TENANT ISOLATION BREACH - SEV-1

Expected Store: <store_id>
Actual Store: <store_id>
Actor: <actor_id>
Data Accessed: <YES/NO/INVESTIGATING>
Data Modified: <YES/NO/INVESTIGATING>

DEPLOYMENTS FROZEN
INVESTIGATION IN PROGRESS
```

#### Step 5: Impact Assessment (5-30 minutes)

**Determine:**
1. Root cause of mismatch
2. Extent of data exposure
3. Affected tenants
4. Timeline of exposure

```bash
# Search for similar incidents
grep "tenant.store_mismatch" storage/logs/security.log | grep "<actor_id>"

# Check actor's full activity history
php artisan tinker
>>> \App\Models\AuditLog::where('actor_id', <actor_id>)
    ->where('created_at', '>', now()->subHours(24))
    ->get();
```

#### Step 6: Tenant Notification (If Required)

**If data exposure confirmed:**
1. Prepare incident summary
2. Identify affected tenants
3. Coordinate with Legal/Compliance
4. Notify affected tenants
5. Document notification

#### Step 7: Remediation

**Immediate:**
- Fix store resolution logic
- Add additional validation
- Deploy fix with priority

**Long-term:**
- Architecture review
- Add runtime assertions
- Enhance monitoring
- Update policies

---

### Procedure 3: Cross-Store Access Attempt {#cross-store-access}

**Trigger:** Alert `Cross-Store Access Attempt`  
**Severity:** Sev-2 (High)

#### Step 1: Identify Pattern (0-5 minutes)

```bash
# Get actor details
ACTOR_ID="<from_alert>"
grep "authorization.denied.*store_mismatch.*actor_id.*${ACTOR_ID}" storage/logs/security.log

# Check frequency
grep "authorization.denied.*${ACTOR_ID}" storage/logs/security.log | wc -l
```

**Determine:**
- Is this a single actor or multiple?
- Is this malicious probing or accidental?
- What stores are being targeted?

#### Step 2: Assess Threat Level

**Malicious Indicators:**
- Multiple stores targeted
- Systematic probing pattern
- Rapid succession attempts
- No legitimate membership

**Accidental Indicators:**
- Single store target
- User has membership in target store
- Frontend navigation error
- Isolated incident

#### Step 3: Response

**If Malicious:**
```bash
# Block actor immediately
php artisan tinker
>>> $user = \App\Models\User::find(<actor_id>);
>>> $user->update(['is_active' => false]);

# Notify Security Team
# Preserve evidence for investigation
```

**If Accidental:**
```bash
# Investigate frontend issue
# Check store-switching logic
# Review membership cache
# Monitor for recurrence
```

#### Step 4: Root Cause

**Common Causes:**
- Frontend store-switching bug
- Stale membership cache
- Policy misconfiguration
- Malicious actor

---

## Verification Procedures

### Verify Store Context Coverage

```bash
# Run readiness check
php artisan architecture:wave1-readiness --json | jq '.operational_foundations'

# Check recent requests
grep "store_id" storage/logs/laravel.log | tail -100 | grep -c "store_id"
```

### Verify Tenant Isolation

```bash
# Run isolation tests
php artisan test --filter TenantIsolation

# Check audit logs
php artisan tinker
>>> \App\Models\AuditLog::whereDate('created_at', today())
    ->select('store_id', \DB::raw('count(*) as count'))
    ->groupBy('store_id')
    ->get();
```

---

## Communication Templates

### Internal Notification (Sev-1)

```
Subject: [SEV-1] Tenant Isolation Incident

Incident ID: <correlation_id>
Severity: Sev-1
Status: INVESTIGATING

Summary:
<brief description>

Impact:
- Affected Stores: <count or UNKNOWN>
- Data Exposure: <YES/NO/INVESTIGATING>
- Data Modification: <YES/NO/INVESTIGATING>

Actions Taken:
- Evidence preserved
- Deployments frozen
- <other actions>

Next Steps:
- <immediate next steps>

Incident Commander: <name>
```

### Tenant Notification (If Required)

```
Subject: Security Incident Notification

Dear <tenant_name>,

We are writing to inform you of a security incident that may have affected your account.

Incident Summary:
On <date> at <time>, we detected <brief description>.

Impact:
<specific impact to this tenant>

Actions Taken:
<remediation steps>

Your Action Required:
<any required tenant actions>

We take security seriously and have implemented additional safeguards to prevent recurrence.

For questions, contact: security@example.com
```

---

## Post-Incident Review

### Required Artifacts

1. **Incident Timeline**
2. **Root Cause Analysis**
3. **Impact Assessment**
4. **Remediation Plan**
5. **Prevention Measures**
6. **Tenant Notifications** (if applicable)

### Review Meeting

**Attendees:**
- Incident Commander
- Platform Team Lead
- Security Team Lead
- Engineering Manager
- CTO (for Sev-1)

**Agenda:**
1. Incident timeline review
2. Root cause analysis
3. Impact assessment
4. Response effectiveness
5. Prevention measures
6. Action items

---

## Related Documentation

- Alert: `docs/alerts/tenant-isolation-violations.md`
- Dashboard: `docs/dashboards/tenant-isolation-health.md`
- Architecture: `docs/ARCHITECTURE.md` (Tenant Isolation Rules)
- Governance: `docs/EXECUTION_GOVERNANCE.md` (Tenant Isolation Verification)
