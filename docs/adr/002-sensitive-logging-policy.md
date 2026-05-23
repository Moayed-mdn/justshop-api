# ADR-002: Sensitive Data Logging Policy

**Status:** VERIFIED_COMPLETE  
**Date:** 2026-05-23  
**Wave:** Wave 1  
**Deciders:** Security Team, Platform Team  
**Consulted:** Compliance Team

---

## Context

Logging is essential for observability, but sensitive data in logs creates:

- **Security Risk:** Credentials, tokens, signatures exposed in logs
- **Compliance Risk:** PII, payment data, session identifiers in logs
- **Incident Risk:** Logs become attack vectors if compromised

**Verified Incidents:**
- Query strings containing signatures logged directly
- Token parameters logged in debug statements
- Authorization headers potentially logged in request dumps

Without centralized redaction, sensitive data leakage is inevitable.

---

## Decision

Implement a centralized sensitive data redaction system with:

### 1. Redaction Configuration

**Location:** `config/observability.php`

**Sensitive Keys:**
- `password`, `password_confirmation`
- `token`, `access_token`, `refresh_token`, `remember_token`
- `secret`, `signature`
- `authorization`, `cookie`, `set-cookie`
- `csrf`, `xsrf`
- `session`, `session_id`, `sessionid`, `session_ownership_key`
- `sigheader`, `webhook_secret`

**Sensitive Query Parameters:**
- `token`, `access_token`, `refresh_token`
- `signature`
- `password`, `authorization`
- `cookie`, `session`, `session_id`
- `xsrf-token`, `x-xsrf-token`

### 2. Centralized Sanitizer

**Location:** `app/Support/Observability/MetadataSanitizer.php`

**Capabilities:**
- Recursive array sanitization
- Object sanitization
- String pattern matching
- Query parameter redaction
- Key-based redaction
- Configurable placeholder
- String length limits

### 3. Log Processor Integration

**Location:** `app/Logging/SanitizeSensitiveLogData.php`

**Integration:**
- Monolog processor
- Applied to all log channels
- Sanitizes message, context, and extra data
- Configurable enable/disable

**Log Channels:**
- `stack`, `single`, `daily`, `security`, `slack`, `papertrail`, `stderr`, `syslog`, `errorlog`

### 4. Forbidden Patterns

**CI Detection:** `php artisan architecture:detect-forbidden-patterns`

**Detected Patterns:**
- Direct logging of sensitive fields
- Logging request query strings
- Logging authorization headers
- Logging session data
- Logging tokens or signatures

### 5. Remediation

**Completed:**
- ✅ Removed signature logging in `VerifyEmail.php`
- ✅ Removed direct `env()` usage in application layer
- ✅ Centralized redaction in all log channels

---

## Policy Rules

### Rule 1: Never Log Sensitive Data Directly

**Forbidden:**
```php
Log::info('Query string: ' . request()->getQueryString());
Log::info('Token: ' . $token);
Log::info('Authorization: ' . request()->header('Authorization'));
```

**Allowed:**
```php
Log::info('Request processed', [
    'correlation_id' => $correlationId,
    'route' => $route,
    // Sensitive data automatically redacted by sanitizer
]);
```

### Rule 2: Use Structured Logging

**Forbidden:**
```php
Log::info('User ' . $userId . ' accessed store ' . $storeId);
```

**Allowed:**
```php
Log::info('User accessed store', [
    'user_id' => $userId,
    'store_id' => $storeId,
]);
```

### Rule 3: Rely on Centralized Redaction

**Do NOT:**
- Manually redact in application code
- Create custom redaction logic
- Bypass sanitizer

**Do:**
- Trust centralized sanitizer
- Add new sensitive keys to config
- Use structured context

### Rule 4: CI Enforcement

**Required:**
- All PRs must pass forbidden pattern detection
- New sensitive patterns must be added to detection
- Violations block deployment

---

## Consequences

### Positive

- **Security:** Sensitive data automatically redacted
- **Compliance:** PII and credentials protected
- **Consistency:** Centralized redaction logic
- **Observability:** Debugging capability preserved
- **CI Enforcement:** Violations detected automatically

### Negative

- **Performance:** Minimal overhead from sanitization
- **Debugging:** Some data redacted that might be useful
- **False Positives:** Overly aggressive redaction possible

### Risks

- **Bypass:** Developers might disable redaction
- **Incomplete Coverage:** New sensitive fields might be missed
- **Configuration Drift:** Sensitive keys list might become stale

### Mitigations

- Redaction enabled by default
- CI detection for new patterns
- Quarterly review of sensitive keys
- Security team ownership

---

## Implementation Status

**Wave 1 Status:** VERIFIED_COMPLETE

**Implemented:**
- ✅ Redaction configuration
- ✅ MetadataSanitizer class
- ✅ Log processor integration
- ✅ All log channels protected
- ✅ CI detection command
- ✅ GitHub Actions enforcement
- ✅ Sensitive logging violations remediated

**Verified Remediation:**
- ✅ `VerifyEmail.php` signature logging removed
- ✅ Direct `env()` usage removed
- ✅ Zero sensitive logging violations

---

## Compliance Notes

**GDPR/Privacy:**
- PII automatically redacted in logs
- Session identifiers protected
- User identifiers hashed where appropriate

**PCI DSS:**
- Payment tokens redacted
- Card data never logged
- Webhook secrets protected

**SOC 2:**
- Audit trail preserved
- Sensitive data protected
- Access controls maintained

---

## Related Documentation

- Config: `config/observability.php`
- Sanitizer: `app/Support/Observability/MetadataSanitizer.php`
- Log Processor: `app/Logging/SanitizeSensitiveLogData.php`
- Detection: `app/Console/Commands/Architecture/DetectForbiddenPatterns.php`
- Governance: `docs/EXECUTION_GOVERNANCE.md` (Observability Before Async)

---

## Review Schedule

- **Next Review:** 2026-08-23 (Quarterly)
- **Sensitive Keys Audit:** Monthly
- **Pattern Detection Update:** As needed
