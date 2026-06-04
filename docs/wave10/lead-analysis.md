# Lead Test Analysis

## Failure A: `admin endpoints require super admin role`

### Root Cause

**Test expectation outdated.** The test expected error code `HTTP_403` from the `LeadPolicy`, but the identity middleware (`ApplyIdentityRouteContext`) intercepts the request **before** the policy fires.

The middleware chain on platform routes is:
```
identity.route:platform,platform,enforce
  → ApplyIdentityRouteContext
    → Checks if user has platform identity domain
    → Non-super-admin users do NOT → throws InvalidIdentityDomainAccessException
    → Code: IDENTITY_DOMAIN_MISMATCH
  → (policy never reached)
```

### Fix

```php
// AdminLeadManagementTest.php:38-41
->assertJson([
    'success' => false,
    'code' => 'IDENTITY_DOMAIN_MISMATCH',
]);
```

### Regression Risk

**None.** Test-only change. The error code is correct — the middleware does block non-super-admin users, just with a different code than the test expected.

---

## Failure B: `super admin can update status and resolution fields`

### Root Cause

**Production bug — incomplete feature implementation.** The `resolution_notes` field was referenced in the test but never implemented through the full stack:

| Layer | Missing |
|-------|---------|
| Database | No `resolution_notes` column in `leads` table (previous migration `2026_05_20_170000` only added `resolved_at` / `resolved_by`) |
| Model | Not in `$fillable` or `$casts` |
| DTO | `UpdateLeadStatusDTO` had no `resolutionNotes` property |
| Request | `UpdateLeadStatusRequest` had no validation rule for `resolution_notes` |
| Action | `UpdateLeadStatusAction::execute` did not pass `resolution_notes` to the repository |
| Resource | `AdminLeadResource` did not include `resolution_notes` in response |

### Fix

| File | Change |
|------|--------|
| `database/migrations/2026_06_02_000001_add_resolution_notes_to_leads_table.php` | NEW: adds nullable `resolution_notes` text column |
| `app/Models/Lead.php` | Added `'resolution_notes'` to `$fillable` and `$casts` (type `'string'`) |
| `app/DTOs/Lead/UpdateLeadStatusDTO.php` | Added `public readonly ?string $resolutionNotes = null` + parsing from request |
| `app/Http/Requests/Admin/Lead/UpdateLeadStatusRequest.php` | Added `'resolution_notes' => ['nullable', 'string', 'max:5000']` |
| `app/Actions/Lead/UpdateLeadStatusAction.php` | Added `'resolution_notes' => $dto->resolutionNotes ?? $lead->resolution_notes` |
| `app/Http/Resources/Admin/Lead/AdminLeadResource.php` | Added `'resolution_notes' => $this->resolution_notes` |

### Regression Risk

**Low.** Field is nullable. Existing leads get `resolution_notes = null` which is the same as before (no column). API clients that don't send `resolution_notes` see no change. The migration checks `Schema::hasColumn` for safety.

---

## Failure C: `super admin can delete leads`

### Root Cause

**Test assertion mismatch.** The `Lead` model uses `SoftDeletes` trait. The `delete()` method performs a soft delete (sets `deleted_at`). `assertDatabaseMissing('leads', ['id' => $lead->id])` finds the soft-deleted row because it doesn't filter by `deleted_at IS NULL`.

### Fix

```php
// AdminLeadManagementTest.php:167
$this->assertSoftDeleted($lead);
```

### Regression Risk

**None.** Test-only change. `assertSoftDeleted` correctly checks that the row exists with a non-null `deleted_at`.

---

## Failure D: `duplicate detection blocks same submission within window`

### Root Cause

**Test uses wrong URL.** The test hard-coded `/api/v1/leads/contact` but the actual route is registered at `/api/v1/public/leads/contact` (under the `public` prefix in `routes/api.php:271-275`). This caused `NotFoundHttpException` which maps to error code `STR_001` (404).

Additionally, the test used outdated assertion keys `'status'` and `'error_code'` instead of the current `'success'` and `'code'`.

### Fix

```php
// PublicLeadSubmissionTest.php:105,108
// Before:
$this->postJson('/api/v1/leads/contact', ...)
// After:
$this->postJson(route('public.leads.contact'), ...)

// Assertion keys updated:
'status' => false  →  'success' => false
'error_code' => 'VAL_001'  →  'code' => 'VAL_001'
```

### Regression Risk

**None.** Test-only change. Using `route()` ensures URL matches the route definition. Assertion keys now match the actual API response format.
