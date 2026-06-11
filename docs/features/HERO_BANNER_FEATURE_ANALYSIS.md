# Hero Banner Feature Analysis

## Question
Can merchants CRUD hero banners for their stores in this project?

## Answer: ❌ NO - NOT IMPLEMENTED YET

Hero banners exist in the backend but there's **NO merchant dashboard UI** or **merchant API endpoints** for CRUD operations.

---

## What Exists ✅

### 1. Database Schema ✅
Hero banners have a complete database structure:

**Tables:**
- `hero_banners` - Main banner table with store_id
- `hero_banner_translations` - Multi-language support

**Migrations:**
- `2026_01_20_184758_create_hero_banners_table.php`
- `2026_02_14_162414_create_hero_banner_translations_table.php`
- `2026_04_30_000001_add_store_id_to_hero_banners_table.php`

### 2. Models ✅
- `App\Models\HeroBanner` - Main model with soft deletes
- `App\Models\HeroBannerTranslation` - Translation model
- Relationships properly defined

### 3. Enums ✅
- `HeroVisualTypeEnum` - image, gradient, video
- `HeroLinkTargetEnum` - _self, _blank

### 4. Seeders ✅
- `HeroBannerSeeder` - Creates sample hero banners for stores
- Currently seeded with 4 sample banners

### 5. Storefront API ✅
**Read-only endpoint for customers:**

```
GET /api/v1/storefront/stores/{store}/homepage/hero
```

Controller: `HomePageController::hero()`
- Returns active hero banners for a store
- Used by storefront to display banners
- Public endpoint (no auth required)

### 6. DTOs & Resources ✅
- `GetHeroBannersDTO`
- `HeroBannerDTO`
- `HeroBannerResource`
- `GetHeroBannersRequest`

### 7. Services ✅
- `HomePageService::getHeroBanners()` - Fetches active banners
- `StorefrontRuntimeService` - Includes hero banners in runtime payload

---

## What's Missing ❌

### 1. Merchant API Endpoints ❌
No CRUD endpoints for merchants to manage their hero banners:

**Missing routes:**
```php
// These DON'T exist:
POST   /api/v1/merchant/stores/{store}/hero-banners        // Create
GET    /api/v1/merchant/stores/{store}/hero-banners        // List
GET    /api/v1/merchant/stores/{store}/hero-banners/{id}   // Show
PUT    /api/v1/merchant/stores/{store}/hero-banners/{id}   // Update
DELETE /api/v1/merchant/stores/{store}/hero-banners/{id}   // Delete
```

### 2. Merchant Controller ❌
No controller exists for merchant hero banner management:

**Missing:**
- `App\Http\Controllers\Api\Merchant\AdminHeroBannerController` ❌

**Expected location:**
```
laratenant-backend/app/Http/Controllers/Api/Merchant/
├── AdminBrandController.php ✅
├── AdminCategoryController.php ✅
├── AdminProductController.php ✅
├── AdminTagController.php ✅
└── AdminHeroBannerController.php ❌ MISSING!
```

### 3. Merchant Dashboard UI ❌
No pages exist in the Next.js merchant dashboard:

**Missing pages:**
```
laratenant-commerce/src/app/[locale]/(merchant)/merchant/
├── brands/ ✅
├── categories/ ✅
├── products/ ✅
├── tags/ ✅
└── hero-banners/ ❌ MISSING!
    ├── page.tsx (list)
    ├── new/page.tsx (create)
    └── [id]/
        └── page.tsx (edit)
```

**Also missing store-scoped routes:**
```
laratenant-commerce/src/app/[locale]/(dashboard)/stores/[storeId]/
├── brands/ ✅
├── categories/ ✅
├── products/ ✅
├── tags/ ✅
└── hero-banners/ ❌ MISSING!
```

### 4. Form Components ❌
No React components for hero banner forms:

**Missing:**
```
src/features/dashboard/hero-banners/
├── CreateHeroBannerForm.tsx ❌
├── EditHeroBannerForm.tsx ❌
└── HeroBannerList.tsx ❌
```

### 5. API Client Functions ❌
No client-side API functions:

**Missing in:** `src/lib/api/`
```typescript
// hero-banners.ts - MISSING
export async function getHeroBanners(storeId: string) { }
export async function createHeroBanner(storeId: string, data: any) { }
export async function updateHeroBanner(storeId: string, id: string, data: any) { }
export async function deleteHeroBanner(storeId: string, id: string) { }
```

### 6. Route Config ❌
Hero banner routes not defined in route config:

**Missing in:** `laratenant-commerce/src/config/routes.ts`
```typescript
store: (storeId: string) => ({
  // ... existing routes
  heroBanners: {  // ❌ MISSING!
    list: () => `/stores/${storeId}/hero-banners`,
    new: () => `/stores/${storeId}/hero-banners/new`,
    edit: (id: string) => `/stores/${storeId}/hero-banners/${id}`,
  }
})
```

---

## Current Status

### For Merchants: ❌
- **Cannot create** hero banners
- **Cannot edit** hero banners
- **Cannot delete** hero banners
- **Cannot view** their hero banners in dashboard
- **Must rely on** developers/admins to seed data

### For Customers: ✅
- **Can see** hero banners on storefront
- Banners load via storefront API
- Properly displayed in hero sections

