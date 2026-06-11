# 🔧 Quick Fix - Upload Size Limit Issue

## Problem

Upload fails with generic error: `"The image failed to upload"`

Your image: **2.2MB**  
PHP limit: **2M** ❌

## Quick Solution (2 minutes)

### Step 1: Find your php.ini file

```bash
php --ini | grep "Loaded Configuration File"
```

Example output: `/etc/php/8.2/cli/php.ini`

### Step 2: Edit php.ini

```bash
# Replace 8.2 with your PHP version
sudo nano /etc/php/8.2/fpm/php.ini
sudo nano /etc/php/8.2/cli/php.ini
```

### Step 3: Change these lines

Find and change:
```ini
upload_max_filesize = 2M
```

To:
```ini
upload_max_filesize = 10M
```

Also change:
```ini
post_max_size = 8M
```

To:
```ini
post_max_size = 12M
```

**Save and exit:** `Ctrl+X`, then `Y`, then `Enter`

### Step 4: Restart PHP

```bash
# If using PHP-FPM:
sudo systemctl restart php8.2-fpm

# If using Apache:
sudo systemctl restart apache2

# If using php artisan serve:
# Just restart: Ctrl+C and run again
```

### Step 5: Verify

```bash
php -r "echo 'Upload limit: ' . ini_get('upload_max_filesize');"
```

Should show: `Upload limit: 10M` ✅

### Step 6: Test Upload Again

- Go back to your browser
- Try uploading the image again
- Should work! 🎉

---

## Alternative: Quick Dev Fix (No root needed)

If you can't edit php.ini, reduce the validation limit:

### Backend

**File:** `laratenant-backend/app/Http/Requests/Admin/Media/UploadImageRequest.php`

Line 19-24, change:
```php
'max:5120', // 5MB
```

To:
```php
'max:2048', // 2MB (matches PHP limit)
```

### Frontend (React)

**File:** `laratenant-commerce/src/components/media/GenericImageUploader.tsx`

Line 17, change:
```typescript
const DEFAULT_MAX_SIZE_MB = 5;
```

To:
```typescript
const DEFAULT_MAX_SIZE_MB = 2;
```

### Frontend (Vue)

**File:** `justshop-frontend/app/components/merchant/shared/GenericImageUploader.vue`

Find and change (around line 10-15):
```typescript
const MAX_FILE_SIZE_MB = 5
```

To:
```typescript
const MAX_FILE_SIZE_MB = 2
```

Then users need to compress images before uploading.

---

## What I Fixed

While investigating, I also:

1. ✅ **Improved error messages** - You'll now see specific errors instead of generic ones
2. ✅ **Created missing directories** - `brands/`, `categories/`, `tags/`, `stores/`
3. ✅ **Better error extraction** - Frontend now shows Laravel validation errors properly

---

## Summary

**Root Cause:** PHP's `upload_max_filesize` (2M) < Your image size (2.2MB)

**Best Fix:** Increase PHP limit to 10M (see Step 1-6 above)

**Quick Fix:** Lower app limits to 2MB (works immediately, but users need smaller images)

Choose the best option for your needs!
