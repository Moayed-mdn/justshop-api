# CMS Architecture - Final Documentation

## ✅ CORRECT ARCHITECTURE (Confirmed)

### 1. Platform-Level CMS (Global Marketing Site)

#### Backend Management (platform-dashboard at localhost:3001)
- **Admin creates/edits/deletes**: `/api/v1/platform/cms/pages`
- **Controller**: `AdminPlatformMarketingPageController`
- **Table**: `platform_marketing_pages` (10 pages migrated ✅)
- **Authorization**: Permission checks (`MARKETING_PLATFORM_*`)
- **Middleware**: `platform.context` (sets team_id=0)

#### Public Display (laratenant-commerce at localhost:3000)
- **Customers view**: `/api/v1/public/cms/pages/{slug}`
- **Controller**: `PublicMarketingController`
- **Data Source**: `platform_marketing_pages` (NEW) with fallback to `marketing_pages` (LEGACY)
- **No authentication required**

**Flow:**
```
Super Admin → platform-dashboard
    ↓ manages via
/api/v1/platform/cms/pages (CRUD)
    ↓ writes to
platform_marketing_pages table
    ↓ read by
/api/v1/public/cms/pages/{slug} (READ-ONLY)
    ↓ displayed to
Customers viewing laratenant-commerce marketing site
```

**Pages (all in platform_marketing_pages):**
1. home
2. about
3. contact
4. features
5. enterprise
6. pricing
7. demo
8. templates
9. blog (index page)
10. docs (documentation home)

---

### 2. Store-Level CMS (Per-Tenant Stores)

#### Merchant Management (laratenant-commerce merchant dashboard)
- **Merchants create/edit/delete**: `/api/v1/merchant/stores/{store}/cms/pages`
- **Controller**: `AdminStoreMarketingPageController`
- **Table**: `store_marketing_pages` (HAS store_id column)
- **Authorization**: Store-scoped policies (`MARKETING_STORE_*`)
- **Middleware**: `store.context` (sets team_id=store_id)

**Flow:**
```
Merchant → laratenant-commerce merchant dashboard
    ↓ manages via
/api/v1/merchant/stores/{store}/cms/pages (CRUD)
    ↓ writes to
store_marketing_pages table (scoped by store_id)
    ↓ read by
Storefront (public routes per store)
    ↓ displayed to
Customers viewing that specific store
```

---

### 3. Legacy System (Deprecated)

**`marketing_pages` table:**
- Status: ✅ **MIGRATED** to `platform_marketing_pages`
- Records: 10 (can now be archived/deleted)
- Still used as fallback in `PublicMarketingController` for safety
- Recommendation: Keep for 1-2 months then drop

---

## 📊 Complete Data Status

```sql
-- Platform CMS (Global)
platform_marketing_pages: 10 records ✅ (migrated from legacy)
  ├── home, about, contact, features
  ├── enterprise, pricing, demo, templates
  └── blog, docs

-- Store CMS (Per Tenant)
store_marketing_pages: 5 records ✅ (store-specific)

-- Legacy (Archived)
marketing_pages: 10 records ⚠️ (can be dropped after verification)

-- Platform Content (Other)
blog_posts: 8 records ✅ (platform blog articles)
cms_documents: 12 records ✅ (help documentation)
```

---

## 🔑 Key Routes

### Platform CMS Management (Admin)
```
GET    /api/v1/platform/cms/pages          # List all pages
POST   /api/v1/platform/cms/pages          # Create page
GET    /api/v1/platform/cms/pages/{id}     # Get page
PUT    /api/v1/platform/cms/pages/{id}     # Update page
DELETE /api/v1/platform/cms/pages/{id}     # Delete page
POST   /api/v1/platform/cms/pages/{id}/publish   # Publish page
POST   /api/v1/platform/cms/pages/{id}/unpublish # Unpublish page
```