### For Developers: ⚠️
- Can seed hero banners via `HeroBannerSeeder`
- Can manually create via Tinker/SQL
- Database structure is ready
- Models and relationships work

---

## How Merchants Currently Manage Hero Banners

**Answer:** They can't! ❌

The only way to add/edit hero banners is:

1. **Database Seeder** (development only)
2. **Manual SQL** (not practical)
3. **Laravel Tinker** (technical users only)
4. **Custom admin tool** (doesn't exist)

---

## What Needs to Be Built

To enable merchant CRUD for hero banners, you need:

### Backend (Laravel)

1. **Controller:**
   ```php
   // app/Http/Controllers/Api/Merchant/AdminHeroBannerController.php
   public function index(int $storeId)
   public function store(StoreHeroBannerRequest $request, int $storeId)
   public function show(int $storeId, int $id)
   public function update(UpdateHeroBannerRequest $request, int $storeId, int $id)
   public function destroy(int $storeId, int $id)
   ```

2. **Requests:**
   ```php
   // app/Http/Requests/HeroBanner/StoreHeroBannerRequest.php
   // app/Http/Requests/HeroBanner/UpdateHeroBannerRequest.php
   ```

3. **Routes:**
   ```php
   // routes/api/v1/merchant/stores.php
   Route::apiResource('hero-banners', AdminHeroBannerController::class);
   ```

4. **Authorization:**
   - Policy: `HeroBannerPolicy`
   - Ensure merchants can only manage their own store's banners

### Frontend (Next.js)

1. **Pages:**
   - List: `/merchant/hero-banners/page.tsx`
   - Create: `/merchant/hero-banners/new/page.tsx`
   - Edit: `/merchant/hero-banners/[id]/page.tsx`

2. **Forms:**
   - `CreateHeroBannerForm.tsx`
   - `EditHeroBannerForm.tsx`
   - Image upload support
   - Translation fields (title, subtitle, CTA)

3. **API Client:**
   - `src/lib/api/hero-banners.ts`

4. **Route Config:**
   - Add to `ROUTES.ts` and `API_ROUTES.ts`

### Features to Include

- **Image Upload** - Upload banner images
- **Multi-language** - Title, subtitle, CTA per locale
- **Visual Types** - Image, gradient, video
- **Link Configuration** - URL and target (_self, _blank)
- **Position/Order** - Control banner display order
- **Active/Inactive** - Toggle banner visibility
- **Schedule** - Optional start/end dates
- **Preview** - See how banner looks before saving

---

## Comparison with Other Features

| Feature | Database | Model | API | Dashboard | Status |
|---------|----------|-------|-----|-----------|--------|
| Products | ✅ | ✅ | ✅ | ✅ | **Complete** |
| Categories | ✅ | ✅ | ✅ | ✅ | **Complete** |
| Brands | ✅ | ✅ | ✅ | ✅ | **Complete** |
| Tags | ✅ | ✅ | ✅ | ✅ | **Complete** |
| **Hero Banners** | ✅ | ✅ | ❌ | ❌ | **Incomplete** |

Hero banners are about **50% complete** - backend models exist but merchant interface doesn't.

---

## Workaround (Until Feature is Built)

If merchants need custom hero banners now:

### Option 1: Seeder Approach
1. Update `HeroBannerSeeder.php` with merchant's data
2. Run `php artisan db:seed --class=HeroBannerSeeder`
3. Requires developer intervention

### Option 2: Tinker Approach
```php
php artisan tinker

use App\Models\HeroBanner;

$banner = HeroBanner::create([
    'store_id' => 1,
    'cat_url' => '/shop',
    'position' => 0,
    'visual_type' => 'image',
    'image_path' => 'hero/my-banner.jpg',
    'is_active' => true,
]);

$banner->translations()->create([
    'locale' => 'en',
    'title' => 'My Banner',
    'subtitle' => 'Shop now!',
    'cta_text' => 'Browse',
]);
```

### Option 3: Direct Database
- Use phpMyAdmin or similar
- Insert into `hero_banners` and `hero_banner_translations` tables

---

## Recommendation

### Priority: HIGH 🔴

Hero banners are a **critical storefront feature** that merchants typically want to control themselves. This should be prioritized alongside products, categories, and other core features.

### Estimated Effort

- **Backend API:** 4-6 hours
- **Frontend UI:** 8-12 hours
- **Image Upload:** 2-4 hours
- **Testing:** 2-4 hours
- **Total:** ~2-3 days for one developer

### Similar Features to Reference

Since brands and tags CRUD are complete, you can:
1. Copy `AdminBrandController` → `AdminHeroBannerController`
2. Copy `brands/` pages → `hero-banners/` pages
3. Adapt for hero banner specific fields (image, translations, etc.)

---

## Summary

**Can merchants CRUD hero banners?**
- ❌ **NO** - Feature is not implemented
- ✅ Database and models ready
- ✅ Storefront display works
- ❌ No merchant API or UI
- ⚠️ Currently requires developer intervention

**Next Steps:**
1. Build merchant API endpoints (Backend)
2. Create dashboard UI pages (Frontend)
3. Add image upload functionality
4. Test end-to-end
5. Deploy to production

Would you like me to create the implementation plan or start building this feature?
