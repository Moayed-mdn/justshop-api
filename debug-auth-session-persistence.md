# Debug Session: auth-session-persistence
- **Status**: [OPEN]
- **Issue**: User remains redirected to `/en/sign-in` after reload even though `XSRF-TOKEN` and `ecommerce_session` cookies are still present.
- **Debug Server**: http://127.0.0.1:7777/event
- **Log File**: `.dbg/trae-debug-log-auth-session-persistence.ndjson`

## Reproduction Steps
1. Sign in successfully.
2. Confirm `XSRF-TOKEN` and `ecommerce_session` cookies exist.
3. Reload the page.
4. Observe redirect back to `/en/sign-in`.

## Hypotheses & Verification
| ID | Hypothesis | Likelihood | Effort | Evidence |
|----|------------|------------|--------|----------|
| A | The session cookie exists in the browser but is not being sent back on the protected reload request due to host/origin/cookie attribute mismatch. | High | Low | Pending |
| B | Laravel receives the session cookie, but the session payload cannot be decrypted or read consistently after reload because of driver/config/runtime mismatch. | High | Medium | Pending |
| C | Authentication succeeds on login, but the subsequent protected request resolves a different guard or middleware stack, so the session-backed user is ignored. | High | Medium | Pending |
| D | Frontend reload logic or SSR middleware calls an endpoint without credentials or against a different base URL, triggering a guest redirect despite valid browser cookies. | Medium | Medium | Pending |
| E | Tenant or identity middleware invalidates or bypasses the authenticated context on reload, even when the session is otherwise valid. | Medium | Medium | Pending |

## Log Evidence
- `pre-fix/C`: Merchant login created a session with `session_auth_domain=merchant`, `merchant_guard_check=true`, and cookie names `ecommerce_session`, `XSRF-TOKEN`.
- `pre-fix/A`: Direct backend `GET /api/v1/users/auth/me` receives `ecommerce_session` and `XSRF-TOKEN` and returns `200`.
- `pre-fix/A,B`: Next SSR request from `GET http://localhost:3002/en` forwards `ecommerce_session`, `XSRF-TOKEN`, and `NEXT_LOCALE` to Laravel and receives `200` in controlled reproduction.
- `pre-fix/A,B`: Guest SSR request forwards only `NEXT_LOCALE` and Laravel correctly returns `401`.
- `pre-fix/A,B` from the user's browser showed two later `/api/v1/users/auth/me` responses returning `401` even though the frontend prepared the request with `ecommerce_session` and `XSRF-TOKEN`, and those `401` responses had no matching backend middleware trace. This indicates the failure path was being reused before Laravel route execution.
- Fix applied in `platform-dashboard/lib/api/client.ts`: server-side fetches now use `cache: 'no-store'`.
- Post-fix controlled replay of `guest /en -> login -> reload /en -> reload /en` shows both authenticated reloads returning dashboard HTML, with matching frontend request logs, backend middleware entry logs, and `200` responses from `/api/v1/users/auth/me`.

## Verification Conclusion
- Hypothesis A is currently **rejected** for the controlled reproduction: the reload request does send the session cookie.
- Hypothesis B is currently **rejected** for the controlled reproduction: Laravel resolves the authenticated user on `/api/v1/users/auth/me`.
- Hypothesis C is currently **not confirmed** in controlled reproduction: `/api/v1/users/auth/me` succeeds after SSR forwarding.
- Hypothesis D is now **confirmed**: the failing path was the Next SSR session lookup reusing an unauthenticated response before Laravel route execution.
- Hypothesis E remains **possible** but not yet evidenced.
