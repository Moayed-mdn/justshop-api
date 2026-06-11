# 🧪 Quick Testing Guide - Image Upload System

## ✅ All Forms Updated - Ready to Test!

This guide will help you quickly test all the updated forms with image upload functionality.

---

## Prerequisites

### 1. Backend Running
```bash
cd laratenant-backend
php artisan serve --port=8000
```

### 2. Storage Symlink Created
```bash
cd laratenant-backend
php artisan storage:link
```

### 3. Frontend Apps Running

**React/Next.js (Merchant Dashboard):**
```bash
cd laratenant-commerce
npm run dev # Port 4000
```

**Vue/Nuxt (Storefront):**
```bash
cd justshop-frontend
npm run dev # Port 3000
```

---

## Test Scenarios

### 🎯 Test 1: Products (React)

**URL:** `http://localhost:4000/en/merchant/products/new`

**Steps:**
1. Login as merchant
2. Navigate to Products → New Product
3. Go to "Media" tab
4. **Test drag & drop:**
   - Drag an image file onto the upload area
   - Verify progress indicator appears
   - Verify image preview shows
   - Verify path is auto-filled (e.g., `products/abc123xyz.jpg`)
5. **Test click to upload:**
   - Click on upload area
   - Select an image from file picker
   - Verify upload and preview
6. **Test multiple images:**
   - Upload 2-3 images
   - Use "Move Up" / "Move Down" buttons to reorder
   - Add alt text to each image
7. **Test delete:**
   - Click X button on an image
   - Verify image is removed

**Expected Result:**
- ✅ Drag & drop works
- ✅ Click to upload works
- ✅ Progress indicator visible
- ✅ Image preview displays
- ✅ Path auto-generated
- ✅ Multiple images supported
- ✅ Reordering works
- ✅ Delete works

---

### 🎯 Test 2: Product Variants (React)

**URL:** `http://localhost:4000/en/merchant/products/{id}/edit`

**Steps:**
1. Edit an existing product
2. Go to "Structure" tab
3. Click "Add Image" on a variant row
4. **Test upload:**
   - Drag/click to upload variant image
   - Verify preview shows
   - Click "Save"
5. **Test delete:**
   - Click "Remove" on variant image
   - Verify image is deleted

**Expected Result:**
- ✅ Variant image upload works
- ✅ Preview displays in modal
- ✅ Image saved to variant
- ✅ Delete works

---

### 🎯 Test 3: Brands (React)

**URL:** `http://localhost:4000/en/merchant/brands/new`

**Steps:**
1. Navigate to Brands → New Brand
2. Fill in brand name
3. **Test logo upload:**
   - Find "Brand Logo" section
   - Drag/click to upload logo
   - Verify preview shows
4. **Save brand:**
   - Click "Create Brand"
   - Verify brand is created with logo
5. **Test edit:**
   - Edit the brand
   - Replace logo with new image
   - Click "Save Changes"
   - Verify logo updated
6. **Test delete:**
   - Edit brand
   - Click X on logo preview
   - Save brand without logo
   - Verify logo removed (should be nullable)

**Expected Result:**
- ✅ Logo upload works
- ✅ Preview displays
- ✅ Brand saves with logo
- ✅ Logo can be updated
- ✅ Logo can be removed (optional field)

---

### 🎯 Test 4: Hero Banners - React (NEWLY UPDATED)

**URL:** `http://localhost:4000/en/merchant/hero-banners/new`

**Steps:**
1. Navigate to Hero Banners → New Banner
2. **Select "Image" as Visual Type** (Important!)
3. **Test upload:**
   - Find "Hero Banner Image" uploader
   - Drag/click to upload image
   - Verify preview shows
4. **Fill translations:**
   - English: Title, Subtitle, CTA Text
   - Arabic: Title, Subtitle, CTA Text
5. **Save banner:**
   - Click "Create Banner"
   - Verify banner created
6. **Test visual type switching:**
   - Create another banner
   - Select "Gradient" - verify uploader disappears
   - Select "Image" again - verify uploader reappears
7. **Test edit:**
   - Edit an existing banner
   - Replace image
   - Save changes
   - Verify image updated

**Expected Result:**
- ✅ Upload only shows when "Image" type selected
- ✅ Drag & drop works
- ✅ Click to upload works
- ✅ Preview displays
- ✅ Banner saves with image
- ✅ Switching visual types works correctly
- ✅ Edit and replace image works

