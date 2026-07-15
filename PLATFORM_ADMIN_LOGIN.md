# Platform Admin Login Credentials

## ✅ SUPER_ADMIN User Found

A SUPER_ADMIN user exists in the database and is ready for platform dashboard authentication.

### Login Credentials

```
Email: super@test.com
Password: password
```

### User Details

| Field | Value |
|-------|-------|
| **ID** | 2 |
| **Name** | Super Admin |
| **Email** | super@test.com |
| **Email Verified** | ✅ Yes (2026-07-14 11:34:39) |
| **Active Store ID** | null (platform users don't need stores) |
| **Role** | super_admin |
| **Created** | 2026-07-14 11:34:39 |

### Identity Context

When this user logs in, they get the following identity context:

```
Actor Type: super_admin
Auth Domain: platform
Actor ID: 2
Onboarding Required: No
```

### Permissions

This user has **ALL** platform permissions (85+ permissions), including:

- **User Management**: view, create, block, delete, restore
- **Product Management**: view, create, update, delete, restore
- **Order Management**: view, update_status, cancel, refund
- **Store Management**: view, update, delete
- **CMS Management**: docs, blog, pages (view, create, update, delete, publish)
- **Marketing**: platform & store marketing pages
- **Platform Admin**: Full access to all platform features

### Platform Authentication Endpoints

The frontend at `localhost:3002` should use:

```typescript
// Login
POST /api/v1/platform/auth/login
{
  "email": "super@test.com",
  "password": "password"
}

// Get User Info (Bootstrap)
GET /api/v1/platform/auth/me

// Logout
POST /api/v1/platform/auth/logout
```

### Key Differences from Merchant Auth

| Feature | Platform (`super@test.com`) | Merchant (`merchant@test.com`) |
|---------|----------------------------|--------------------------------|
| **Login Endpoint** | `/api/v1/platform/auth/login` | `/api/v1/merchant/auth/login` |
| **Bootstrap Endpoint** | `/api/v1/platform/auth/me` | `/api/v1/merchant/me` |
| **Session Domain** | `platform` | `merchant` |
| **Auth Domain** | `platform` | `merchant` |
| **Actor Type** | `super_admin` | `merchant` |
| **Requires Store** | ❌ No | ✅ Yes |
| **Middleware** | `platform.authority:platform_admin` | None (merchant-specific) |

### Testing Login with cURL

```bash
# 1. Get CSRF cookie
curl -c cookies.txt \
  -X GET 'http://localhost:8000/sanctum/csrf-cookie'

# 2. Login
curl -b cookies.txt -c cookies.txt \
  -X POST 'http://localhost:8000/api/v1/platform/auth/login' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "email": "super@test.com",
    "password": "password"
  }'

# 3. Get user info
curl -b cookies.txt \
  -X GET 'http://localhost:8000/api/v1/platform/auth/me' \
  -H 'Accept: application/json'
```

### Expected Response from `/auth/me`

```json
{
  "data": {
    "user": {
      "id": 2,
      "name": "Super Admin",
      "email": "super@test.com",
      "email_verified_at": "2026-07-14T11:34:39.000000Z"
    },
    "actor_context": "super_admin",
    "auth_domain": "platform",
    "permissions": []
  },
  "meta": {
    "session": {
      "auth_domain": "platform",
      "actor_type": "super_admin"
    }
  },
  "message": "Success"
}
```

### Frontend Integration (Next.js)

Your platform dashboard at `localhost:3002` should:

1. **Use the correct base URL**: `/api/v1/platform` (NOT `/api/v1/merchant`)
2. **Call the right endpoints**:
   - Login: `POST /api/v1/platform/auth/login`
   - Bootstrap: `GET /api/v1/platform/auth/me`
   - Logout: `POST /api/v1/platform/auth/logout`
3. **Check session domain**: Ensure `meta.session.auth_domain === 'platform'`
4. **Verify actor type**: Ensure `data.actor_context === 'super_admin'`

### Common Issues

❌ **Using merchant endpoints for platform login**
```javascript
// WRONG - This is for merchant dashboard
POST /api/v1/merchant/auth/login
```

✅ **Using platform endpoints**
```javascript
// CORRECT - This is for platform dashboard
POST /api/v1/platform/auth/login
```

❌ **Session domain mismatch**
- If you login via merchant endpoints, the session will be tagged as `merchant`
- Platform routes will reject `merchant` sessions (401 Unauthorized)

✅ **Proper session isolation**
- Login via platform endpoints → session tagged as `platform`
- Platform routes will accept `platform` sessions
- Middleware enforces this boundary

### Source Files

- **Seeder**: `database/seeders/StoreSeeder.php` (line 28-36)
- **Routes**: `routes/api/v1/platform/auth.php`
- **Controller**: `app/Http/Controllers/Api/Platform/PlatformAuthController.php`
- **Middleware**: `app/Http/Middleware/ApplyIdentityRouteContext.php`
- **Identity Resolver**: `app/Services/Auth/IdentityContextResolver.php`

---

## Next Steps

1. ✅ **Verify user exists** - DONE (User ID: 2)
2. 🔄 **Test login in Postman/cURL** - Use credentials above
3. 🔄 **Update frontend** - Ensure localhost:3002 uses `/api/v1/platform/*` endpoints
4. 🔄 **Verify session domain** - Check that login returns `auth_domain: "platform"`
5. 🔄 **Test protected routes** - Try accessing platform admin features

---

**Created**: July 15, 2026  
**User Found By**: Tinker Query on `User::role(RoleEnum::SUPER_ADMIN->value)`