### Public CMS Display (Customers)
```
GET    /api/v1/public/cms/pages/{slug}     # View published page
GET    /api/v1/public/cms/blog             # List blog posts
GET    /api/v1/public/cms/blog/{slug}      # View blog post
GET    /api/v1/public/cms/docs/{path}      # View documentation
```

### Store CMS Management (Merchants)
```
GET    /api/v1/merchant/stores/{store}/cms/pages          # List store pages
POST   /api/v1/merchant/stores/{store}/cms/pages          # Create store page
GET    /api/v1/merchant/stores/{store}/cms/pages/{id}     # Get store page
PUT    /api/v1/merchant/stores/{store}/cms/pages/{id}     # Update store page
DELETE /api/v1/merchant/stores/{store}/cms/pages/{id}     # Delete store page
```

---

## 🎯 Authorization Patterns

### Platform CMS (Permission-Based)
```php
// CORRECT ✅
if (!auth()->user()?->can(PermissionEnum::MARKETING_PLATFORM_VIEW)) {
    abort(403);
}
```

**Required Middleware:**
- `platform.context` - Sets `team_id = 0` for Spatie Permission
- `platform.authority:platform_admin` - Verifies platform authority

### Store CMS (Policy-Based)
```php
// CORRECT ✅
$this->authorize('viewAny', StoreMarketingPage::class);
```

**Required Middleware:**
- `store.context` - Sets `team_id = store_id` for Spatie Permission
- Automatically checks store ownership

---

## 📝 Page Types (MarketingPageTypeEnum)

Valid template types for marketing pages:
- `home` - Homepage
- `about` - About Us
- `contact` - Contact page
- `features` - Features listing
- `enterprise` - Enterprise solutions
- `pricing` - Pricing plans
- `demo` - Product demo
- `templates` - Template showcase
- `blog` - Blog index (not individual posts)
- `documentation` - Docs home (not individual docs)

---

## ✅ Migration Completed

**What was done:**
1. ✅ Created `PlatformContext` middleware
2. ✅ Fixed authorization in platform CMS controllers
3. ✅ Migrated 10 pages from `marketing_pages` → `platform_marketing_pages`
4. ✅ Added fallback support in `PublicMarketingController`
5. ✅ Verified all routes are working

**Current Status:**
- Platform dashboard can create/edit platform CMS ✅
- Commerce frontend can view platform CMS pages ✅
- Legacy data is preserved but deprecated ✅
- Store CMS continues to work independently ✅

---

## 🚀 Testing

### Test Platform CMS Management
```bash
# Login to platform-dashboard (localhost:3001)
# Navigate to /en/cms
# Should see 10 marketing pages, 8 blog posts, 12 docs
```

### Test Public Display
```bash
# Visit laratenant-commerce (localhost:3000)
# Navigate to /pricing, /features, /about, etc.
# Should display content from platform_marketing_pages
```

### Verify Data
```bash
php artisan tinker --execute="
echo 'Platform Marketing Pages: ' . DB::table('platform_marketing_pages')->count() . PHP_EOL;
echo 'Store Marketing Pages: ' . DB::table('store_marketing_pages')->count() . PHP_EOL;
"
```

---

## 📌 Important Notes

1. **Two Separate Systems**: Platform CMS and Store CMS are completely independent
2. **No /platform suffix**: Platform-dashboard uses `/api/v1/platform/cms/pages` (no suffix)
3. **Public routes**: Commerce frontend uses `/api/v1/public/cms/pages/{slug}` for display
4. **Legacy fallback**: `PublicMarketingController` tries new table first, falls back to legacy
5. **Migration complete**: All 10 marketing pages now in `platform_marketing_pages`

---

## 🎉 Summary

The CMS architecture is now correctly implemented with clear separation:

- **Platform CMS** = Marketing site content (managed by super admins)
- **Store CMS** = Tenant store content (managed by merchants)
- **Legacy system** = Migrated and ready to be archived

All data has been migrated and both frontends can now work correctly!