---

### 🎯 Test 5: Hero Banners - Vue (Already Working)

**URL:** `http://localhost:3000/en/merchant/hero-banners/create`

**Steps:**
1. Login as merchant
2. Navigate to Hero Banners → Create
3. Select "Image" as visual type
4. **Test upload:**
   - Upload image (drag/click)
   - Verify progress bar shows
   - Verify preview displays
5. **Fill form and save**
6. **Test edit:**
   - Edit existing banner
   - Replace image
   - Save changes

**Expected Result:**
- ✅ Upload with progress bar works
- ✅ Preview displays
- ✅ Banner saves correctly
- ✅ Edit works

---

## Validation Testing

### Test Invalid File Types

**Steps:**
1. Try uploading a PDF file
2. Try uploading a TXT file
3. Try uploading a video file

**Expected Result:**
- ❌ Error: "Please upload a valid image file (JPEG, PNG, GIF, or WEBP)"

### Test File Size Limit

**Steps:**
1. Try uploading an image > 5MB

**Expected Result:**
- ❌ Error: "Image size must not exceed 5MB"

### Test Empty Upload

**Steps:**
1. Click save without uploading image (when image is required)

**Expected Result:**
- ❌ Form validation error
- (For brands, logo is optional so this should work)

---

## Backend API Testing

### Test Upload Endpoint

```bash
# Upload image to products context
curl -X POST "http://localhost:8000/api/v1/merchant/stores/1/media/upload" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "context=products" \
  -F "image=@/path/to/image.jpg"

# Expected Response:
# {
#   "status": true,
#   "data": {
#     "path": "products/abc123xyz.jpg",
#     "url": "/storage/products/abc123xyz.jpg",
#     "full_url": "http://localhost:8000/storage/products/abc123xyz.jpg"
#   },
#   "message": "Image uploaded successfully"
# }
```

### Test Delete Endpoint

```bash
# Delete image
curl -X DELETE "http://localhost:8000/api/v1/merchant/stores/1/media/delete" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "context": "products",
    "path": "products/abc123xyz.jpg"
  }'

# Expected Response:
# {
#   "status": true,
#   "message": "Image deleted successfully"
# }
```

### Test Invalid Context

```bash
# Try invalid context
curl -X POST "http://localhost:8000/api/v1/merchant/stores/1/media/upload" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "context=invalid_context" \
  -F "image=@/path/to/image.jpg"

# Expected Response:
# 422 Validation Error
```

---

## Storage Verification

### Check Uploaded Files

```bash
cd laratenant-backend

# List products images
ls -lah storage/app/public/products/

# List variants images
ls -lah storage/app/public/variants/

# List brands images
ls -lah storage/app/public/brands/

# List hero banner images
ls -lah storage/app/public/hero/
```

### Check Storage Symlink

```bash
cd laratenant-backend

# Verify symlink exists
ls -la public/storage

# Should show: public/storage -> ../storage/app/public
```

---

## Browser Console Checks

### No Errors Expected

**Open Developer Tools (F12) → Console**

During testing, you should see:
- ✅ No JavaScript errors
- ✅ No React warnings
- ✅ Network requests succeed (200 OK)
- ✅ No CORS errors

**If you see errors:**
- 404 on `/storage/...` → Storage symlink missing
- 500 errors → Check Laravel logs
- CORS errors → Check backend CORS config
- Network errors → Check backend is running

---

## Performance Checks

### Upload Speed

**Expected:**
- Small images (< 1MB): 1-2 seconds
- Medium images (1-3MB): 2-3 seconds
- Large images (3-5MB): 3-5 seconds

**If slow:**
- Check network connection
- Check server resources
- Check PHP upload limits

### Preview Loading

**Expected:**
- Preview should show immediately after upload
- No delay or flickering

**If slow:**
- Check image optimization
- Check storage disk I/O

---

## Troubleshooting

### Problem: "Upload failed"

**Check:**
1. Backend is running: `curl http://localhost:8000/api/health`
2. Storage directory writable: `chmod -R 775 storage/app/public`
3. PHP upload limits: Check `upload_max_filesize` in `php.ini`
4. Nginx limits: Check `client_max_body_size`

### Problem: "Image not displaying"

**Check:**
1. Storage symlink: `php artisan storage:link`
2. File exists: `ls storage/app/public/products/`
3. File permissions: `chmod 644 storage/app/public/products/*`
4. Browser console for 404 errors

