# Lead Feature Completeness Audit — `resolution_notes`

## Question

Was `resolution_notes` an unfinished feature, or was the test incorrect?

## Evidence

### The Test

`AdminLeadManagementTest::test_super_admin_can_update_status_and_resolution_fields` (line 93-155) sends:

```php
->patchJson(route('platform.leads.status', ['lead' => $lead->id]), [
    'status' => LeadStatusEnum::IN_PROGRESS->value,
    'resolution_notes' => 'Contacting the user now.',
])
->assertJsonPath('data.resolution_notes', 'Contacting the user now.');
```

The test expects `resolution_notes` to round-trip: sent in request → stored in DB → returned in response.

### The Production Code (Before Wave 10)

| Layer | State | Evidence |
|-------|-------|----------|
| **DB Schema** | Column did not exist | `2026_05_20_160000_create_leads_table.php` — no `resolution_notes` column |
| **DB Schema** | Column did not exist | `2026_05_20_170000_alter_leads_add_resolution_tracking.php` — added `resolved_at`/`resolved_by` but NOT `resolution_notes` |
| **Model** | Not in `$fillable` or `$casts` | `Lead.php` pre-Wave 10 |
| **DTO** | No `resolutionNotes` property | `UpdateLeadStatusDTO.php` had only `id`, `status`, `actorUserId` |
| **Request** | No validation rule | `UpdateLeadStatusRequest.php` had only `status` |
| **Action** | Not passed to repository | `UpdateLeadStatusAction.php` did not include `resolution_notes` in the update array |
| **Resource** | Not in response | `AdminLeadResource.php` omitted `resolution_notes` |

### Conclusion

**`resolution_notes` was an unfinished feature.** The `resolved_at`/`resolved_by` columns were added by the Wave 7 resolution tracking migration, but `resolution_notes` was omitted — either intentionally (planned for later) or accidentally (missed during implementation). The test expectation was architecturally correct: admin users should be able to add notes when updating lead status. The production code was incomplete.

The test was **not** wrong. It correctly identified a gap in the implementation.

---

## End-to-End Trace (Post-Fix)

```
PATCH /v1/platform/leads/{lead}/status
  Body: { "status": "in_progress", "resolution_notes": "Contacting..." }

  → UpdateLeadStatusRequest::rules()
      ├── 'status' => required, LeadStatusEnum
      ├── 'resolution_notes' => nullable, string, max:5000       ← VALIDATED

  → UpdateLeadStatusDTO::fromRequest()
      ├── status = LeadStatusEnum::from('in_progress')
      ├── resolutionNotes = 'Contacting...'                       ← PARSED

  → UpdateLeadStatusAction::execute($dto)
      ├── LeadRepository::findByIdOrFail($dto->id)
      └── DB::transaction():
          └── LeadRepository::updateStatus($lead, [
              'resolution_notes' => $dto->resolutionNotes          ← PERSISTED
                ?? $lead->resolution_notes,
              // ...
          ])

  → AdminLeadResource::toArray()
      └── 'resolution_notes' => $this->resolution_notes           ← EXPOSED IN API

  ← Response: { "data": { ..., "resolution_notes": "Contacting..." } }
      ✅ Round-trip complete
```

### Missing Layer Check

| Layer | Status | Notes |
|-------|--------|-------|
| Migration | ✅ | `add_resolution_notes_to_leads_table` — nullable text column |
| Model fillable | ✅ | `'resolution_notes'` in `$fillable` |
| Model casts | ✅ | `'resolution_notes' => 'string'` |
| DTO | ✅ | `?string $resolutionNotes = null` |
| Request validation | ✅ | `nullable, string, max:5000` |
| Action | ✅ | `$dto->resolutionNotes ?? $lead->resolution_notes` |
| Repository | ✅ | `updateStatus()` passes attributes to `$lead->update()` |
| Resource | ✅ | `'resolution_notes' => $this->resolution_notes` |
| API Response | ✅ | Field present in JSON |
| Factory | N/A | Factory creates NEW leads — `resolution_notes` is set by admin update, not creation |

**All layers are complete.** No gaps remain.

---

## Should `resolution_notes` Exist in Production?

**Yes.** Lead management without notes is incomplete. The feature adds:
- Free-text admin notes during status transitions
- Backend validation (nullable, max 5000 chars)
- Partial update support (omitting the field preserves existing notes)
- Full API exposure via the AdminLeadResource

The implementation follows every existing pattern in the Lead system: DTO-first, Action-based, Request-validated, Resource-exposed. No shortcuts were taken.

---

## Risk Assessment

| Concern | Assessment |
|---------|------------|
| Existing leads | `resolution_notes` defaults to `null` — no impact on existing rows |
| API clients not sending the field | `??` operator preserves existing value — no behavior change |
| API clients sending empty string | `fromRequest()` converts empty string to `null` — safely ignored |
| API clients sending very long notes | `max:5000` validation prevents storage of oversized input |
| Unauthorized users setting notes | `UpdateLeadStatusRequest::authorize()` requires SUPER_ADMIN role |
| SQL injection | Eloquent binding + validation prevents injection |

### Verdict: ✅ `resolution_notes` is a correct, minimal, and architecturally consistent feature addition. It should remain in production.
