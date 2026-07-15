# Debug Session: platform-auth-401

Status: [OPEN]

## Symptom

- `platform-dashboard` signs in successfully through merchant auth.
- Visiting `/en` still triggers `GET /api/v1/platform/dashboard` and `GET /api/v1/platform/analytics`, both `401`.
- The app then falls back to `/en/sign-in?redirect=%2Fen%2F&expired=1`.

## Scope

- Frontend app: `platform-dashboard`
- Runtime surface: Next.js route rendering, bootstrap store/provider, dashboard data queries

## Hypotheses

1. The dashboard route renders protected client components before auth redirect state stabilizes, so their React Query calls execute once.
2. `login()` resolves successfully because merchant bootstrap returns a user, but platform authorization is only rejected later by another request path.
3. A second bootstrap/refetch or unauthorized event clears session state after the first `/merchant/me` success, causing the redirect loop.
4. The dashboard layout mounts even when `bootstrapError` or `redirectTarget` should block child rendering, due to a client-side render ordering gap.
5. One or more dashboard data hooks are mounted outside the intended auth boundary and ignore the bootstrap store’s auth status.

## Plan

1. Add runtime instrumentation only.
2. Reproduce and inspect ordered logs.
3. Confirm or reject hypotheses.
4. Apply the smallest fix only after evidence is clear.
