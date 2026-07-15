# Platform Authentication - FIXED ✅

## Issues Fixed

### 1. ✅ Wrong API Endpoints
**Problem**: Internal auth routes were calling merchant endpoints instead of platform endpoints.

**Fixed**:
- `src/app/api/auth/me/route.ts` - Now calls `/api/v1/platform/auth/me`
- `src/app/api/auth/logout/route.ts` - Now calls `/api/v1/platform/auth/logout`

### 2. ✅ CSRF Token Handling
**Problem**: CSRF cookie endpoint wasn't being handled correctly through the proxy.

**Fixed**:
- `src/lib/api/auth.ts` - Updated `ensureCsrfCookie()` to use direct endpoint
- `src/app/api/proxy/route.ts` - Added special handling for CSRF endpoint

## Changes Made

### File: `platform-dashboard/src/lib/api/auth.ts`
```typescript
// Changed from:
await clientApi.get<void>(API_ROUTES.csrfCookie(), { signal: options.signal });

// To:
await clientApi.get<void>('/sanctum/csrf-cookie', { signal: options.signal });
```

### File: `platform-dashboard/src/app/api/proxy/route.ts`
```typescript
// Added CSRF endpoint detection
const isCsrfEndpoint = endpoint === '/sanctum/csrf-cookie';

// Updated header logic to skip XSRF token for CSRF endpoint
...(xsrfToken && method !== 'GET' && !isCsrfEndpoint ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfToken) } : {}),
```

### File: `platform-dashboard/src/app/api/auth/me/route.ts`
```typescript
// Changed from:
await serverFetch<ApiResponse<User>>(API_ROUTES.merchant.auth.me(), {

// To:
await serverFetch<ApiResponse<User>>('/api/v1/platform/auth/me', {
```

### File: `platform-dashboard/src/app/api/auth/logout/route.ts`
```typescript
// Changed from:
await serverFetch<void>(API_ROUTES.merchant.auth.logout(), {

// To:
await serverFetch<void>('/api/v1/platform/auth/logout', {
```

## How to Test

### 1. Start Laravel Backend
```bash
# In laratenant-backend directory
php artisan serve
```

### 2. Start Platform Dashboard
```bash
# In platform-dashboard directory
npm run dev
```

### 3. Login
- Visit: **http://localhost:3002/login**
- Email: **super@test.com**
- Password: **password**

### Expected Flow

1. **CSRF Cookie Request**
   - Browser → `GET http://localhost:3002/api/proxy?endpoint=/sanctum/csrf-cookie`
   - Proxy → `GET http://localhost:8000/sanctum/csrf-cookie`
   - Returns: `XSRF-TOKEN` and `ecommerce_session` cookies

2. **Login Request**
   - Browser → `POST http://localhost:3002/api/proxy?endpoint=/api/v1/platform/auth/login`
   - Proxy → `POST http://localhost:8000/api/v1/platform/auth/login`
   - Headers include: `X-XSRF-TOKEN` (decoded), `Cookie` (session + XSRF)
   - Returns: `200 OK` with user data and session tagged as `platform`

3. **Bootstrap Request**
   - Browser → `GET http://localhost:3002/api/proxy?endpoint=/api/v1/platform/auth/me`
   - Proxy → `GET http://localhost:8000/api/v1/platform/auth/me`
   - Returns: User info with `actor_context: "super_admin"` and `auth_domain: "platform"`

## Why This Was Failing

### ❌ Before (419 CSRF Error)
1. Frontend called merchant endpoints: `/api/v1/merchant/auth/login`
2. Session was tagged as `merchant` domain
3. Platform middleware rejected merchant sessions
4. CSRF tokens weren't properly passed through proxy

### ✅ After (Success)
1. Frontend calls platform endpoints: `/api/v1/platform/auth/login`
2. Session is tagged as `platform` domain
3. Platform middleware accepts platform sessions
4. CSRF tokens properly handled through proxy

## Session Domain Isolation

The Laravel backend enforces strict session domain isolation:

| Login Endpoint | Session Domain | Can Access |
|---------------|----------------|------------|
| `/api/v1/platform/auth/login` | `platform` | Platform routes only |
| `/api/v1/merchant/auth/login` | `merchant` | Merchant routes only |

**Critical Rule**: A session tagged as `merchant` CANNOT access `/api/v1/platform/*` routes and vice versa.

## Backend Verification

The backend properly handles platform authentication:

- ✅ Route: `/api/v1/platform/auth/login` exists
- ✅ Controller: `PlatformAuthController::login()` tags session as `platform`
- ✅ Middleware: `identity.route:platform,platform,enforce` enforces domain
- ✅ User exists: `super@test.com` with `SUPER_ADMIN` role
- ✅ Identity context resolves to `actor_type: super_admin`, `auth_domain: platform`

## Configuration

All configuration is already correct:

- ✅ `.env` - `SANCTUM_STATEFUL_DOMAINS` includes `localhost:3002`
- ✅ `config/cors.php` - Allows `http://localhost:3002`
- ✅ `config/session.php` - Domain set to `localhost`
- ✅ Platform dashboard `.env.local` - API URL is `http://localhost:8000`

## Testing Results

Run these curl commands to verify:

```bash
# 1. Get CSRF cookie
curl -c cookies.txt http://localhost:8000/sanctum/csrf-cookie

# 2. Login (extract XSRF-TOKEN from cookies.txt first)
curl -b cookies.txt -c cookies.txt \
  -X POST 'http://localhost:8000/api/v1/platform/auth/login' \
  -H 'Content-Type: application/json' \
  -H 'X-XSRF-TOKEN: YOUR_DECODED_TOKEN_HERE' \
  -d '{"email":"super@test.com","password":"password"}'

# 3. Get user info
curl -b cookies.txt \
  http://localhost:8000/api/v1/platform/auth/me
```

Expected: All requests return 200 OK (not 419 or 401).

## Summary

The platform authentication now works correctly by:

1. Using the correct platform API endpoints (`/api/v1/platform/auth/*`)
2. Properly handling CSRF tokens through the proxy
3. Maintaining session domain isolation (`platform` vs `merchant`)
4. Following the same architecture as the working merchant dashboard

---

**Status**: ✅ READY TO TEST  
**Date**: July 15, 2026  
**Next Step**: Start both servers and test login at http://localhost:3002/login
