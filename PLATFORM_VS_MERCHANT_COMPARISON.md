# Platform Admin vs Merchant Dashboard - Complete Comparison

## Executive Summary

Two separate dashboards with different authentication endpoints and user contexts:

| Dashboard | Port | Base API | Session Tag | Actor Type |
|-----------|------|----------|-------------|------------|
| **Merchant** | 3001 | `/api/v1/merchant/` | `merchant` | MERCHANT_OWNER, MERCHANT_ADMIN, MERCHANT_STAFF |
| **Platform** | **3002** | **`/api/v1/platform/`** | **`platform`** | **SUPER_ADMIN** |

## Authentication Routes

### Merchant Dashboard (Original)

```typescript
// Base: /api/v1/merchant/auth/

POST   /api/v1/merchant/auth/login              // Login
POST   /api/v1/merchant/auth/register           // Register new merchant
GET    /api/v1/merchant/me                      // Bootstrap
POST   /api/v1/merchant/auth/logout             // Logout
POST   /api/v1/merchant/auth/password/forgot    // Forgot password
POST   /api/v1/merchant/auth/password/reset     // Reset password
GET    /api/v1/merchant/auth/email/status       // Email verification status
POST   /api/v1/merchant/auth/email/resend       // Resend verification
GET    /api/v1/merchant/auth/email/verify/:id/:hash  // Verify email
PATCH  /api/v1/merchant/auth/active-store       // Switch store
GET    /api/v1/merchant/auth/google             // Google OAuth login
GET    /api/v1/merchant/auth/google/callback    // Google OAuth callback
```

### Platform Dashboard (New)

```typescript
// Base: /api/v1/platform/auth/

POST   /api/v1/platform/auth/login   ✅          // Login ONLY
GET    /api/v1/platform/auth/me      ✅          // Bootstrap ONLY
POST   /api/v1/platform/auth/logout  ✅          // Logout ONLY

// Everything else is DISABLED:
// ❌ No registration
// ❌ No password reset
// ❌ No email verification
// ❌ No Google OAuth
// ❌ No store switching
```

## Middleware Comparison

### Merchant Dashboard Middleware

```php
Route::middleware([
    'web',
    'auth:sanctum',
    'identity.route:merchant_users,merchant,enforce'
])->group(function () {
    // Merchant routes
});
```

### Platform Dashboard Middleware

```php
Route::middleware([
    'web',
    'auth:sanctum',
    'identity.route:platform,platform,enforce',
    'platform.authority:platform_admin'  // ← Extra security layer
])->group(function () {
    // Platform routes
});
```

## Session Configuration

### Merchant Dashboard

```env
SANCTUM_SESSION_COOKIE=ecommerce_session
NEXT_PUBLIC_APP_URL=http://localhost:3001
```

```javascript
{
  session: {
    tag: 'merchant',
    actor_type: 'MERCHANT_OWNER|MERCHANT_ADMIN|MERCHANT_STAFF',
    auth_domain: 'merchant',
    route_domain: 'merchant'
  }
}
```

### Platform Dashboard

```env
SANCTUM_SESSION_COOKIE=platform_session        # ← Different cookie
NEXT_PUBLIC_APP_URL=http://localhost:3002      # ← Different port
```

```javascript
{
  session: {
    tag: 'platform',                            // ← Different tag
    actor_type: 'SUPER_ADMIN',                  // ← Different actor
    auth_domain: 'platform',                    // ← Different domain
    route_domain: 'platform'
  }
}
```

## Feature Matrix

| Feature | Merchant Dashboard | Platform Dashboard |
|---------|-------------------|-------------------|
| **Self Registration** | ✅ Yes | ❌ No - admins created manually |
| **Login** | ✅ Yes | ✅ Yes |
| **Logout** | ✅ Yes | ✅ Yes |
| **Password Reset** | ✅ Yes | ❌ No - handled separately |
| **Email Verification** | ✅ Yes | ❌ No - not applicable |
| **Google OAuth** | ✅ Yes | ❌ No |
| **Store Management** | ✅ Own stores only | ✅ All stores |
| **Store Switching** | ✅ Yes | ❌ No - platform-wide access |
| **Onboarding Flow** | ✅ Yes | ❌ No |
| **Multi-tenant** | ✅ Yes - store scoped | ❌ No - platform-wide |
| **Session Isolation** | ✅ Via `merchant` tag | ✅ Via `platform` tag |

## Code Differences

### Login API Call

**Merchant Dashboard:**
```typescript
// src/lib/api/auth.ts
export async function login(credentials: LoginPayload) {
  return clientApi.post(
    '/api/v1/merchant/auth/login',  // ← Merchant endpoint
    credentials
  );
}
```

**Platform Dashboard:**
```typescript
// src/lib/api/auth.ts
export async function login(credentials: LoginPayload) {
  return clientApi.post(
    '/api/v1/platform/auth/login',  // ← Platform endpoint
    credentials
  );
}
```

### Bootstrap API Call

**Merchant Dashboard:**
```typescript
export async function bootstrap() {
  return clientApi.get('/api/v1/merchant/me');  // ← Merchant endpoint
}
```

**Platform Dashboard:**
```typescript
export async function bootstrap() {
  return clientApi.get('/api/v1/platform/auth/me');  // ← Platform endpoint
}
```

### UI Differences

**Merchant Dashboard:**
```tsx
// Has signup link
<CardContent>
  <LoginForm />
  <div>
    Need an account? <Link href="/signup">Create one</Link>
  </div>
</CardContent>
```

**Platform Dashboard:**
```tsx
// No signup link
<CardContent>
  <LoginForm />
  {/* No signup - admins don't self-register */}
</CardContent>
```

