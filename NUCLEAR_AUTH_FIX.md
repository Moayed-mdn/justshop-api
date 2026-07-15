# Nuclear Authentication Fix - Platform Dashboard 401 Errors

## Problems Identified

### 1. **XSRF Token Encryption/Mismatch**
Laravel was encrypting the `XSRF-TOKEN` cookie, and the Next.js proxy was double-decoding it, causing "CSRF token mismatch" errors.

### 2. **Stale Configuration Cache**  
Even though `.env` had `localhost:3002` in `SANCTUM_STATEFUL_DOMAINS`, the cached config didn't include it.

### 3. **Session Not Persisting Between Requests**
After successful login, subsequent API calls returned 401 because the authentication wasn't being maintained.

## Nuclear Solutions Applied

### Fix 1: Disable XSRF-TOKEN Cookie Encryption

**File**: `bootstrap/app.php`

Added exception for `XSRF-TOKEN` from cookie encryption:

```php
$middleware->encryptCookies(except: [
    'XSRF-TOKEN',
]);
```

**Why**: Sanctum SPAs need the raw XSRF token value, not an encrypted one. The token is already cryptographically secure and URL-safe.

### Fix 2: Remove decodeURIComponent from Proxy

**File**: `platform-dashboard/app/api/proxy/route.ts`

Changed:
```typescript
// Before
{ 'X-XSRF-TOKEN': decodeURIComponent(xsrfToken) }

// After  
{ 'X-XSRF-TOKEN': xsrfToken }
```

**Why**: Next.js cookies API already handles URL decoding. Double-decoding corrupts the token.

### Fix 3: Updated Sanctum Stateful Domains

**File**: `config/sanctum.php`

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 
    'localhost,localhost:3000,localhost:3002,localhost:8000,127.0.0.1,127.0.0.1:8000,::1'
)),
```

**Why**: Added `localhost:3002` to the default fallback to ensure it's always included even if `.env` is missing it.

### Fix 4: Added Debug Endpoints

**File**: `routes/api/v1/platform/debug.php` (NEW)

Added two debug endpoints:
- `/api/v1/platform/debug/session` - No auth required, shows session state
- `/api/v1/platform/debug/auth` - Auth required, shows authenticated user state

**Usage**:
```bash
# Check session (before login)
curl http://localhost:8000/api/v1/platform/debug/session

# Check auth (after login, include cookies)
curl http://localhost:8000/api/v1/platform/debug/auth \
  -H "Cookie: ecommerce_session=xxx; XSRF-TOKEN=yyy"
```

## Complete Testing Procedure

### Step 1: Clear Everything (Nuclear Approach)

```bash
# Backend
cd /home/leader/projects/laravel/v3/tenant/laratenant-backend
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Restart Laravel server (if running)
# Kill any running processes on port 8000
lsof -ti:8000 | xargs kill -9 2>/dev/null || true
php artisan serve &

# Frontend  
cd platform-dashboard
rm -rf .next
npm run dev
```

### Step 2: Clear Browser State

1. Open DevTools → Application → Storage
2. Clear ALL for `localhost:3002` and `localhost:8000`:
   - Cookies
   - Local Storage
   - Session Storage
   - IndexedDB

### Step 3: Test Login Flow

1. Navigate to `http://localhost:3002/en/sign-in`
2. Open DevTools → Network tab
3. Sign in with platform admin credentials

**Watch for these requests**:

```
✅ GET  /api/proxy?endpoint=%2Fapi%2Fsanctum%2Fcsrf-cookie → 204
✅ POST /api/proxy?endpoint=%2Fapi%2Fv1%2Fplatform%2Fauth%2Flogin → 200
✅ GET  /api/proxy?endpoint=%2Fapi%2Fv1%2Fplatform%2Fauth%2Fme → 200
✅ GET  /api/proxy?endpoint=%2Fapi%2Fv1%2Fplatform%2Fdashboard → 200 (NOT 401!)
✅ GET  /api/proxy?endpoint=%2Fapi%2Fv1%2Fplatform%2Fanalytics → 200 (NOT 401!)
```

### Step 4: Verify Cookies

Check DevTools → Application → Cookies → `http://localhost:3002`

Should see:
- `ecommerce_session` = [encrypted session ID]
- `XSRF-TOKEN` = [plain text token, NOT encrypted]
- `NEXT_LOCALE` = en

