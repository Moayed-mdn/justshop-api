# Wave 2.5 Targeted Authorization Normalization

Status: complete for the approved Wave 2.5 safe-domain slice.
Wave 3 remains blocked.

## Scope Executed

Executed only the approved low-risk authorization normalization slice:

- Brand
- Tag
- Category
- CMS Blog
- Dashboard read paths

Not touched for authority change:

- Orders
- Checkout
- Auth/session topology
- Membership evolution
- Guard split
- Customer identity separation
- Async adoption

## Machine-Readable Artifacts

Generated artifacts:

- `storage/app/wave2/authorization-drift-report.json`
- `storage/app/wave2/authorization-triage-report.json`
- `storage/app/wave2/policy-ownership-report.json`
- `storage/app/wave2/operational-readiness-report.json`

Commands:

- `php artisan architecture:detect-authorization-drift --format=json --output=storage/app/wave2/authorization-drift-report.json`
- `php artisan architecture:wave2-authorization-triage --output=storage/app/wave2/authorization-triage-report.json`
- `php artisan architecture:report-policy-ownership --output=storage/app/wave2/policy-ownership-report.json`
- `php artisan architecture:wave2-readiness-report --output=storage/app/wave2/operational-readiness-report.json`

## Drift Reduction Summary

Pre-normalization baseline observed during this cycle:

- total findings: `89`
- hidden authorization: `2`
- permission middleware drift: `19`
- generic `currentStore` drift: `37`

Post-normalization:

- total findings: `66`
- hidden authorization: `0`
- permission middleware drift: `18`
- generic `currentStore` drift: `17`

Net reduction:

- total findings reduced by `23`
- hidden authorization reduced by `2`
- generic `currentStore` leakage reduced by `20`
- permission middleware drift reduced by `1`

## Normalized Domains

## Brand

- controller ownership moved to `BrandPolicy`
- route permission middleware retained
- generic `currentStore` controller authorization removed
- dual authorization path retained intentionally for parity visibility

## Tag

- hidden request permission checks removed from `ListTagsRequest` and `UpdateTagRequest`
- explicit route permission middleware added for index/store/show/update/destroy
- controller ownership moved to `TagPolicy`
- repository store/global tag visibility rules preserved

## Category

- controller ownership moved to `CategoryPolicy`
- route permission middleware retained
- generic `currentStore` controller authorization removed

## CMS Blog

- controller ownership moved to explicit `BlogPostPolicy` calls
- request-level authorization fallback removed
- existing `role:super_admin` route middleware retained as compatibility bridge
- no permission middleware added in Wave 2.5

## Dashboard

- controller read paths moved to explicit `DashboardPolicy`
- route permission middleware retained
- generic `currentStore` controller authorization removed

## Ownership / Parity Evidence

From `storage/app/wave2/policy-ownership-report.json`:

- explicit policy routes increased from `10` to `38`
- hidden fallback routes reduced from `67` to `21`
- generic `currentStore` ownership routes reduced from `39` to `19`
- normalized domain ownership health score: `100`

Normalized domain status:

- `brand`: normalized, health `100`
- `tag`: normalized, health `100`
- `category`: normalized, health `100`
- `cms_blog`: normalized, health `100`
- `dashboard`: normalized, health `100`

Middleware vs policy parity telemetry is now emitted on normalized domains through `authorization.policy.decision` with:

- `policy_capability`
- `middleware_capability`
- `middleware_permission_allowed`
- `middleware_policy_parity`
- `dual_authorization_path`

## Hidden Authorization Cleanup

Resolved known hidden authorization findings:

- `app/Http/Requests/Admin/Tag/ListTagsRequest.php`
- `app/Http/Requests/Admin/Tag/UpdateTagRequest.php`

Result:

- active hidden authorization findings: `0`

## Triage Classification

From `storage/app/wave2/authorization-triage-report.json`:

Remaining generic `currentStore` findings: `17`

Classification:

- `requires_wave_3_context`: `5` (`order`)
- `requires_rbac_normalization_later`: `6` (`product`)
- `requires_membership_evolution_later`: `6` (`membership_admin`)

Migration priority ordering remains:

1. `brand` — safe-to-normalize now
2. `tag` — safe-to-normalize now
3. `category` — safe-to-normalize now
4. `cms_blog` — compatibility bridge / visibility normalization
5. `dashboard` — safe-to-normalize now
6. `product` — RBAC normalization later
7. `membership_admin` — membership evolution later
8. `order` — Wave 3 context required

## Tenant Isolation Verification

Validation added for normalized domains covers:

- cross-store denial
- unauthorized actor denial
- super-admin bypass correctness
- middleware vs policy parity logging for normalized brand routes

Readiness artifact status:

- store-scoped routes: `63`
- store-scoped routes with `store.context`: `63`
- tenant isolation status: `healthy`

## Remaining Blockers Before Wave 3

Wave 3 remains blocked because:

- generic `currentStore` ownership drift remains in `order`, `product`, and `membership_admin`
- permission middleware drift remains in non-normalized domains (`cms blog`, `marketing pages`, `leads`)
- hidden fallback authorization paths remain in non-normalized domains
- production-like parity telemetry review is still required

## Explicit Non-Goals Still Unchanged

Still not started:

- customer auth separation
- customer bootstrap rewrite
- guard split
- session split
- identity separation
- memberships table
- RBAC snapshots as authority
- checkout auth rewrite
