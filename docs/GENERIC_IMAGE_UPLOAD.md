# Generic Image Upload System

## Overview

This document describes the unified image upload system that replaces URL-based image inputs across all features in the platform.

## Architecture

The system follows the project's strict architecture rules:
- ✅ Domain-first structure
- ✅ DTOs for all data transfer
- ✅ Actions for business logic
- ✅ Store-scoped with `{store}` parameter
- ✅ Thin controllers
- ✅ Centralized API responses

## Components

### Backend

```
app/
├── Actions/Admin/Media/
│   ├── UploadImageAction.php
│   └── DeleteImageAction.php
├── DTOs/Admin/Media/
│   ├── UploadImageDTO.php
│   └── DeleteImageDTO.php
├── Http/Controllers/Api/Merchant/
│   └── AdminMediaController.php
├── Http/Requests/Admin/Media/
│   ├── UploadImageRequest.php
│   └── DeleteImageRequest.php
├── Enums/
│   └── MediaContextEnum.php
└── Exceptions/Media/
    ├── InvalidMediaContextException.php
    ├── MediaUploadFailedException.php
    └── InvalidMediaPathException.php
```

### Frontend

```
app/
├── components/merchant/shared/
│   └── GenericImageUploader.vue
└── utils/api/
    └── media.ts
```

## Usage

### Backend API Endpoints

#### Upload Image
```http
POST /api/v1/merchant/stores/{store}/media/upload

Content-Type: multipart/form-data

Fields:
  - context: string (products|variants|brands|categories|hero|tags|stores)
  - image: file (jpeg|jpg|png|gif|webp, max 5MB)

Response:
{
  "status": true,
  "message": "Image uploaded successfully",
  "data": {
    "path": "products/abc123xyz.jpg",
    "url": "/storage/products/abc123xyz.jpg",
    "full_url": "http://localhost:8000/storage/products/abc123xyz.jpg"
  }
}
```

#### Delete Image
```http
DELETE /api/v1/merchant/stores/{store}/media/delete

Content-Type: application/json

Body:
{
  "context": "products",
  "path": "products/abc123xyz.jpg"
}

Response:
{
  "status": true,
  "message": "Image deleted successfully",
  "data": null
}
```

### Frontend Component Usage

#### Basic Usage

```vue
<template>
  <GenericImageUploader
    v-model="formData.image_path"
    label="Product Image"
    :store-id="storeId"
    context="products"
  />
</template>

<script setup lang="ts">
import GenericImageUploader from '~/components/merchant/shared/GenericImageUploader.vue'

const formData = ref({
  image_path: ''
})
</script>
```

#### Available Contexts

| Context | Storage Path | Use Case |
|---------|-------------|----------|
| `products` | `storage/app/public/products/` | Product images |
| `variants` | `storage/app/public/variants/` | Product variant images |
| `brands` | `storage/app/public/brands/` | Brand logos |
| `categories` | `storage/app/public/categories/` | Category icons/images |
| `hero` | `storage/app/public/hero/` | Hero banner images |
| `tags` | `storage/app/public/tags/` | Tag icons |
| `stores` | `storage/app/public/stores/` | Store logos/favicons |

## Features

### Backend

✅ **Context-based storage**: Each entity type has its own directory
✅ **Security validation**: Path validation ensures files are in correct context
✅ **Unique filenames**: 20-character random string prevents collisions
✅ **File validation**: Type (jpeg/jpg/png/gif/webp) and size (5MB max)
✅ **Store-scoped**: All operations require valid store context
✅ **Localization**: All messages support multiple languages
✅ **Error handling**: Custom exceptions with proper error codes

### Frontend

✅ **Drag & drop**: Modern file upload UX
✅ **Click to upload**: Traditional file picker
✅ **Progress indicator**: Visual feedback during upload
✅ **Image preview**: Shows uploaded image with delete option
✅ **Client-side validation**: Instant feedback before upload
✅ **Error messages**: User-friendly error handling
✅ **URL support**: Backward compatible with external URLs

## Security