### Step 5: Test Session Persistence

1. Refresh the page (F5)
2. Should stay on dashboard (NOT redirect to `/sign-in?expired=1`)
3. Navigate between pages
4. Close tab and reopen (within session lifetime)

## Debug Commands

### Check Laravel Config

```bash
php artisan config:show sanctum | grep stateful
php artisan config:show session | grep -E "domain|cookie|driver"
```

### Check Session in Database

```bash
php artisan tinker --execute="
  \$session = \Illuminate\Support\Facades\DB::table('sessions')
    ->latest('last_activity')
    ->first();
  print_r(\$session);
"
```

### Test Backend Auth Directly

```bash
# 1. Get CSRF cookie
curl -c cookies.txt http://localhost:8000/api/sanctum/csrf-cookie

# 2. Login
curl -b cookies.txt -c cookies.txt \
  -X POST http://localhost:8000/api/v1/platform/auth/login \
  -H "Content-Type: application/json" \
  -H "X-Requested-With: XMLHttpRequest" \
  -d '{"email":"admin@example.com","password":"password"}'

# 3. Test authenticated endpoint
curl -b cookies.txt http://localhost:8000/api/v1/platform/auth/me
curl -b cookies.txt http://localhost:8000/api/v1/platform/dashboard
```

### Use Debug Endpoints

```bash
# From browser console (after login):
fetch('/api/proxy?endpoint=%2Fapi%2Fv1%2Fplatform%2Fdebug%2Fauth')
  .then(r => r.json())
  .then(console.log);
```

## Architecture Flow

```
Browser (localhost:3002)
    ↓
Next.js Proxy (/api/proxy)
    ├─ Reads cookies from browser
    ├─ Forwards Cookie header
    ├─ Forwards X-XSRF-TOKEN header (unencrypted!)
    ↓
Laravel API (localhost:8000)
    ├─ web middleware (starts session)
    ├─ auth:sanctum middleware
    │   ├─ Checks if request is from stateful domain (localhost:3002) ✅
    │   ├─ Validates session cookie
    │   └─ Authenticates user from session
    ├─ identity.route:platform,platform,enforce
    │   └─ Validates session has auth_domain=platform
    ├─ platform.authority:platform_admin
    │   └─ Checks user has SUPER_ADMIN actor context
    ↓
PlatformDashboardController
```

## Common Issues & Fixes

### Issue: "CSRF token mismatch"
**Cause**: XSRF-TOKEN cookie is encrypted  
**Fix**: ✅ Applied - `XSRF-TOKEN` excluded from encryption

### Issue: 401 on dashboard after successful login
**Cause**: `localhost:3002` not in sanctum stateful domains  
**Fix**: ✅ Applied - Added to config default + cleared cache

### Issue: Session not persisting
**Cause**: Session cookies not being sent/stored properly  
**Fix**: Check Domain=localhost, SameSite=Lax, Secure=false for HTTP

### Issue: Login works but /me returns 401
**Cause**: Different guards or session not tagged properly  
**Fix**: Verify `SessionOwnershipManager` tags session with 'platform'

## Files Modified

1. ✅ `bootstrap/app.php` - Excluded XSRF-TOKEN from encryption
2. ✅ `config/sanctum.php` - Added localhost:3002 to stateful domains default
3. ✅ `platform-dashboard/app/api/proxy/route.ts` - Removed double URL decode
4. ✅ `routes/api/v1/platform/debug.php` - Added debug endpoints (NEW)
5. ✅ `routes/api.php` - Included debug routes

## Prevention Checklist

- [ ] Always run `php artisan config:clear` after `.env` changes
- [ ] Add any new frontend ports to `SANCTUM_STATEFUL_DOMAINS`
- [ ] Never encrypt `XSRF-TOKEN` cookie for SPA auth
- [ ] Use debug endpoints to diagnose auth issues quickly
- [ ] Monitor for 401s immediately after 200 login responses

## Next Steps

1. **Test thoroughly** with the procedure above
2. **Remove debug routes** before production deployment
3. **Document** the final working configuration
4. **Update** `.env.example` with `SANCTUM_STATEFUL_DOMAINS` documentation

---

**Status**: 🚀 Ready to test  
**Date**: 2026-07-15  
**Impact**: Backend + Frontend  
**Test Time**: 5 minutes  
**Risk**: Low (reversible changes)
