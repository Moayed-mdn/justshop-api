# ADR-001: Feature Flag Governance System

**Status:** DRAFT  
**Date:** 2026-05-23  
**Wave:** Wave 1  
**Deciders:** Platform Team, Operations Team  
**Consulted:** Security Team, Engineering Leadership

---

## Context

The platform evolution roadmap requires safe, reversible migrations across multiple waves. Feature flags are the primary mechanism for:

- Authority cutover control
- Shadow-read and dual-read enablement
- Kill switches for high-risk features
- Canary rollout control
- Rollback capability

Without governance, feature flags become:
- Undocumented
- Orphaned
- Inconsistently named
- Missing ownership metadata
- Lacking expiry tracking

This creates operational risk and prevents safe rollback.

---

## Decision

Implement a canonical feature flag governance system with:

### 1. Canonical Registry

**Location:** `config/features.php`

**Required Metadata:**
- `default`: Default state (boolean or string)
- `owner`: Technical owner team
- `business_owner`: Business/operations owner
- `description`: Human-readable purpose
- `blast_radius`: Impact scope (platform-wide, domain-specific, etc.)
- `rollback_effect`: What happens when flag is disabled
- `expiry_milestone`: When flag should be removed
- `category`: Domain classification
- `introduced_wave`: Which wave introduced the flag
- `kill_switch`: Whether this is an emergency kill switch

### 2. Naming Convention

**Format:** `<domain>.<capability>.<mode>`

**Modes:**
- `enabled`: Feature enablement
- `shadow`: Shadow-read mode
- `dual_read`: Dual-read parity mode
- `dual_write`: Dual-write mode
- `authority`: Authority cutover
- `kill`: Emergency kill switch
- `strict`: Strict enforcement mode

**Examples:**
- `bootstrap.v2.enabled`
- `bootstrap.shadow_read`
- `membership.dual_read`
- `auth.guard_split.enabled`
- `async.listener.orders.kill`

### 3. Helper Class

**Location:** `app/Support/FeatureFlags/FeatureFlag.php`

**Methods:**
- `enabled(string $flag): bool`
- `disabled(string $flag): bool`
- `metadata(string $flag): ?array`
- `all(): array`
- `byCategory(string $category): array`
- `byWave(string $wave): array`
- `killSwitches(): array`
- `validate(string $flag): array`
- `validateAll(): array`

### 4. Governance Validation

**Command:** `php artisan architecture:validate-feature-flags`

**Validation Rules:**
- All flags must have complete metadata
- Flags without metadata fail validation
- CI enforcement via GitHub Actions
- Machine-readable output for automation

### 5. CI Enforcement

**Workflow:** `.github/workflows/wave1-governance.yml`

**Enforcement:**
- Validate all flags on PR
- Fail CI if invalid flags detected
- Generate flag inventory report
- Track flag count by wave and category

---

## Consequences

### Positive

- **Explicit Ownership:** Every flag has technical and business owners
- **Expiry Tracking:** Flags have defined cleanup milestones
- **Rollback Clarity:** Rollback effects are documented
- **Kill Switch Inventory:** Emergency controls are discoverable
- **CI Enforcement:** Governance violations block deployment
- **Operational Visibility:** Flag inventory is machine-readable

### Negative

- **Metadata Overhead:** Each flag requires complete metadata
- **Validation Friction:** Invalid flags block CI
- **Migration Effort:** Existing flags need metadata backfill

### Risks

- **Metadata Drift:** Metadata may become stale if not maintained
- **Validation Bypass:** Developers may disable validation under pressure
- **Orphaned Flags:** Flags may outlive their expiry milestones

### Mitigations

- Quarterly flag review process
- Automated expiry milestone alerts
- Flag cleanup as part of wave completion
- Governance validation in CI (non-bypassable)

---

## Implementation Status

**Wave 1 Status:** VERIFIED_COMPLETE

**Implemented:**
- ✅ Canonical registry (`config/features.php`)
- ✅ Helper class (`FeatureFlag.php`)
- ✅ Validation command
- ✅ CI enforcement workflow
- ✅ 30+ flags registered with metadata
- ✅ Kill switch inventory

**Remaining Work:**
- Quarterly review process (operational)
- Expiry milestone automation (future)

---

## Related Documentation

- Config: `config/features.php`
- Helper: `app/Support/FeatureFlags/FeatureFlag.php`
- Command: `app/Console/Commands/Architecture/ValidateFeatureFlags.php`
- Governance: `docs/EXECUTION_GOVERNANCE.md` (Feature Flag Strategy)
- CI: `.github/workflows/wave1-governance.yml`

---

## Review Schedule

- **Next Review:** 2026-08-23 (Quarterly)
- **Expiry Review:** Per wave completion
- **Metadata Audit:** Monthly
