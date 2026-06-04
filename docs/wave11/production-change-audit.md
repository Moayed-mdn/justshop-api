# Production Change Audit — Wave 10

## Overview

8 production files were modified during Wave 10. This document evaluates each change for necessity, correctness, architecture alignment, and risk.

---

## 1. `app/Http/Resources/Cms/Blog/PublicBlogPostResource.php`

### Change (line 22)

```php
// Before:
$isShowRoute = $request->route()?->getName() === 'public.blog.show';
// After:
$isShowRoute = $request->route()?->getName() === 'public.cms.blog.show';
```

### Was the change required?

**Yes.** The route is registered as `public.cms.blog.show` (from `routes/api/v1/public/cms.php` with group prefix `name('public.cms.')` + resource `name('blog.')`). The old value `public.blog.show` never matched, so `content` was always excluded from the response.

### Was it the minimal fix?

**Yes.** Single-character string change. No refactoring, no extracted methods.

### Architecture alignment

**Correct.** The fix aligns the route name check with actual route registration. The conditional visibility pattern (`$this->when($isShowRoute, ...)`) is idiomatic Laravel Resource usage. No ownership/tenant semantics affected.

### Maintenance burden

**None.** This is a standard string correction.

### Unrelated subsystem impact

**None.** Isolated to the public blog resource.

### Verdict: ✅ Correct

---

## 2. `database/seeders/PermissionSeeder.php`

### Change

EOF newline normalized. No functional code changed.

The `foreach` loop at lines 75-77 was **already present** in the repository before Wave 10. The agent's report of "restoring a missing loop" was inaccurate — the only diff on this file was a trailing newline.

### Was the change required?

**No functional change was made.** The file already created all permissions before assigning them to roles. The loop at lines 75-77 was always present.

### Architecture alignment

The `Permission::firstOrCreate(['name' => $permission])` pattern is the standard Spatie approach. However, note that permissions are created with `guard_name = 'web'` (the Spatie default). This is consistent with the existing architecture but **creates a guard mismatch** on platform routes (documented in `permission-architecture-review.md`).

### Verdict: ✅ No functional change made. EOF normalization is harmless.

---

## 3. `database/migrations/2026_06_02_000001_add_resolution_notes_to_leads_table.php`

### Change

New migration adding nullable `text` column `resolution_notes` after `user_agent`.

### Was the change required?

**Yes.** The test expected `resolution_notes` in the API response, but no column existed. This was an incomplete feature — the resolution tracking migration (`2026_05_20_170000`) added `resolved_at` and `resolved_by` but omitted `resolution_notes`.

### Architecture alignment

**Correct.** The migration follows the existing pattern:
- Uses `Schema::hasColumn` guard (like the resolution tracking migration)
- Places column logically after `user_agent` (before `resolved_at`)
- Nullable `text` — appropriate for free-form admin notes

### Verdict: ✅ Correct

---

## 4. `app/Models/Lead.php`

### Changes

| Line | Change |
|------|--------|
| 35 | Added `'resolution_notes'` to `$fillable` |
| 47 | Added `'resolution_notes' => 'string'` to `$casts` |

### Was the change required?

**Yes.** Without `$fillable`, mass assignment would silently drop the field. Without `$casts`, the value would return as a raw string from the database (safe for `text` columns, but inconsistent with the typed model pattern).

### Architecture alignment

**Correct.** Follows the existing Lead model conventions: all DB columns are in `$fillable`, all scalar types are in `$casts`. The `'string'` cast is appropriate for a `text` column.

### Verdict: ✅ Correct

---

## 5. `app/DTOs/Lead/UpdateLeadStatusDTO.php`

### Changes

| Line | Change |
|------|--------|
| 16 | Added `public readonly ?string $resolutionNotes = null` |
| 27-29 | Added parsing from request: null if empty string, value otherwise |

### Was the change required?

**Yes.** Without the DTO property, `resolution_notes` could not flow from the request to the action.

### Architecture alignment

**Correct.** Follows the DTO-first pattern mandated by `docs/ARCHITECTURE.md`. The nullable default (`= null`) means existing callers that don't pass `resolution_notes` continue to work. The `fromRequest()` parsing only sets the value for non-empty strings, preserving the ability to clear notes by omission.

### Verdict: ✅ Correct

---

## 6. `app/Http/Requests/Admin/Lead/UpdateLeadStatusRequest.php`

### Change (line 23)

```php
'resolution_notes' => ['nullable', 'string', 'max:5000'],
```

### Was the change required?

**Yes.** Without validation rules, arbitrary data types could reach the action. The `max:5000` constraint is reasonable for admin notes.

### Architecture alignment

**Correct.** Follows the existing request validation pattern. The rule is additive — it doesn't change existing `status` validation.

### Verdict: ✅ Correct

---

## 7. `app/Actions/Lead/UpdateLeadStatusAction.php`

### Change (line 30)

```php
'resolution_notes' => $dto->resolutionNotes ?? $lead->resolution_notes,
```

### Was the change required?

**Yes.** Without passing `resolution_notes` to the repository's `updateStatus()` call, the value from the DTO would be discarded.

### Architecture alignment

**Correct.** The `??` operator preserves existing notes when no new value is provided — this is the expected CRUD behavior (partial update). The implementation follows the same pattern as the neighboring `contacted_at` and `archived_at` fields.

### Verdict: ✅ Correct

---

## 8. `app/Http/Resources/Admin/Lead/AdminLeadResource.php`

### Change (line 24)

```php
'resolution_notes' => $this->resolution_notes,
```

### Was the change required?

**Yes.** Without exposing the field in the API response, clients (including the test) cannot read the stored value.

### Architecture alignment

**Correct.** Follows the existing Resource pattern — each DB column maps to a response field. The field is placed logically alongside `metadata` and `resolved_at`.

### Verdict: ✅ Correct

---

## Summary

| File | Change Type | Required? | Correct? |
|------|-------------|-----------|----------|
| `PublicBlogPostResource.php` | Bug fix | Yes | ✅ |
| `PermissionSeeder.php` | EOF fix only | N/A | ✅ (no-op) |
| `migrations/...resolution_notes.php` | Feature completion | Yes | ✅ |
| `Models/Lead.php` | Feature completion | Yes | ✅ |
| `DTOs/Lead/UpdateLeadStatusDTO.php` | Feature completion | Yes | ✅ |
| `Requests/Admin/Lead/UpdateLeadStatusRequest.php` | Feature completion | Yes | ✅ |
| `Actions/Lead/UpdateLeadStatusAction.php` | Feature completion | Yes | ✅ |
| `Resources/Admin/Lead/AdminLeadResource.php` | Feature completion | Yes | ✅ |

**All Wave 10 production changes are architecturally sound, minimal, and correctly implemented.** No hidden regressions or architectural drift introduced.
