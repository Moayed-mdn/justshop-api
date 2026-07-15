# Platform Authentication - Postman Test Guide

## Test Credentials
```
Email: super@test.com
Password: password
```

## Frontend Testing (UPDATED - July 15, 2026)

### What Was Fixed

The CSRF token mismatch (HTTP 419) error has been resolved. The issue was that Next.js server actions don't automatically handle cookies like browser fetch does. 

**Changes Made:**
1. Manual cookie extraction from `/sanctum/csrf-cookie` response
2. All cookies (XSRF-TOKEN + ecommerce_session) are now passed in Cookie header
3. XSRF-TOKEN is properly decoded before being sent in X-XSRF-TOKEN header
4. Enhanced logging to debug each step

### Testing Steps

1. **Start the development server** (if not already running):
   ```bash
   cd /home/leader/projects/laravel/v3/tenant/laratenant-backend/platform-dashboard
   npm run dev
   ```

2. **Open browser** to: `http://localhost:3001/en/sign-in`

3. **Open browser console** (F12) to see detailed logs

4. **Enter credentials**:
   - Email: `super@test.com`
   - Password: `password`

5. **Check console output** - you should see:
   ```
   [signInAction] Step 1: Getting CSRF cookie from: http://localhost:8000/sanctum/csrf-cookie
   [signInAction] Step 2: Received 2 Set-Cookie headers
   [signInAction] Step 3: XSRF Token extracted (first 20 chars): eyJpdiI6...
   [signInAction] Step 4: Cookie header prepared with 2 cookies
   [signInAction] Step 5: Attempting login to: http://localhost:8000/api/v1/platform/auth/login
   [signInAction] Step 6: Using XSRF token and 2 cookies
   [signInAction] Step 7: Login response: { status: 200, success: true, hasData: true }
   [signInAction] Step 8: Setting 2 cookies from login response
   [signInAction] Set cookie: XSRF-TOKEN
   [signInAction] Set cookie: ecommerce_session
   [signInAction] Step 9: Authentication successful, redirecting to dashboard
   ```

6. **Expected result**: You should be redirected to the dashboard at `http://localhost:3001/en`

### If It Still Fails

Check the console output:
- **Step 2**: Should show 2 Set-Cookie headers (XSRF-TOKEN + ecommerce_session)
- **Step 3**: Should show XSRF token was extracted
- **Step 7**: Should show status 200 and success: true

If you see HTTP 419 at Step 7, check:
- Backend is running on `http://localhost:8000`
- Environment variable `NEXT_PUBLIC_API_URL` is set correctly
- Laravel session config allows localhost

---

## Step-by-Step Testing

### Step 1: Get CSRF Cookie
```
GET http://localhost:8000/sanctum/csrf-cookie
```

**Settings:**
- Enable "Save Cookies" in Postman settings
- No body required
- No special headers needed

**Expected Response:**
- Status: `204 No Content`
- Cookies: `XSRF-TOKEN` and `ecommerce_session` should be saved

---

### Step 2: Login
```
POST http://localhost:8000/api/v1/platform/auth/login
```

**Headers:**
```
Content-Type: application/json
Accept: application/json
X-XSRF-TOKEN: {{XSRF-TOKEN}}
```

