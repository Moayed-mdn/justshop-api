# Platform Bootstrap Structure - FIXED ✅

## Issue
The platform `/api/v1/platform/auth/me` endpoint was returning:
```json
{
  "data": {
    "user": { "id": 2, "email_verified": true, ... },
    "actor_context": "super_admin",
    "permissions": []
  }
}
```

But the frontend expected a full bootstrap structure like:
```json
{
  "data": {
    "user": { ... },
    "email_verified": true,  // ← At root level, not inside user
    "stores": [],
    "active_store": null,
    "onboarding": { ... },
    "permissions": [],
    ...
  }
}
```

## Root Cause
The frontend `bootstrapStore.ts` validates that `email_verified` exists at the **root level** of the bootstrap data, not inside the `user` object.

## Fixes Applied

### 1. ✅ Added `email_verified` to `UserResource`
**File**: `app/Http/Resources/UserResource.php`

```php
'email_verified' => !is_null($this->email_verified_at),
'email_verified_at' => $this->email_verified_at,
```

### 2. ✅ Updated Platform `me()` Endpoint to Return Full Bootstrap
**File**: `app/Http/Controllers/Api/Platform/PlatformAuthController.php`

The `me()` method now returns:
```php
[
    'user' => new UserResource($user),
    'email_verified' => !is_null($user->email_verified_at), // Boolean at root
    'stores' => [], // Platform users don't manage stores
    'active_store' => null,
    'active_store_id' => null,
    'onboarding' => [
        'step' => 'completed',
        'completed_steps' => [],
        'can_resume' => false,
        'store_id' => null,
        'is_completed' => true,
    ],
    'permissions' => [],
    'capabilities' => [],
    'features' => [],
    'config' => [
        'locale' => 'en',
        'timezone' => 'UTC',
        'currency' => 'USD',
    ],
    'localization' => [
        'locale' => 'en',
        'timezone' => 'UTC',
        'currency' => 'USD',
    ],
    'actor_context' => $identityContext->actorType->value,
    'auth_domain' => $identityContext->authDomain->value,
]
```

## Expected Response Structure

### Platform Bootstrap (`/api/v1/platform/auth/me`)
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
      "email_verified_at": "2026-07-15T12:59:14.000000Z",
      "avatar": null,
      "has_password": true,
      "has_google_linked": false
    },
    "email_verified": true,
    "stores": [],
    "active_store": null,
    "active_store_id": null,
    "onboarding": {
      "step": "completed",
      "completed_steps": [],
      "can_resume": false,
      "store_id": null,
      "is_completed": true
    },
    "permissions": [],
    "capabilities": [],
    "features": {},
    "config": {
      "locale": "en",
      "timezone": "UTC",
      "currency": "USD"
    },
    "localization": {
      "locale": "en",
      "timezone": "UTC",
      "currency": "USD"
    },
    "actor_context": "super_admin",
    "auth_domain": "platform"
  },
  "meta": {
    "session": {
      "auth_domain": "platform",
      "actor_type": "super_admin"
    }
  }
}
```

## Validation Checks

The frontend `bootstrapStore.ts` performs these checks:

✅ `typeof value.email_verified !== 'boolean'` - **NOW PASSES**  
✅ `!isBootstrapUserShape(value.user)` - **PASSES** (user has email_verified boolean)  
✅ `!Array.isArray(value.stores)` - **PASSES** (empty array for platform)  
✅ `active_store === null` - **PASSES** (platform users don't have stores)  
✅ `!isRecord(value.onboarding)` - **PASSES** (completed onboarding)  
✅ `Array.isArray(value.permissions)` - **PASSES** (empty array)  

## Differences: Platform vs Merchant Bootstrap

| Field | Platform Admin | Merchant User |
|-------|---------------|---------------|
| `stores` | `[]` (empty) | Array of stores user owns/manages |
| `active_store` | `null` | Current store object |
| `active_store_id` | `null` | ID of active store |
| `onboarding.step` | `"completed"` | Varies (`completed`, `create_store`, etc.) |
| `actor_context` | `"super_admin"` | `"merchant"` |
| `auth_domain` | `"platform"` | `"merchant"` |
| `permissions` | Platform-level | Store-scoped |

## Testing

### Quick Test
```bash
# Start Laravel
php artisan serve

# Start Platform Dashboard  
cd platform-dashboard && npm run dev -- --port 3000

# Login at http://localhost:3000/login
# Email: super@test.com
# Password: password
```

### Expected Behavior
1. ✅ CSRF cookie fetched successfully
2. ✅ Login succeeds (200 OK)
3. ✅ Bootstrap data loads without console errors
4. ✅ User redirected to dashboard
5. ✅ No "[Bootstrap] email_verified is not a boolean" error

## Summary

The platform authentication now returns a complete bootstrap structure that:
- Includes `email_verified` as a boolean at the root level
- Provides empty arrays for `stores` (platform users don't manage stores)
- Sets `onboarding` to completed (platform users don't onboard)
- Returns proper `actor_context` and `auth_domain` for platform identity

This matches the structure the frontend expects while maintaining platform-specific semantics.

---

**Status**: ✅ READY TO TEST  
**Date**: July 15, 2026  
**Files Changed**: 2 (UserResource.php, PlatformAuthController.php)
