# Multi-Tenant Asset URL Fix

## Problem Summary
In a multi-tenant SaaS architecture with multiple store subdomains (`demo.justshop.test`, `test.justshop.test`, etc.) pointing to a single backend (`localhost:8000`), image URLs were being generated with the tenant's subdomain instead of the backend URL.

This caused 404 errors because tenant subdomains don't serve Laravel - they're just frontend domains.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Multi-Tenant Setup                        │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Frontend (Nuxt SSR)                    Backend (Laravel)   │
│  ┌────────────────────┐                 ┌────────────────┐ │
│  │ demo.justshop.test │────API calls───>│ localhost:8000 │ │
│  │ test.justshop.test │────API calls───>│                │ │
│  │ test1.justshop.test│────API calls───>│   /storage/    │ │
│  └────────────────────┘                 │   hero/*.jpg   │ │
│         :3000                            └────────────────┘ │
│                                                 :8000        │
└─────────────────────────────────────────────────────────────┘
```

## The Problem

### Before Fix ❌
```php
// HeroBanner.php
public function getImageUrlAttribute(){
    return $this->image_path ? asset('storage/'.$this->image_path) : null;
}
```

**What happened**:
1. Nuxt SSR makes API request with `Host: demo.justshop.test` header
2. Laravel's `asset()` helper respects the incoming host
3. Generated URL: `http://demo.justshop.test/storage/hero/banner.jpg`
4. Browser tries to load image from `demo.justshop.test` → **404 Not Found**
5. The file actually exists at `localhost:8000/storage/hero/banner.jpg`

### After Fix ✅
```php
// HeroBanner.php
public function getImageUrlAttribute(){
    if (!$this->image_path) {
        return null;
    }
    
    // Use APP_URL for multi-tenant setups to ensure assets are served from backend
    $appUrl = rtrim(config('app.url'), '/');
    return $appUrl . '/storage/' . $this->image_path;
}
```

**What happens now**:
1. Nuxt SSR makes API request with `Host: demo.justshop.test` header
2. Model explicitly uses `APP_URL` from config
3. Generated URL: `http://localhost:8000/storage/hero/banner.jpg`
4. Browser loads image from backend → **200 OK** ✅

## Why This Happens

Laravel's `asset()` helper has smart defaults:
- In single-tenant apps: Uses request host (good!)
- In multi-tenant apps: Still uses request host (bad!)

```php
// Laravel's default behavior
asset('file.jpg')
// Returns: http://{current-request-host}/file.jpg
```

This is great for single-tenant apps but breaks in multi-tenant SaaS where:
- Frontend domains are per-tenant
- Backend domain is shared
- Assets live on the backend

## The Solution Pattern

For any model with file paths in multi-tenant setups:

```php
public function getFileUrlAttribute(){
    if (!$this->file_path) {
        return null;
    }
    
    // Always use APP_URL in multi-tenant setups
    $appUrl = rtrim(config('app.url'), '/');
    return $appUrl . '/storage/' . $this->file_path;
}
```

## Alternative Solutions (Not Used)

### Option A: Configure Nginx for All Tenant Domains
**Pros**: Standard approach
**Cons**: Need nginx config for every new tenant (doesn't scale)

### Option B: Frontend Proxy Rewrite
**Pros**: Keeps tenant URLs
**Cons**: Extra proxy logic, more complexity

### Option C: Use APP_URL (Chosen) ✅
**Pros**: Simple, scales automatically, one backend serves all assets
**Cons**: None for this architecture

## Configuration Check

Verify your `.env` has:
```env
APP_URL=http://localhost:8000
```

This ensures all asset URLs point to the actual backend server.

## Testing

```bash
# Test API response
curl 'http://localhost:8000/api/v1/storefront/runtime/page/home?path=/en' \
  -H 'X-Storefront-Version: 2026-05-28' \
  -H 'Host: demo.justshop.test' | python3 -m json.tool

# Should see:
# "imageUrl": "http://localhost:8000/storage/hero/banner.jpg"
# NOT: "imageUrl": "http://demo.justshop.test/storage/hero/banner.jpg"
```

## Files Modified
- `laratenant-backend/app/Models/HeroBanner.php` - Fixed `getImageUrlAttribute()`

## Related Issues
This same pattern should be applied to any other models that serve files:
- Product images
- Category images
- User avatars
- Store logos
- Marketing assets

All should use `APP_URL` instead of `asset()` helper in multi-tenant contexts.

## Status
✅ **RESOLVED** - Asset URLs now correctly point to backend in multi-tenant setup