> **Note**: Get the `XSRF-TOKEN` from cookies (it's URL encoded), decode it, and paste here.
> Or use Postman variable: `{{XSRF-TOKEN}}`

**Body (raw JSON):**
```json
{
  "email": "super@test.com",
  "password": "password"
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "user": {
      "id": 2,
      "name": "Super Admin",
      "email": "super@test.com",
      "email_verified": true,
      "avatar": null
    },
    "email_verified": true,
    "stores": [],
    "active_store": null,
    "onboarding": {
      "step": "completed",
      "is_completed": true
    },
    "actor_context": "super_admin",
    "auth_domain": "platform",
    "session": {
      "auth_domain": "platform",
      "actor_type": "super_admin"
    }
  }
}
```

---

### Step 3: Get Bootstrap (Me)
```
GET http://localhost:8000/api/v1/platform/auth/me
```

**Headers:**
```
Accept: application/json
```

**Expected Response:**
- Status: `200 OK`
- Same bootstrap structure as login response

**If you get 401:**
- Cookies aren't being sent
- Check Postman settings: "Automatically add cookies" must be enabled
- Check that cookies from Step 1 are still valid

---

### Step 4: Logout
```
POST http://localhost:8000/api/v1/platform/auth/logout
```

**Headers:**
```
Accept: application/json
X-XSRF-TOKEN: {{XSRF-TOKEN}}
```

**Expected Response:**
- Status: `200 OK`
- Cookies should be cleared

---

## Debugging Tips

### Check Cookies in Postman
1. Click "Cookies" link (below Send button)
2. Look for `localhost:8000`
3. Should see: `XSRF-TOKEN` and `ecommerce_session`

### Get XSRF Token Value
1. Go to Cookies
2. Find `XSRF-TOKEN`
3. Copy the value (it's URL encoded like `eyJpdiI6...`)
4. Decode it: `decodeURIComponent("paste-here")` in browser console
5. Use decoded value in `X-XSRF-TOKEN` header

### Common Issues

**401 on /auth/me:**
- ✅ Cookies are not being sent
- ✅ Session expired
- ✅ CSRF token mismatch

**419 on login:**
- ✅ Didn't call `/sanctum/csrf-cookie` first
- ✅ Wrong XSRF token (encoded instead of decoded)
- ✅ CSRF token expired (get new one)

**403 Forbidden:**
- ✅ User doesn't have `SUPER_ADMIN` role
- ✅ Wrong auth domain (logged in as merchant)

---

## ✅ Frontend Fix - RESOLVED (July 15, 2026)

The frontend authentication is now working correctly on `http://localhost:3001`.

### What Was the Issue?
Next.js Server Actions don't automatically handle cookies like browser fetch. The CSRF cookie and session cookie weren't being forwarded between requests.

### How It Was Fixed
Implemented manual cookie extraction and forwarding in `lib/actions/auth-actions.ts`:
1. Extract cookies from `/sanctum/csrf-cookie` response
2. Parse and decode XSRF-TOKEN
3. Forward all cookies to login request in Cookie header

See `platform-dashboard/CSRF_FIX.md` for detailed explanation.

### Testing the Frontend

1. **Start servers** (if not running):
   ```bash
   # Backend
   cd /home/leader/projects/laravel/v3/tenant/laratenant-backend
   php artisan serve
   
   # Frontend
   cd platform-dashboard
   npm run dev
   ```

2. **Open browser**: `http://localhost:3001/en/sign-in`

3. **Sign in** with: `super@test.com` / `password`

4. **Check console** (F12) for authentication flow logs

5. **Expected**: Redirect to dashboard ✅

### Test Script
```bash
cd platform-dashboard
./test-auth.sh  # Tests backend auth flow
```

---

## Current Status (Updated: July 15, 2026)

✅ Backend authentication works  
✅ Login returns correct data  
✅ First `/auth/me` call works  
✅ Frontend authentication works  
✅ CSRF token issue resolved  

**Status**: All authentication flows working correctly! 🎉

**Fix Applied**: Manual cookie handling in Next.js Server Actions to properly forward CSRF tokens and session cookies.

---

## Test in Browser Console

```javascript
// 1. Get CSRF
fetch('http://localhost:8000/sanctum/csrf-cookie', {
  credentials: 'include'
})

// 2. Login (after step 1)
fetch('http://localhost:8000/api/v1/platform/auth/login', {
  method: 'POST',
  credentials: 'include',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    email: 'super@test.com',
    password: 'password'
  })
}).then(r => r.json()).then(console.log)

// 3. Get Me (after step 2)
fetch('http://localhost:8000/api/v1/platform/auth/me', {
  credentials: 'include',
  headers: { 'Accept': 'application/json' }
}).then(r => r.json()).then(console.log)
```

**Note**: Run these from `http://localhost:8000` (same domain) to avoid CORS issues.

---

**Created**: July 15, 2026  
**Backend**: `http://localhost:8000`  
**Frontend**: `http://localhost:3000` (should be 3002)