## Bootstrap Response Comparison

### Merchant Bootstrap Response

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "stores": [
      {"id": 1, "name": "My Store", "slug": "my-store"}
    ],
    "active_store": {
      "id": 1,
      "name": "My Store",
      "slug": "my-store"
    },
    "active_store_id": 1,
    "session": {
      "actor_type": "MERCHANT_OWNER",
      "auth_domain": "merchant",
      "route_domain": "merchant"
    },
    "onboarding": {
      "step": "completed",
      "is_completed": true
    },
    "permissions": ["products.create", "orders.view", ...]
  }
}
```

### Platform Bootstrap Response

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Platform Admin",
      "email": "admin@platform.com"
    },
    "stores": [],                    // ← Empty - no store scoping
    "active_store": null,            // ← Null - platform-wide access
    "active_store_id": null,         // ← Null
    "session": {
      "actor_type": "SUPER_ADMIN",   // ← Different actor type
      "auth_domain": "platform",     // ← Different domain
      "route_domain": "platform"
    },
    "onboarding": {
      "step": null,                  // ← Not applicable
      "is_completed": true
    },
    "permissions": [
      "platform.admin",              // ← Platform-wide permissions
      "stores.manage_all",
      "users.manage_all",
      ...
    ]
  }
}
```

## Session Storage Comparison

### Browser Cookies

**Merchant Dashboard:**
```
Cookie: ecommerce_session=eyJpdiI6...
Domain: localhost
Path: /
```

**Platform Dashboard:**
```
Cookie: platform_session=eyJpdiI6...     ← Different cookie name
Domain: localhost
Path: /
```

### LocalStorage/SessionStorage

Both dashboards may store similar data structure, but with different values:

**Merchant:**
```json
{
  "auth": {
    "user": {...},
    "actor_type": "MERCHANT_OWNER",
    "active_store_id": 1
  }
}
```

**Platform:**
```json
{
  "auth": {
    "user": {...},
    "actor_type": "SUPER_ADMIN",
    "active_store_id": null
  }
}
```

## Security Isolation

### Why Separate Sessions?

1. **Actor Type Isolation**: Merchant users cannot access platform routes and vice versa
2. **Permission Boundaries**: Different permission sets for different contexts
3. **Audit Trail**: Clear separation of who did what in which context
4. **Session Hijacking Protection**: Stolen merchant session cannot access platform
5. **Multi-tenancy**: Merchant sessions are store-scoped, platform sessions are not

### Middleware Enforcement

```php
// Merchant middleware checks:
- Is user authenticated? (auth:sanctum)
- Is session tagged 'merchant'? (identity.route)
- Does user belong to a store? (implicit)

// Platform middleware checks:
- Is user authenticated? (auth:sanctum)
- Is session tagged 'platform'? (identity.route)
- Is user a SUPER_ADMIN? (platform.authority)
```

## Testing Both Dashboards Simultaneously

You can run both dashboards at the same time:

```bash
# Terminal 1: Merchant Dashboard
cd /path/to/merchant-dashboard
npm run dev    # Runs on port 3001

# Terminal 2: Platform Dashboard
cd /path/to/platform-dashboard
npm run dev    # Runs on port 3002

# Terminal 3: Backend
cd /path/to/laravel-backend
php artisan serve    # Runs on port 8000
```

Then:
- Merchant Dashboard: http://localhost:3001
- Platform Dashboard: http://localhost:3002
- Backend API: http://localhost:8000

Sessions are completely isolated due to:
1. Different session tags (`merchant` vs `platform`)
2. Different cookie names (`ecommerce_session` vs `platform_session`)
3. Different actor types (MERCHANT_* vs SUPER_ADMIN)
4. Different middleware stacks

## Database Structure

### Users Table

```sql
-- Merchant users
id | name | email | actor_type | created_at
1  | John | john@example.com | MERCHANT_OWNER | 2024-01-01
2  | Jane | jane@example.com | MERCHANT_ADMIN | 2024-01-02

-- Platform users
100 | Admin | admin@platform.com | SUPER_ADMIN | 2024-01-01
```

### Sessions Table

```sql
-- Merchant session
id | user_id | payload | tag | last_activity
abc | 1 | {...} | merchant | 2024-07-15 10:00:00

-- Platform session
xyz | 100 | {...} | platform | 2024-07-15 10:05:00
```

## Summary Table

| Aspect | Merchant Dashboard | Platform Dashboard |
|--------|-------------------|-------------------|
| **Directory** | `dashbard-authentication-works/` | `platform-dashboard/` |
| **Port** | 3001 | **3002** |
| **URL** | http://localhost:3001 | http://localhost:3002 |
| **API Base** | `/api/v1/merchant/` | `/api/v1/platform/` |
| **Login Route** | `POST /merchant/auth/login` | `POST /platform/auth/login` |
| **Bootstrap Route** | `GET /merchant/me` | `GET /platform/auth/me` |
| **Session Cookie** | `ecommerce_session` | `platform_session` |
| **Session Tag** | `merchant` | `platform` |
| **Actor Type** | MERCHANT_* | SUPER_ADMIN |
| **Registration** | ✅ Allowed | ❌ Forbidden |
| **Store Scoping** | ✅ Yes | ❌ No (platform-wide) |
| **Onboarding** | ✅ Yes | ❌ No |
| **Password Reset** | ✅ Yes | ❌ No |
| **Email Verify** | ✅ Yes | ❌ No |
| **OAuth** | ✅ Yes | ❌ No |

---

**Key Takeaway**: These are **completely separate applications** with **different authentication systems**, **different session contexts**, and **different user types**. They just happen to share the same UI framework (Next.js) and backend (Laravel).
