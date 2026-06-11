# Generic Image Upload - Quick Start Guide

## 🚀 Ready to Use!

The generic image upload system is now live and ready to use for any feature that needs images.

---

## 📍 API Endpoints

### Upload
```
POST /api/v1/merchant/stores/{store}/media/upload
```

### Delete
```
DELETE /api/v1/merchant/stores/{store}/media/delete
```

---

## 🎯 How to Use in Your Feature

### Step 1: Import the Component

```vue
<script setup lang="ts">
import GenericImageUploader from '~/components/merchant/shared/GenericImageUploader.vue'
</script>
```

### Step 2: Add to Your Template

```vue
<template>
  <form>
    <!-- Your other form fields -->
    
    <GenericImageUploader
      v-model="formData.image_path"
      label="Upload Image"
      :store-id="storeId"
      context="products"
    />
    
    <!-- More form fields -->
  </form>
</template>
```

### Step 3: Set the Context

Choose the appropriate context for your feature:

| Feature | Context | Storage Path |
|---------|---------|--------------|
| Products | `products` | `storage/app/public/products/` |
| Product Variants | `variants` | `storage/app/public/variants/` |
| Brands | `brands` | `storage/app/public/brands/` |
| Categories | `categories` | `storage/app/public/categories/` |
| Hero Banners | `hero` | `storage/app/public/hero/` |
| Tags | `tags` | `storage/app/public/tags/` |
| Stores | `stores` | `storage/app/public/stores/` |

---

## ✅ Example: Product Form

```vue
<template>
  <form @submit.prevent="handleSubmit">
    <div class="space-y-4">
      <!-- Product Name -->
      <div>
        <label>Product Name</label>
        <input v-model="formData.name" type="text" required />
      </div>

      <!-- Product Image -->
      <GenericImageUploader
        v-model="formData.image_path"
        label="Product Image"
        :store-id="storeId"
        context="products"
      />

      <!-- Price -->
      <div>
        <label>Price</label>
        <input v-model="formData.price" type="number" required />
      </div>

      <button type="submit">Create Product</button>
    </div>
  </form>
</template>

<script setup lang="ts">
import GenericImageUploader from '~/components/merchant/shared/GenericImageUploader.vue'

const props = defineProps<{
  storeId: number
}>()

const formData = ref({
  name: '',
  image_path: '',  // This will hold: "products/abc123xyz.jpg"
  price: 0
})

const handleSubmit = async () => {
  // formData.image_path now contains the uploaded image path
  console.log('Image path:', formData.image_path)
  // → "products/abc123xyz.jpg"
  
  // Send to your API...
}
</script>
```

---

## 🎨 Features You Get

✅ **Drag & Drop** - Modern file upload UX
✅ **Click to Upload** - Traditional file picker
✅ **Progress Bar** - Visual feedback during upload
✅ **Image Preview** - Shows uploaded image
✅ **Delete Button** - Easy image removal
✅ **Validation** - Client-side file type & size checks
✅ **Error Messages** - User-friendly error handling
✅ **Responsive** - Works on all screen sizes

---

## 🔧 Backend: No Changes Needed!

The backend is already set up. The `Image` model handles both:
- ✅ Old URL-based images (`https://example.com/image.jpg`)
- ✅ New file-based uploads (`products/abc123xyz.jpg`)

So you don't need to:
- ❌ Create migrations
- ❌ Update models
- ❌ Write upload controllers
- ❌ Add routes

**Everything just works!**

---

## 📦 What Gets Stored

When a user uploads an image:

1. **File is saved**: `storage/app/public/products/abc123xyz.jpg`
2. **Database stores**: `products/abc123xyz.jpg`
3. **Frontend displays**: `http://localhost:8000/storage/products/abc123xyz.jpg`

The component handles everything automatically!

---

## 🔒 Security Built-In

- ✅ Only image files accepted (jpeg, jpg, png, gif, webp)
- ✅ 5MB maximum file size
- ✅ Unique random filenames (no conflicts)
- ✅ Context validation (can't delete from wrong context)
- ✅ Store-scoped (users can only upload to their stores)
- ✅ Path validation (prevents directory traversal)

---

## 🌍 Localization

All messages are already localized in English and Arabic:
- Upload success/failure
- Validation errors
- Delete confirmation
- File type errors
- Size limit errors

---

## 🎯 Migration from URL Input

### Old Way (URL Input):
```vue
<input
  v-model="formData.logo_url"
  type="url"
  placeholder="https://example.com/logo.jpg"
/>
```

### New Way (File Upload):
```vue
<GenericImageUploader
  v-model="formData.logo_path"
  :store-id="storeId"
  context="brands"
/>
```

**That's it!** Just replace the input with the component.

---

## 💡 Tips

1. **Use descriptive labels**:
   ```vue
   <GenericImageUploader
     label="Brand Logo (max 5MB)"
     ...
   />
   ```

2. **Handle empty state**:
   ```vue
   const formData = ref({
     image_path: '' // Empty string, not null
   })
   ```

3. **The path is what you save**:
   ```vue
   // Component gives you: "products/abc123xyz.jpg"
   // That's what you send to your API
   // The backend will generate the full URL when needed
   ```

---

## 🐛 Troubleshooting

### Image not uploading?
- Check console for errors
- Verify file is < 5MB
- Verify file is an image (jpeg, jpg, png, gif, webp)
- Check network tab for API errors

### Preview not showing?
- Verify `modelValue` contains a valid path
- Check browser console for errors
- Verify storage symlink exists: `php artisan storage:link`

### Delete not working?
- Verify the path matches the context
- Check browser console for errors
- Verify user has permission to delete

---

## 📚 Full Documentation

For complete technical details, see:
- `/docs/GENERIC_IMAGE_UPLOAD.md` - Technical documentation
- `/GENERIC_IMAGE_UPLOAD_IMPLEMENTATION.md` - Implementation details

---

## ✨ You're Done!

That's all you need to know. The component handles everything else automatically.

Happy coding! 🎉
