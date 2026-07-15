# Debug Session: dashboard-auth-session
- **Status**: [OPEN]
- **Issue**: Platform dashboard login succeeds initially, but the authenticated session does not persist and `/api/v1/users/auth/me` returns `401 Unauthenticated` when checked with the session cookie after login.
- **Debug Server**: Pending initialization
- **Log File**: `.dbg/trae-debug-log-dashboard-auth-session.ndjson`

## Reproduction Steps
1. Visit `http://localhost:3002/en/sign-in`
2. Log in with `super@test.com` / `password`
3. Confirm redirect to dashboard
4. Call `GET http://localhost:8000/api/v1/users/auth/me` using the resulting `ecommerce_session` cookie
5. Reload the dashboard page and observe whether the app returns to sign-in

## Hypotheses & Verification
| ID | Hypothesis | Likelihood | Effort | Evidence |
|----|------------|------------|--------|----------|
| A | The backend issues a session cookie, but the dashboard or browser context is not sending it back on authenticated API calls because of cookie domain/path/SameSite/stateful-domain mismatch. | High | Low | Pending |
| B | Login succeeds, but the authenticated session is later rejected by Laravel because the request host/origin does not match Sanctum's stateful SPA expectations. | High | Low | Pending |
| C | The login endpoint writes session data, but a later bootstrap or `/me` request is served by a different session configuration/driver and cannot read it back. | Medium | Medium | Pending |
| D | The dashboard redirects correctly after login using client state, but the backend user lookup is actually unauthenticated from the beginning. | Medium | Low | Pending |
| E | A middleware or auth-domain ownership check is invalidating or rejecting the session on `/api/v1/users/auth/me` for this actor/context. | Medium | Medium | Pending |

## Log Evidence
Pending

## Verification Conclusion
Pending
