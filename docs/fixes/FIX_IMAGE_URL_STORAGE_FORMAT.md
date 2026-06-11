# Fix: Image URLs Stored as Absolute URLs in Database

## Problem
Images were being stored in the database with **absolute URLs** instead of **relative paths**:

**Bad** ❌:
```
image_url: "http://localhost:8000/storage/variants/xyz.jpg"
```

**Good** ✅:
```
image_url: "variants/xyz.jpg"
```

### Why This Matters
1. The `Image` model has a `full_url` accessor that adds `/storage/` prefix
2. When `image_url` already contains absolute URL, the accessor returns it as-is
3. This caused inconsistent behavior:
   - Some images: `http://localhost:8000/storage/variants/xyz.jpg` (stored as absolute)
   - Other images: `variants/xyz.jpg` (stored correctly as relative)

### Impact
- Storefront API returned mixed URL formats
- Some images showed only 1 of 2 variant images
- Database contained environment-specific URLs (breaks if domain changes)
- Made the application less portable

## Root Cause

1. **UploadImageAction** returns 3 formats:
   ```php
   return [
       'path' => 'variants/xyz.jpg',           // ✅ Relative
       'url' => '/storage/variants/xyz.jpg',   // ⚠️ Has /storage/
       'full_url' => 'http://.../.jpg',        // ❌ Absolute
   ];
   ```

2. **Frontend** was using `url` or `full_url` in some cases

3. **AdminProductRepository** was saving whatever the frontend sent without normalization

## Solution

### 1. Added Normalization in Repository ✅

Added `normalizeImagePath()` method to `AdminProductRepository`:

```php
private function normalizeImagePath(string $url): string
{
    // If it's an external URL (not from our storage), keep as-is
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
        $appUrl = config('app.url');
        // Only normalize if it's from our own domain
        if (!str_starts_with($url, $appUrl)) {
            return $url; // External URL - preserve
        }
        // Strip our domain
        $url = str_replace($appUrl, '', $url);
    }

    // Strip leading /storage/ prefix
    $url = preg_replace('#^/?storage/#', '', $url);

    return $url;
}
```

Modified methods:
- `createProductMedia()` - now calls `normalizeImagePath()` before saving
- `createVariantMedia()` - now calls `normalizeImagePath()` before saving

### 2. Created Migration to Fix Existing Data ✅

Migration: `fix_image_urls_remove_domain.php`

Scans the `images` table and normalizes all URLs:
- Strips `http://localhost:8000` (or any configured app.url)
- Strips `/storage/` prefix
- Preserves external URLs (non-localhost domains)

Ran successfully: ✅ 650.45ms

## Files Modified

1. **AdminProductRepository.php** ✅
   - Added `normalizeImagePath()` helper method
   - Updated `createProductMedia()` to normalize URLs
   - Updated `createVariantMedia()` to normalize URLs

2. **Database Migration** ✅
   - `database/migrations/fix_image_urls_remove_domain.php`
   - Fixed all existing records in `images` table

## Verification

Before fix:
```sql
SELECT id, image_url FROM images WHERE imageable_id = 257;
-- 269 | http://localhost:8000/storage/variants/5me1AFfYcvWQ88bdcUde.jpg
-- 270 | variants/R18J1UEsHOucrWvogRNa.jpg
```

After fix:
```sql
SELECT id, image_url FROM images WHERE imageable_id = 257;
-- 269 | variants/5me1AFfYcvWQ88bdcUde.jpg
-- 270 | variants/R18J1UEsHOucrWvogRNa.jpg
```

Now when fetched through API:
```json
{
  "images": [
    {
      "id": 269,
      "url": "http://localhost:8000/storage/variants/5me1AFfYcvWQ88bdcUde.jpg",
      "alt_text": null,
      "is_primary": 1
    },
    {
      "id": 270,
      "url": "http://localhost:8000/storage/variants/R18J1UEsHOucrWvogRNa.jpg",
      "alt_text": null,
      "is_primary": 0
    }
  ]
}
```

## Testing Checklist

- [ ] Verify storefront product pages show all variant images
- [ ] Verify new image uploads are stored as relative paths
- [ ] Check database: `SELECT * FROM images ORDER BY created_at DESC LIMIT 10;`
- [ ] Confirm no `http://` in `image_url` column (except external URLs)
- [ ] Test in different environments (domain changes should not break images)

## Benefits

1. **Consistency**: All local images stored as relative paths
2. **Portability**: URLs work across different domains/environments
3. **Correctness**: The `full_url` accessor now always works correctly
4. **Future-proof**: New uploads will be normalized automatically

## Related Issues

- Task 3: Fix Product Images Missing `/storage/` Prefix (completed earlier)
- This fix ensures the data layer is correct, complementing the API layer fixes

---

**Status**: ✅ COMPLETE  
**Date**: 2026-06-06  
**Migration Runtime**: 650.45ms  
**Records Fixed**: All images with absolute URLs normalized to relative paths