1. **Context validation**: Files can only be deleted from their original context
2. **File type validation**: Only image files allowed
3. **Size limits**: Maximum 5MB per image
4. **Path validation**: Prevents directory traversal attacks
5. **Store scoping**: Users can only upload to their own stores

## Migration Guide

### Replacing URL Inputs with File Upload

#### Before (URL Input)
```vue
<template>
  <div>
    <label>Image URL</label>
    <input
      v-model="formData.logo_url"
      type="url"
      placeholder="https://example.com/logo.jpg"
    />
  </div>
</template>
```

#### After (File Upload)
```vue
<template>
  <GenericImageUploader
    v-model="formData.logo_path"
    label="Brand Logo"
    :store-id="storeId"
    context="brands"
  />
</template>

<script setup lang="ts">
import GenericImageUploader from '~/components/merchant/shared/GenericImageUploader.vue'
</script>
```

### Backend Migration

#### Update Request Validation

Before:
```php
'logo_url' => ['sometimes', 'nullable', 'string', 'url', 'max:2048'],
```

After:
```php
'logo_path' => ['sometimes', 'nullable', 'string', 'max:500'],
```

#### Update Model

The `Image` model already supports both paths and URLs via `getFullUrlAttribute()`:

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

This means:
- ✅ Old URL-based records continue to work
- ✅ New file-based uploads work automatically
- ✅ No database migration needed

## Adding New Contexts

To add a new image context (e.g., for a new feature):

1. **Add to MediaContextEnum**:
```php
// app/Enums/MediaContextEnum.php
case NEW_FEATURE = 'new-feature';
```

2. **Create storage directory**:
```bash
mkdir storage/app/public/new-feature
```

3. **Use in frontend**:
```vue
<GenericImageUploader
  v-model="formData.image_path"
  context="new-feature"
  :store-id="storeId"
/>
```

That's it! The system handles everything else automatically.

## Benefits

### For Merchants
- ✅ Professional drag & drop interface
- ✅ Instant visual feedback
- ✅ No need for external image hosting
- ✅ Consistent UX across all features

### For Developers
- ✅ Reusable component for all features
- ✅ Follows project architecture rules
- ✅ Type-safe with TypeScript/PHP enums
- ✅ Easy to extend and maintain

### For the Platform
- ✅ Industry-standard approach (Shopify, WooCommerce, etc.)
- ✅ Better control over image storage
- ✅ Automatic optimization possible in future
- ✅ CDN integration ready

## Future Enhancements

Possible improvements (not yet implemented):

- [ ] Image cropping/resizing before upload
- [ ] Multiple image upload (bulk)
- [ ] Image optimization (auto WebP conversion)
- [ ] CDN integration (Cloudinary, S3, etc.)
- [ ] Progress tracking for large files
- [ ] Thumbnail generation
- [ ] Alt text editor

## Related Files

### Backend
- `app/Enums/MediaContextEnum.php` - Context definitions
- `app/Actions/Admin/Media/` - Business logic
- `app/DTOs/Admin/Media/` - Data transfer objects
- `app/Http/Controllers/Api/Merchant/AdminMediaController.php` - API endpoints
- `app/Exceptions/Media/` - Custom exceptions
- `routes/api/v1/merchant/admin.php` - Route definitions
- `lang/en/media.php`, `lang/ar/media.php` - Localization

### Frontend
- `components/merchant/shared/GenericImageUploader.vue` - Main component
- `utils/api/media.ts` - API client functions

## Testing

### Manual Testing

1. **Upload Test**:
   - Navigate to any form with image upload
   - Drag and drop an image
   - Verify preview appears
   - Verify image is saved

2. **Delete Test**:
   - Click delete button on uploaded image
   - Confirm deletion
   - Verify image is removed

3. **Validation Test**:
   - Try uploading invalid file type (.pdf, .txt)
   - Try uploading file > 5MB
   - Verify error messages appear

4. **Context Test**:
   - Upload to different contexts (products, brands, etc.)
   - Verify files are stored in correct directories

### Automated Testing

See `tests/Feature/Media/` for feature tests covering:
- Upload validation
- Context validation
- Path security
- Store scoping
- File deletion

## Support

For issues or questions, contact the development team or refer to the main ARCHITECTURE.md document.
