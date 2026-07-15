# Debug Session: auth-session-reload

Status: OPEN

## Symptom
- User signs in successfully.
- After page reload, app redirects back to `/en/sign-in`.
- Browser still shows `XSRF-TOKEN` and `ecommerce_session` cookies.

## Initial Hypotheses
- H1: The session cookie exists, but Laravel cannot decrypt or read it on the next request.
- H2: The cookie is valid, but the authenticated guard/session key is not being written during login.
- H3: The browser sends the cookie, but the reload request is handled by a different host/origin/port path than the login flow.
- H4: Middleware or frontend proxy logic treats the user as unauthenticated even when the backend session is present.
- H5: Session storage is being regenerated or cleared between login and reload.

## Evidence Log
- `config/session.php` resolves to `driver=database`, `domain=null`, `cookie=ecommerce_session`.
- `config/sanctum.php` resolves `localhost:3002` as a stateful domain.
- Laravel runtime log shows successful login requests on `api/v1/users/auth/login`.
- Laravel runtime log shows authenticated follow-up requests on `api/v1/users/auth/me`.
- Database `sessions` table contains active rows with a non-null `user_id`, confirming persistence.
- Dedicated debug-server capture was interrupted by terminal reuse; native Laravel telemetry was used instead.

## Hypothesis Status
- H1: REJECTED. Backend receives authenticated merchant/platform requests after login.
- H2: REJECTED. Session rows with `user_id` are persisted in the `sessions` table.
- H3: MOSTLY REJECTED. The backend does receive follow-up auth requests through the current flow.
- H4: MOST LIKELY. The failure appears to be in frontend/proxy/session interpretation, not cookie persistence.
- H5: REJECTED. No evidence that the session is immediately cleared after login.

## Key Evidence References
- `storage/logs/laravel.log` lines around 44503-44507: login request reaches backend and completes auth telemetry.
- `storage/logs/laravel.log` lines around 44530-44535: `/api/v1/users/auth/me` reaches backend with an authenticated user.
- Latest `sessions` rows include entries with `user_id=2`, showing database-backed session persistence.

## Next Step
- Shift investigation to the frontend/proxy path on ports `3001`/`3002`, especially route guards and how `/api/v1/users/auth/me` or `/api/v1/merchant/me` responses are interpreted after reload.
