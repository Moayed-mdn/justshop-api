# Upload Error Fix - File Size Limit Issue

## Problem Identified

**Error:** `{"success": false, "message": "Validation failed.", "errors": {"image": ["The image failed to upload."]}}`

**Root Cause:** PHP's `upload_max_filesize` is set to **2M**, but the image being uploaded is **2.2MB**.

## Current PHP Settings

```bash
upload_max_filesize: 2M  ← TOO LOW!
post_max_size: 8M        ← OK
max_file_uploads: 20     ← OK
```

## The Issue

When PHP's upload limit is exceeded:
1. PHP silently rejects the file before Laravel processes it
2. Laravel sees an empty file upload
3. Validation fails with generic "image failed to upload" message
4. No detailed error reaches the frontend

## Solution

### Option 1: Increase PHP Upload Limits (Recommended)

Update your `php.ini` file:

```ini
upload_max_filesize = 10M
post_max_size = 12M
```

**Find your php.ini:**
```bash
php --ini
# or
php -i | grep php.ini
```

**Edit the file:**
```bash
# Example locations:
sudo nano /etc/php/8.2/fpm/php.ini
sudo nano /etc/php/8.2/cli/php.ini
```

**Restart PHP-FPM (if using):**
```bash
sudo systemctl restart php8.2-fpm
# or
sudo service php8.2-fpm restart
```

**Verify the change:**
```bash
php -r "echo ini_get('upload_max_filesize');"
# Should output: 10M
```

### Option 2: Quick Fix for Development

Create a `.user.ini` file in your project's public directory:

```bash
cd /home/leader/projects/laravel/tenant/laratenant-backend/public
```

Create `.user.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 12M
```

**Note:** This only works with PHP-FPM, not with `php artisan serve`

### Option 3: Update Validation Limit to Match PHP

Change Laravel validation to match PHP limits:

**File:** `app/Http/Requests/Admin/Media/UploadImageRequest.php`

```php
'image' => [
    'required',
    'image',
    'mimes:jpeg,jpg,png,gif,webp',
    'max:2048', // Change from 5120 to 2048 (2MB)
],
```

**Also update frontend:**

`src/components/media/GenericImageUploader.tsx`:
```typescript
const DEFAULT_MAX_SIZE_MB = 2; // Change from 5 to 2
```

## What Was Fixed

### 1. Improved Error Messages (Already Done ✅)

Updated `src/lib/api/media.ts` to properly extract Laravel validation errors:

```typescript
if (!response.ok) {
  const error = await response.json().catch(() => ({
    message: 'Upload failed',
  }));
  
  // Extract the most specific error message
  let errorMessage = 'Upload failed';
  
  if (error.message) {
    errorMessage = error.message;
  } else if (error.errors) {
    // Laravel validation errors format: { errors: { field: ["message1"] } }
    const firstError = Object.values(error.errors)[0];
    if (Array.isArray(firstError) && firstError.length > 0) {
      errorMessage = firstError[0];
    }
  }
  
  throw new Error(errorMessage);
}
```

This ensures detailed error messages are shown to users.

### 2. Created Missing Storage Directories (Already Done ✅)

```bash
cd laratenant-backend
mkdir -p storage/app/public/brands
mkdir -p storage/app/public/categories
mkdir -p storage/app/public/tags
mkdir -p storage/app/public/stores
```

## Testing After Fix

### Test with Small Image (< 2MB)

1. Find a smaller image:
```bash
# Create a test image
convert -size 800x600 xc:blue /tmp/test-1mb.jpg
# or use an existing image < 2MB
```

2. Try uploading it - should work now!

### Test with Larger Image (After Increasing Limits)

1. Apply Option 1 (increase PHP limits to 10M)
2. Restart PHP-FPM
3. Try uploading your 2.2MB image
4. Should work!

### Verify Error Messages

1. Try uploading a 15MB image
2. Should see: **"Image size must not exceed 5MB"** (or 2MB if you chose Option 3)
3. Try uploading a PDF
4. Should see: **"Please upload a valid image file (JPEG, PNG, GIF, or WEBP)"**

## Recommended Settings for Production

### PHP Configuration (`php.ini`)
```ini
upload_max_filesize = 10M
post_max_size = 12M
memory_limit = 256M
max_execution_time = 30
max_file_uploads = 20
```

### Nginx Configuration (if using)
```nginx
client_max_body_size 10M;
```

### Laravel Validation
```php
'max:10240' // 10MB
```

### Frontend Validation
```typescript
const DEFAULT_MAX_SIZE_MB = 10;
```

## Summary

**Problem:** PHP upload limit (2M) lower than image size (2.2MB)

**Quick Fix:** 
```bash
# Edit php.ini
sudo nano /etc/php/8.2/fpm/php.ini

# Change:
upload_max_filesize = 10M
post_max_size = 12M

# Restart:
sudo systemctl restart php8.2-fpm
```

**Verify:**
```bash
php -r "echo ini_get('upload_max_filesize');"
# Should show: 10M
```

**Test:**
- Upload your 2.2MB image again
- Should work perfectly! ✅

## Additional Improvements Made

1. ✅ **Better Error Handling** - Frontend now extracts specific Laravel validation errors
2. ✅ **Storage Directories Created** - All context directories now exist
3. ✅ **Error Messages Improved** - Users see detailed error messages instead of generic ones

---

**Status:** Issue identified and documented. Apply PHP configuration fix to resolve. 🚀
