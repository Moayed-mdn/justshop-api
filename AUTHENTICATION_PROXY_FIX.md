# Authentication Proxy Fix

## Problem

When navigating to stores or users pages in the Platform Dashboard, the API calls to `/api/v1/platform/stores` and `/api/v1/platform/users` were returning `401 Unauthenticated` even though the user was logged in and `/api/auth/me` was working correctly.

## Root Cause

The API client was making **cross-origin requests** directly from the browser to `http://localhost:8000` (Laravel backend). Browser security (CORS) prevents sending cookies (including session cookies) in cross-origin requests, even if CORS headers are configured.

The `/api/auth/me` endpoint worked because it had a custom Next.js API route that acted as a server-side proxy, forwarding cookies to Laravel.

## Solution

Implemented a **universal API proxy** in Next.js that forwards ALL `/api/v1/*` requests to the Laravel backend with cookies. This makes all API calls same-origin from the browser's perspective.

### Changes Made

1. **API Proxy Route** (`app/api/v1/[...path]/route.ts`)
   - Catch-all route that proxies all `/api/v1/*` requests to Laravel
   - Forwards cookies from browser to Laravel backend
   - Forwards CSRF tokens in `X-XSRF-TOKEN` header
   - Supports GET, POST, PUT, PATCH, DELETE methods
   - Fixed params handling for Next.js 15 (params must be awaited)
   - Added console logging for debugging

2. **CSRF Cookie Proxy** (`app/api/sanctum/csrf-cookie/route.ts`)
   - Proxies `/api/sanctum/csrf-cookie` to Laravel's `/sanctum/csrf-cookie`
   - Forwards Set-Cookie headers from Laravel to browser
   - Added console logging for debugging

3. **API Client Updates** (`lib/api/client.ts`)
   - Updated CSRF token endpoint to `/api/sanctum/csrf-cookie` (through proxy)
   - baseURL now uses empty string for relative URLs (same-origin)

4. **Environment Configuration** (`.env.local`)
   - `NEXT_PUBLIC_API_URL=` (empty) - Forces relative URLs through Next.js proxy
   - `BACKEND_URL=http://localhost:8000` - Backend URL for server-side proxy

## How It Works

### Before (Direct Backend Calls)
```
Browser → http://localhost:8000/api/v1/platform/stores
❌ Cross-origin, no cookies sent
❌ 401 Unauthenticated
```

### After (Through Next.js Proxy)
```
Browser → http://localhost:3001/api/v1/platform/stores
         ↓
Next.js API Route → http://localhost:8000/api/v1/platform/stores (with cookies)
         ↓
Laravel Backend ✅ (authenticated)
         ↓
Next.js API Route ← Response
         ↓
Browser ← Response ✅
```

## Testing

1. **Clear all cookies** using `platform-dashboard/public/clear-cookies.html`
2. **Restart Next.js dev server** (Ctrl+C then `npm run dev`)
3. **Login** with `super@test.com` / `password`
4. **Navigate to stores/users pages** - should work without 401 errors
5. **Check console logs** for proxy debug messages:
   ```
   [API Proxy GET] http://localhost:8000/api/v1/platform/stores?page=1&per_page=10
   [API Proxy] Forwarding cookies, length: 715
   [API Proxy] Backend response: 200
   ```

## Key Learnings

- **Same-origin is required** for cookie-based authentication in browsers
- **Server-side proxies** are the solution for cross-origin cookie forwarding
- **Next.js 15** requires `await params` in dynamic route handlers
- **Session cookies** must be forwarded by server-side code, not browser fetch

## Files Modified

- `platform-dashboard/app/api/v1/[...path]/route.ts` (NEW)
- `platform-dashboard/app/api/sanctum/csrf-cookie/route.ts` (NEW)
- `platform-dashboard/lib/api/client.ts`
- `platform-dashboard/.env.local`

## Related Documentation

- HTTP 431 Fix: `HTTP_431_FIX.md`
- Cookie Cleaner: `platform-dashboard/public/clear-cookies.html`
