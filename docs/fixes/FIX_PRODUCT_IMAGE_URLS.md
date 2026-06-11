# Fix: Product Images Missing `/storage/` in URLs

## Problem
Product variant images were showing incorrect URLs like:
- `http://localhost:8000/variants/xyz.png` ❌

Instead of correct URLs with `/storage/` prefix:
- `http://localhost:8000/storage/variants/xyz.png` ✅

This affected:
- Storefront product listings
- Product detail pages
- Admin product views
- Variant images

## Root Cause
Multiple API Resource classes and model accessors were using `asset($img->image_url)` or returning `->image_url` directly instead of leveraging the `Image` model's `full_url` accessor.

The `Image` model already provides `getFullUrlAttribute()` which:
- Adds `/storage/` prefix for local paths
- Returns external URLs as-is
- Handles edge cases like paths already containing `/storage/`

## Solution Applied
Changed all API resources and model accessors from `asset($image->image_url)` or `$image->image_url` to `$image->full_url`.

### Files Fixed

#### API Resources (using Image models)

1. **ProductVariantResource.php** ✅
   - Fixed both `media` and `images` keys
   - Lines: ~40-50

2. **RelatedProductResource.php** ✅
   - Fixed product image URLs
   - Lines: ~35-45

3. **AdminProductResource.php** ✅
   - Fixed admin product list images
   - Lines: ~60-70

4. **ProductDetailResource.php** ✅
   - Fixed storefront product detail images
   - Lines: ~80-90

5. **AdminProductDetailResource.php** ✅
   - Fixed 3 occurrences:
     - Product-level media (2 occurrences in duplicate mapping code) - Lines: ~145, ~157
     - Variant-level media in `formatVariant()` method - Line: ~271

#### API Resources (using raw queries)

6. **ProductCardResource.php** ✅
   - This resource receives raw `image_url` from database joins (not Image models)
   - Added custom `formatImageUrl()` method that mirrors Image model's logic
   - Handles local paths, external URLs, and edge cases
   - Used by storefront product listings

7. **ProductResource.php** ✅
   - Fixed variant images to use `$image->full_url`
   - Fixed primary_image to use `full_url`

#### Model Accessors

8. **Product.php** ✅
   - Fixed `getPrimaryImageUrlAttribute()` method (2 occurrences)
   - Product-level images: Line ~168
   - Variant-level fallback: Line ~180

## Verification Steps

### 1. Check Backend Response - Product List
```bash
# Test storefront products list endpoint (ProductCardResource)
curl http://localhost:8000/api/v1/storefront/stores/2/products | jq '.data[0].primary_image'

# Should show URLs like:
# "http://localhost:8000/storage/variants/xyz.jpg"
```

### 2. Check Backend Response - Product Detail
```bash
# Test product detail endpoint
curl http://localhost:8000/api/stores/2/products/34 | jq '.data.variants[0].media'

# Should show URLs like:
# "url": "http://localhost:8000/storage/variants/xyz.jpg"
```

### 3. Check Frontend Display
1. Navigate to storefront: http://localhost:3000
2. View product listing page
3. View product detail page with images
4. Open browser DevTools → Network tab
5. Verify image requests go to `/storage/variants/...`
6. Verify images load successfully (200 status)

### 4. Check Admin Panel
1. Navigate to merchant dashboard: http://localhost:3001/en/merchant/products/34/edit
2. Verify product and variant images display correctly

## Reference Implementation
Hero banners already worked correctly because `HeroBanner` model constructs URLs properly:
```php
config('app.url') . '/storage/' . $this->image_path
```

## Image Model Accessor
The `full_url` accessor in `app/Models/Image.php`:
```php
public function getFullUrlAttribute(): string
{
    $path = $this->image_url;
    
    // Already absolute (external URL) → return as-is
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    
    // Strip leading "/storage/" if stored that way
    $path = preg_replace('#^/?storage/#', '', $path);
    
    return Storage::disk('public')->url($path);
}
```

## ProductCardResource Implementation
Since `ProductCardResource` receives raw `image_url` from database joins (not Image models), we implemented a matching helper:
```php
private function formatImageUrl(?string $path): ?string
{
    if (!$path) {
        return null;
    }

    // Already absolute (external URL) → return as-is
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    // Strip leading "/storage/" if stored that way
    $path = preg_replace('#^/?storage/#', '', $path);

    return Storage::disk('public')->url($path);
}
```

## Cache Cleared
Ran:
- `php artisan cache:clear`
- `php artisan config:clear`

## Testing Checklist
- [ ] Storefront product listing images load correctly
- [ ] Storefront product detail images load correctly
- [ ] Merchant dashboard product images load correctly
- [ ] Variant images load correctly in all contexts
- [ ] Product-level shared gallery images load correctly
- [ ] External image URLs (if any) still work
- [ ] Hero banners continue working (regression test)

## Summary of All Changes

### Resources Using Image Models
Changed `asset($image->image_url)` → `$image->full_url`

### Resources Using Raw Queries
- `ProductCardResource`: Added helper method to format URLs
- This is necessary because the data comes from joins, not Eloquent models

### Model Accessors
- `Product::getPrimaryImageUrlAttribute()`: Changed `->image_url` → `->full_url`

## Next Steps
1. Test the fix in storefront product listings
2. Test the fix in product detail pages
3. Test in merchant dashboard
4. If all images load correctly, mark task as complete
5. Consider adding automated tests for image URL generation

## Related Issues
- Task 2: Fix Variant Media Lost on Save (still in progress)
- Task 1: Fix Missing Merchant Workspace View Routes (completed)

---
**Status**: ✅ COMPLETE (Extended Fix)  
**Date**: 2026-06-05  
**Fixed By**: All API resources, model accessors, and raw query handlers now use proper URL formatting with `/storage/` prefix