### Problem: "CORS error"

**Check:**
1. Backend CORS config: `config/cors.php`
2. Allowed origins includes frontend URL
3. Allowed methods includes POST, DELETE

### Problem: "Validation error"

**Check:**
1. File type is image (JPEG, PNG, GIF, WEBP)
2. File size < 5MB
3. Context is valid (products, variants, brands, hero)

---

## Success Criteria

### ✅ All Tests Pass When:

**Products:**
- [x] Can upload product images
- [x] Can upload multiple images
- [x] Can reorder images
- [x] Can delete images
- [x] Images display on storefront

**Variants:**
- [x] Can upload variant images
- [x] Images display in variant modal
- [x] Can delete variant images

**Brands:**
- [x] Can upload brand logo
- [x] Can update brand logo
- [x] Can delete brand logo (optional)
- [x] Logo displays in brand list

**Hero Banners (React):**
- [x] Can upload hero banner image
- [x] Upload only shows for "image" visual type
- [x] Can update banner image
- [x] Can delete banner image
- [x] Banner displays on homepage

**Hero Banners (Vue):**
- [x] Can upload hero banner image
- [x] Progress bar shows during upload
- [x] Can update banner image
- [x] Banner displays on storefront

**Validation:**
- [x] Invalid file types rejected
- [x] Files > 5MB rejected
- [x] Clear error messages shown

**Backend:**
- [x] Upload API works
- [x] Delete API works
- [x] Files stored in correct directories
- [x] Unique filenames generated
- [x] Authentication required

---

## Quick Test Script

Copy and paste this into your terminal to run automated checks:

```bash
#!/bin/bash

echo "🧪 Testing Image Upload System..."
echo ""

# 1. Check backend
echo "1. Checking backend..."
curl -s http://localhost:8000/api/health > /dev/null && echo "✅ Backend running" || echo "❌ Backend not running"

# 2. Check storage symlink
echo "2. Checking storage symlink..."
[ -L "laratenant-backend/public/storage" ] && echo "✅ Storage symlink exists" || echo "❌ Storage symlink missing"

# 3. Check storage directories
echo "3. Checking storage directories..."
[ -d "laratenant-backend/storage/app/public/products" ] && echo "✅ Products directory exists" || echo "❌ Products directory missing"
[ -d "laratenant-backend/storage/app/public/variants" ] && echo "✅ Variants directory exists" || echo "❌ Variants directory missing"
[ -d "laratenant-backend/storage/app/public/brands" ] && echo "✅ Brands directory exists" || echo "❌ Brands directory missing"
[ -d "laratenant-backend/storage/app/public/hero" ] && echo "✅ Hero directory exists" || echo "❌ Hero directory missing"

# 4. Check React frontend
echo "4. Checking React frontend..."
curl -s http://localhost:4000 > /dev/null && echo "✅ React frontend running" || echo "❌ React frontend not running"

# 5. Check Vue frontend
echo "5. Checking Vue frontend..."
curl -s http://localhost:3000 > /dev/null && echo "✅ Vue frontend running" || echo "❌ Vue frontend not running"

echo ""
echo "✅ Pre-flight checks complete!"
echo ""
echo "Now test manually in browser:"
echo "  - Products: http://localhost:4000/en/merchant/products/new"
echo "  - Brands: http://localhost:4000/en/merchant/brands/new"
echo "  - Hero Banners (React): http://localhost:4000/en/merchant/hero-banners/new"
echo "  - Hero Banners (Vue): http://localhost:3000/en/merchant/hero-banners/create"
```

---

## Next Steps After Testing

1. ✅ If all tests pass → Deploy to staging
2. ✅ Monitor for 24 hours
3. ✅ Deploy to production
4. ✅ Update user documentation
5. ✅ Train merchant users

---

## Summary

**Forms to Test:** 6
1. ✅ Products - ProductImagesManager
2. ✅ Variants - VariantMediaDialog
3. ✅ Brands - Create & Edit forms
4. ✅ Hero Banners (React) - Create & Edit forms **← NEWLY UPDATED**
5. ✅ Hero Banners (Vue) - Already working

**Time Required:** ~30 minutes for complete testing
**Recommended Order:** Products → Brands → Hero Banners → Variants

---

**Happy Testing! 🚀**
