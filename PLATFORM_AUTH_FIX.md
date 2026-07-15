# Platform Dashboard Authentication Fix - 401 Errors

## Problem Identified

After successful login at `localhost:3002`, API requests to `/api/v1/platform/dashboard` and other platform endpoints returned **401 Unauthorized**.

### Logs showed:
```
POST /api/proxy?endpoint=%2Fapi%2Fv1%2Fplatform%2Fauth%2Flogin 200 ✅
GET /api/proxy?endpoint=%2Fapi%2Fv1%2Fplatform%2Fauth%2Fme 200 ✅
GET /api/proxy?endpoint=%2Fapi%2Fv1%2Fplatform%2Fdashboard 401 ❌
GET /api/proxy?endpoint=%2Fapi%2Fv1%2Fplatform%2Fanalytics 401 ❌
```

## Root Cause

**Sanctum Stateful Domains Configuration Cache**

The platform dashboard runs on `localhost:3002`, but Laravel Sanctum's **configuration cache** was using an old stateful domains list that only included `localhost:3000` and `localhost:8000`.

Even though `.env` had the correct value:
```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,localhost:3002,localhost:8000,...
```

The cached config at `bootstrap/cache/config.php` was stale and didn't include `:3002`.

### Why This Caused 401 Errors

Sanctum only uses **session-based authentication** for requests from domains in the `stateful` array. When the frontend at `localhost:3002` wasn't in the list:

1. ✅ Login succeeded (auth endpoints bypass some checks)
2. ✅ `/auth/me` succeeded (might have fallback logic)
3. ❌ `/platform/dashboard` failed because:
   - Sanctum didn't recognize the request as "stateful"
   - Session cookies weren't being validated
   - The `auth:sanctum` middleware couldn't authenticate the user
   - `EnforcePlatformAuthority` middleware rejected with 401

## Solution Applied

### 1. Updated `config/sanctum.php` (belt-and-suspenders)

Added `localhost:3002` to the default fallback list:

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 
    'localhost,localhost:3000,localhost:3002,localhost:8000,127.0.0.1,127.0.0.1:8000,::1'
)),
```

### 2. Cleared Configuration Cache

```bash
php artisan config:clear
```

This forced Laravel to rebuild the config cache from the actual `.env` values.

### 3. Verified Fix

```bash
$ php artisan config:show sanctum | grep stateful
  stateful ⇁ 0 ..................................................... localhost
  stateful ⇁ 1 ................................................ localhost:3000
  stateful ⇁ 2 ................................................ localhost:3002  ✅
  stateful ⇁ 3 ................................................ localhost:8000
```

## Testing Instructions

1. **Clear browser cookies** for `localhost:3002` and `localhost:8000`
2. Navigate to `http://localhost:3002/en/sign-in`
3. Sign in with platform admin credentials
4. Dashboard should load successfully (no redirect to `/sign-in?expired=1`)
5. Refresh the page - should stay on dashboard (no 401 errors)

## Expected Behavior

- ✅ Login successful
- ✅ Dashboard loads with data
- ✅ Analytics endpoint returns data (not 401)
- ✅ Page refresh maintains authentication
- ✅ No `expired=1` redirects

## Architecture Context

### Platform Authority Flow (Wave 6)

```
Request → auth:sanctum → EnforcePlatformAuthority → PlatformAuthorityResolver
                ↓                                            ↓
          Validates session                       Checks ActorContext via IdentityContext
                ↓                                            ↓
          If not in stateful domains            If not SUPER_ADMIN/SUPPORT_AGENT
                ↓                                            ↓
             401 ❌                                        401 ❌
```

### Why Sanctum Stateful Domains Matter

Platform routes use this middleware stack:
```php
[
    'auth:sanctum',                                    // ← NEEDS stateful domain
    'identity.route:platform,platform,enforce',
    'platform.authority:platform_admin',
]
```

The `auth:sanctum` middleware has two authentication modes:
1. **Token-based** (Bearer tokens in Authorization header)
2. **Session-based** (cookies) - **ONLY for stateful domains**

Since the platform dashboard uses session cookies (not Bearer tokens), it **must** be in the stateful domains list.

## Files Modified

- `config/sanctum.php` - Added `localhost:3002` to default stateful domains fallback

## Related Documentation

- `SESSION_FIX_SUMMARY.md` - Previous session domain fix
- `docs/ARCHITECTURE.md` - Platform authority architecture
- `docs/EXECUTION_GOVERNANCE.md` - Authentication governance rules

## Prevention

To avoid this in the future:

1. **Always run `php artisan config:clear` after changing `.env`**
2. Check stateful domains when adding new frontend ports
3. Monitor logs for 401s immediately after successful login (indicates session issues)

---

**Status**: ✅ Fixed  
**Date**: 2026-07-15  
**Impact**: Backend configuration only  
**Test Time**: 2 minutes
