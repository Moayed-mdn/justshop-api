# Storefront Theme System - Session-Based Implementation Plan

## 📋 Overview

This document breaks down the theme system implementation into **12 independent sessions**. Each session has clear inputs, outputs, and deliverables. Sessions are designed to be run sequentially without confusion.

**Estimated Timeline**: 12 sessions × 2-4 hours = 24-48 hours total work

---

## 🎯 Session Execution Guide

### How to Run a Session

Simply say to Cursor:
```
Hi, run SESSION X from THEME_SYSTEM_SESSION_PLAN.md
```

Each session is **self-contained** with:
- ✅ Clear objectives
- ✅ Specific files to create/modify
- ✅ Verification steps
- ✅ Exit criteria (what "done" looks like)

---

## 📦 Session Breakdown

### **SESSION 1: Core Theme Database Schema**
**Duration**: 2-3 hours  
**Dependencies**: None  
**Focus**: Create all theme-related database tables

#### Objectives
1. Create migration: `themes` table
2. Create migration: `theme_sections` table
3. Create migration: `theme_blocks` table
4. Create migration: `theme_templates` table
5. Create migration: `theme_template_sections` pivot table

#### Deliverables
**Files to Create**:
- `laratenant-backend/database/migrations/2026_06_06_000001_create_themes_table.php`
- `laratenant-backend/database/migrations/2026_06_06_000002_create_theme_sections_table.php`
- `laratenant-backend/database/migrations/2026_06_06_000003_create_theme_blocks_table.php`
- `laratenant-backend/database/migrations/2026_06_06_000004_create_theme_templates_table.php`
- `laratenant-backend/database/migrations/2026_06_06_000005_create_theme_template_sections_table.php`

#### Verification
```bash
cd laratenant-backend
php artisan migrate:status
# All 5 new migrations should appear
```

#### Exit Criteria
- ✅ 5 migration files created
- ✅ All migrations follow Laravel conventions
- ✅ Foreign keys properly defined
- ✅ JSON columns for settings
- ✅ Timestamps and soft deletes included

---

### **SESSION 2: Navigation & Asset Database Schema**
**Duration**: 2 hours  
**Dependencies**: None (can run in parallel with Session 1)  
**Focus**: Create navigation menu and asset storage tables

#### Objectives
1. Create migration: `navigation_menus` table
2. Create migration: `navigation_menu_items` table
3. Create migration: `store_assets` table
4. Add `logo_url`, `favicon_url` columns to `stores` table

#### Deliverables
**Files to Create**:
- `laratenant-backend/database/migrations/2026_06_06_000006_create_navigation_menus_table.php`
- `laratenant-backend/database/migrations/2026_06_06_000007_create_navigation_menu_items_table.php`
- `laratenant-backend/database/migrations/2026_06_06_000008_create_store_assets_table.php`
- `laratenant-backend/database/migrations/2026_06_06_000009_add_theme_fields_to_stores_table.php`

#### Verification
```bash
cd laratenant-backend
php artisan migrate:status
# All 4 new migrations should appear
```

#### Exit Criteria
- ✅ 4 migration files created
- ✅ Self-referencing foreign key on `navigation_menu_items.parent_id`
- ✅ Store table has `logo_url`, `favicon_url`, `active_theme_id` columns
- ✅ Asset types enum properly defined

---

### **SESSION 3: Theme Enums**
**Duration**: 1 hour  
**Dependencies**: None  
**Focus**: Create all theme-related enums

#### Objectives
1. Create `SectionTypeEnum`
2. Create `BlockTypeEnum`
3. Create `TemplateTypeEnum`
4. Create `AssetTypeEnum`

#### Deliverables
**Files to Create**:
- `laratenant-backend/app/Enums/Theme/SectionTypeEnum.php`
- `laratenant-backend/app/Enums/Theme/BlockTypeEnum.php`
- `laratenant-backend/app/Enums/Theme/TemplateTypeEnum.php`
- `laratenant-backend/app/Enums/Theme/AssetTypeEnum.php`

#### Verification
```bash
cd laratenant-backend
php artisan tinker
# Test: use App\Enums\Theme\SectionTypeEnum;
# Test: SectionTypeEnum::cases();
```

#### Exit Criteria
- ✅ 4 enum files created in `app/Enums/Theme/` directory
- ✅ Each enum has `values()` method
- ✅ Each enum has `label()` method
- ✅ Each enum has `options()` method for API responses
- ✅ All enum values match the plan specification

---

### **SESSION 4: Theme Models & Relationships**
**Duration**: 2-3 hours  
**Dependencies**: SESSION 1, SESSION 2, SESSION 3  
**Focus**: Create Eloquent models with relationships

#### Objectives
1. Create `Theme` model
2. Create `ThemeSection` model
3. Create `ThemeBlock` model
4. Create `ThemeTemplate` model
5. Create `NavigationMenu` model
6. Create `NavigationMenuItem` model
7. Create `StoreAsset` model
8. Update `Store` model with theme relationships

#### Deliverables
**Files to Create**:
- `laratenant-backend/app/Models/Theme/Theme.php`
- `laratenant-backend/app/Models/Theme/ThemeSection.php`
- `laratenant-backend/app/Models/Theme/ThemeBlock.php`
- `laratenant-backend/app/Models/Theme/ThemeTemplate.php`
- `laratenant-backend/app/Models/Navigation/NavigationMenu.php`
- `laratenant-backend/app/Models/Navigation/NavigationMenuItem.php`
- `laratenant-backend/app/Models/Asset/StoreAsset.php`

**Files to Modify**:
- `laratenant-backend/app/Models/Store.php` (add theme relationships)

#### Verification
```bash
cd laratenant-backend
php artisan tinker
# Test: App\Models\Theme\Theme::with('sections.blocks')->first();
```

#### Exit Criteria
- ✅ 7 new model files created
- ✅ All models have proper `$fillable` arrays
- ✅ All models have proper `$casts` arrays (JSON, boolean, enum)
- ✅ All relationships defined (hasMany, belongsTo)
- ✅ Theme uses `HasStoreScoping` trait
- ✅ NavigationMenuItem has self-referencing `children()` relationship
- ✅ Store model has `activeTheme()` relationship

---

### **SESSION 5: Theme Repositories**
**Duration**: 2 hours  
**Dependencies**: SESSION 4  
**Focus**: Create repository pattern for data access

#### Objectives
1. Create `ThemeRepository`
2. Create `ThemeSectionRepository`
3. Create `ThemeBlockRepository`
4. Create `NavigationMenuRepository`
5. Create `StoreAssetRepository`

#### Deliverables
**Files to Create**:
- `laratenant-backend/app/Repositories/Theme/ThemeRepository.php`
- `laratenant-backend/app/Repositories/Theme/ThemeSectionRepository.php`
- `laratenant-backend/app/Repositories/Theme/ThemeBlockRepository.php`
- `laratenant-backend/app/Repositories/Navigation/NavigationMenuRepository.php`
- `laratenant-backend/app/Repositories/Asset/StoreAssetRepository.php`

#### Verification
```bash
cd laratenant-backend
# Check namespace structure
ls -la app/Repositories/Theme/
ls -la app/Repositories/Navigation/
```

#### Exit Criteria
- ✅ 5 repository files created
- ✅ Each repository has CRUD methods (create, update, delete, find)
- ✅ ThemeRepository has `getActiveForStore(int $storeId)`
- ✅ ThemeRepository has `unpublishAllForStore(int $storeId)`
- ✅ ThemeSectionRepository has `reorder(array $sectionIds)`
- ✅ NavigationMenuRepository has `getByHandle(string $handle, int $storeId)`

---

### **SESSION 6: Theme DTOs & Actions**
**Duration**: 2-3 hours  
**Dependencies**: SESSION 5  
**Focus**: Create DTOs and action classes for business logic

#### Objectives
1. Create DTOs for Theme operations
2. Create DTOs for Section operations
3. Create DTOs for Block operations
4. Create DTOs for Navigation operations
5. Create Action classes for each operation

#### Deliverables
**Files to Create**:

**DTOs**:
- `laratenant-backend/app/DTOs/Theme/CreateThemeDTO.php`
- `laratenant-backend/app/DTOs/Theme/UpdateThemeDTO.php`
- `laratenant-backend/app/DTOs/Theme/CreateSectionDTO.php`
- `laratenant-backend/app/DTOs/Theme/UpdateSectionDTO.php`
- `laratenant-backend/app/DTOs/Theme/CreateBlockDTO.php`
- `laratenant-backend/app/DTOs/Theme/UpdateBlockDTO.php`
- `laratenant-backend/app/DTOs/Navigation/CreateMenuDTO.php`
- `laratenant-backend/app/DTOs/Navigation/CreateMenuItemDTO.php`

**Actions**:
- `laratenant-backend/app/Actions/Theme/CreateThemeAction.php`
- `laratenant-backend/app/Actions/Theme/UpdateThemeAction.php`
- `laratenant-backend/app/Actions/Theme/PublishThemeAction.php`
- `laratenant-backend/app/Actions/Theme/DuplicateThemeAction.php`
- `laratenant-backend/app/Actions/Theme/CreateSectionAction.php`
- `laratenant-backend/app/Actions/Theme/UpdateSectionAction.php`
- `laratenant-backend/app/Actions/Theme/ReorderSectionsAction.php`
- `laratenant-backend/app/Actions/Navigation/CreateNavigationMenuAction.php`
- `laratenant-backend/app/Actions/Navigation/UpdateNavigationMenuAction.php`

#### Verification
```bash
cd laratenant-backend
# Check DTOs
ls -la app/DTOs/Theme/
# Check Actions
ls -la app/Actions/Theme/
```

#### Exit Criteria
- ✅ 8 DTO files created
- ✅ 9 Action files created
- ✅ All DTOs use readonly properties (PHP 8.1+)
- ✅ PublishThemeAction uses DB transaction
- ✅ DuplicateThemeAction deep copies sections + blocks
- ✅ ReorderSectionsAction updates position field

---

### **SESSION 7: Theme API Controllers & Routes**
**Duration**: 3-4 hours  
**Dependencies**: SESSION 6  
**Focus**: Create admin API endpoints for theme management

#### Objectives
1. Create `ThemeController` (CRUD + publish)
2. Create `ThemeSectionController` (CRUD + reorder)
3. Create `ThemeBlockController` (CRUD + reorder)
4. Create `NavigationMenuController` (CRUD)
5. Create `NavigationMenuItemController` (CRUD + reorder)
6. Create `StoreAssetController` (upload + list + delete)
7. Register all routes in `routes/api/v1/merchant/theme.php`

#### Deliverables
**Files to Create**:
- `laratenant-backend/app/Http/Controllers/Api/V1/Merchant/Theme/ThemeController.php`
- `laratenant-backend/app/Http/Controllers/Api/V1/Merchant/Theme/ThemeSectionController.php`
- `laratenant-backend/app/Http/Controllers/Api/V1/Merchant/Theme/ThemeBlockController.php`
- `laratenant-backend/app/Http/Controllers/Api/V1/Merchant/Navigation/NavigationMenuController.php`
- `laratenant-backend/app/Http/Controllers/Api/V1/Merchant/Navigation/NavigationMenuItemController.php`
- `laratenant-backend/app/Http/Controllers/Api/V1/Merchant/Asset/StoreAssetController.php`
- `laratenant-backend/routes/api/v1/merchant/theme.php`

**Files to Modify**:
- `laratenant-backend/routes/api/v1/merchant.php` (include theme routes)

#### Verification
```bash
cd laratenant-backend
php artisan route:list --path=api/v1/merchant/stores/*/theme
# Should show all theme endpoints
```

#### Exit Criteria
- ✅ 6 controller files created
- ✅ 1 route file created
- ✅ ~30 API endpoints registered
- ✅ All controllers use dependency injection (Actions + Repositories)
- ✅ All endpoints return JSON responses
- ✅ File upload works for StoreAssetController
- ✅ Route model binding used for `{store}`, `{theme}`, `{section}`, `{block}`

---

### **SESSION 8: Storefront Theme API**
**Duration**: 2 hours  
**Dependencies**: SESSION 4  
**Focus**: Update storefront runtime to read theme from database

#### Objectives
1. Create `StorefrontThemeController` (returns active theme)
2. Create `StorefrontNavigationController` (returns menu by handle)
3. Update `StorefrontRuntimeService` to read from DB instead of config
4. Add theme resolution logic

#### Deliverables
**Files to Create**:
- `laratenant-backend/app/Http/Controllers/Api/V1/Storefront/StorefrontThemeController.php`
- `laratenant-backend/app/Http/Controllers/Api/V1/Storefront/StorefrontNavigationController.php`

**Files to Modify**:
- `laratenant-backend/app/Services/Storefront/Runtime/StorefrontRuntimeService.php`
  - Update `themePayload()` method to read from `themes` table
  - Update `navigationPayload()` method to read from `navigation_menus` table
- `laratenant-backend/routes/api/v1/storefront.php` (add new routes)

#### Verification
```bash
curl http://localhost:8000/api/v1/storefront/runtime/theme \
  -H "X-Store-Domain: test.justshop.test"
# Should return theme data from database
```

#### Exit Criteria
- ✅ 2 controller files created
- ✅ StorefrontRuntimeService reads from DB (not config)
- ✅ Falls back to config if no active theme
- ✅ Cache keys include theme version
- ✅ Navigation returns hierarchical menu structure
- ✅ Preview mode bypasses cache

---

### **SESSION 9: Default Theme Seeder**
**Duration**: 2 hours  
**Dependencies**: SESSION 4  
**Focus**: Create default theme with header + footer

#### Objectives
1. Create `DefaultThemeSeeder`
2. Seed default theme for existing stores
3. Create header section with 4 blocks (logo, nav, search, cart)
4. Create footer section with 3 blocks (nav, social, copyright)
5. Create default navigation menus

#### Deliverables
**Files to Create**:
- `laratenant-backend/database/seeders/Theme/DefaultThemeSeeder.php`

**Files to Modify**:
- `laratenant-backend/database/seeders/DatabaseSeeder.php` (call DefaultThemeSeeder)

#### Verification
```bash
cd laratenant-backend
php artisan db:seed --class=DefaultThemeSeeder
php artisan tinker
# Test: App\Models\Theme\Theme::with('sections.blocks')->first();
```

#### Exit Criteria
- ✅ Seeder file created
- ✅ Creates 1 theme per store
- ✅ Creates 2 sections (header + footer)
- ✅ Creates 7 blocks total (4 header + 3 footer)
- ✅ Creates 2 navigation menus (main + footer)
- ✅ Creates sample menu items
- ✅ Theme is marked as active and published
- ✅ Reusable for new store creation

---

### **SESSION 10: Dashboard - Navigation Builder UI**
**Duration**: 4-5 hours  
**Dependencies**: SESSION 7  
**Focus**: Create navigation menu builder page (highest priority MVP feature)

#### Objectives
1. Create navigation menu list page
2. Create navigation menu editor page
3. Create drag-and-drop menu tree component
4. Create menu item form (add/edit)
5. Implement reordering and nesting
6. Connect to backend API

#### Deliverables
**Files to Create**:
- `laratenant-commerce/src/app/[locale]/merchant/stores/[storeId]/theme/navigation/page.tsx`
- `laratenant-commerce/src/app/[locale]/merchant/stores/[storeId]/theme/navigation/[menuId]/page.tsx`
- `laratenant-commerce/src/components/theme/navigation/MenuList.tsx`
- `laratenant-commerce/src/components/theme/navigation/MenuTreeEditor.tsx`
- `laratenant-commerce/src/components/theme/navigation/MenuItemForm.tsx`
- `laratenant-commerce/src/components/theme/navigation/MenuItemNode.tsx`
- `laratenant-commerce/src/lib/api/theme/navigation.ts`
- `laratenant-commerce/src/types/theme/navigation.ts`

#### Verification
1. Navigate to `/en/merchant/stores/1/theme/navigation`
2. Create a new menu
3. Add menu items with drag-and-drop
4. Create nested menu (dropdown)
5. Link menu items to categories/pages/external URLs
6. Save and verify in storefront

#### Exit Criteria
- ✅ 2 page files created
- ✅ 4 component files created
- ✅ 2 utility files created (API + types)
- ✅ Drag-and-drop works (use `@dnd-kit/core`)
- ✅ Nested menus supported (max 2 levels)
- ✅ Menu item types: page, category, product, external
- ✅ Real-time preview of menu structure
- ✅ Save/cancel/delete operations work
- ✅ Responsive design (mobile-friendly)

---

### **SESSION 11: Dashboard - Asset Library & Logo Uploader**
**Duration**: 3-4 hours  
**Dependencies**: SESSION 7  
**Focus**: Create asset management UI for logo, favicon, images

#### Objectives
1. Create asset library page
2. Create logo/favicon uploader component
3. Create image gallery grid
4. Implement file upload with preview
5. Connect to StoreAssetController API
6. Add logo to store settings

#### Deliverables
**Files to Create**:
- `laratenant-commerce/src/app/[locale]/merchant/stores/[storeId]/theme/assets/page.tsx`
- `laratenant-commerce/src/components/theme/assets/AssetLibrary.tsx`
- `laratenant-commerce/src/components/theme/assets/AssetUploader.tsx`
- `laratenant-commerce/src/components/theme/assets/LogoUploader.tsx`
- `laratenant-commerce/src/components/theme/assets/AssetGrid.tsx`
- `laratenant-commerce/src/components/theme/assets/AssetCard.tsx`
- `laratenant-commerce/src/lib/api/theme/assets.ts`
- `laratenant-commerce/src/types/theme/asset.ts`

#### Verification
1. Navigate to `/en/merchant/stores/1/theme/assets`
2. Upload logo image
3. Upload favicon
4. Upload banner images
5. View uploaded assets in grid
6. Delete an asset
7. Verify logo appears in storefront header

#### Exit Criteria
- ✅ 1 page file created
- ✅ 5 component files created
- ✅ 2 utility files created (API + types)
- ✅ File upload works (drag-and-drop + click)
- ✅ Image preview before upload
- ✅ Asset types: logo, favicon, banner, other
- ✅ Grid view with thumbnails
- ✅ Delete confirmation modal
- ✅ Logo/favicon saved to `stores` table
- ✅ Alt text input for accessibility

---

### **SESSION 12: Dashboard - Theme Overview & Settings**
**Duration**: 3-4 hours  
**Dependencies**: SESSION 7  
**Focus**: Create theme selector and global settings UI

#### Objectives
1. Create theme overview page
2. Create theme selector (list of themes)
3. Create global theme settings form (colors, fonts)
4. Create color picker component
5. Create font selector component
6. Implement publish/unpublish theme

#### Deliverables
**Files to Create**:
- `laratenant-commerce/src/app/[locale]/merchant/stores/[storeId]/theme/page.tsx`
- `laratenant-commerce/src/app/[locale]/merchant/stores/[storeId]/theme/settings/page.tsx`
- `laratenant-commerce/src/components/theme/ThemeSelector.tsx`
- `laratenant-commerce/src/components/theme/ThemeCard.tsx`
- `laratenant-commerce/src/components/theme/settings/GlobalSettings.tsx`
- `laratenant-commerce/src/components/theme/settings/ColorPicker.tsx`
- `laratenant-commerce/src/components/theme/settings/FontSelector.tsx`
- `laratenant-commerce/src/lib/api/theme/themes.ts`
- `laratenant-commerce/src/types/theme/theme.ts`

#### Verification
1. Navigate to `/en/merchant/stores/1/theme`
2. See list of available themes
3. Click "Publish" on a theme
4. Navigate to `/theme/settings`
5. Change primary color
6. Change heading font
7. Save settings
8. Verify changes appear in storefront

#### Exit Criteria
- ✅ 2 page files created
- ✅ 5 component files created
- ✅ 2 utility files created (API + types)
- ✅ Theme cards show preview screenshot
- ✅ Publish/unpublish button works
- ✅ Only one theme can be published
- ✅ Color picker supports HEX input
- ✅ Font selector lists Google Fonts
- ✅ Settings form validation
- ✅ Success/error toast notifications

---

## 🔄 Optional Follow-up Sessions (Post-MVP)

### **SESSION 13: Dashboard - Section Manager** (Optional)
**Focus**: Visual section editor with drag-and-drop

### **SESSION 14: Dashboard - Visual Theme Editor** (Optional)
**Focus**: Live preview iframe with click-to-edit

### **SESSION 15: Storefront - Dynamic Theme Rendering** (Optional)
**Focus**: Update Vue components to use theme data

### **SESSION 16: Theme Marketplace & Presets** (Optional)
**Focus**: Multiple theme templates and import/export

---

## 📊 Dependency Graph

```
SESSION 1 (Theme Tables) ──┐
                            ├──> SESSION 4 (Models) ──> SESSION 5 (Repositories) ──> SESSION 6 (DTOs/Actions) ──> SESSION 7 (API)
SESSION 2 (Nav/Asset Tables)┘                                                                                           │
                                                                                                                        │
SESSION 3 (Enums) ──────────────────────────────────────────────────────────────────────────────────────────────────┘
                                                                                                                        │
                                                                                                                        ├──> SESSION 8 (Storefront API)
                                                                                                                        │
                                                                                                                        ├──> SESSION 9 (Seeder)
                                                                                                                        │
                                                                                                                        ├──> SESSION 10 (Nav Builder UI)
                                                                                                                        │
                                                                                                                        ├──> SESSION 11 (Asset Library UI)
                                                                                                                        │
                                                                                                                        └──> SESSION 12 (Theme Settings UI)
```

---

## ✅ Session Checklist

Use this to track progress:

- [ ] SESSION 1: Core Theme Database Schema
- [ ] SESSION 2: Navigation & Asset Database Schema
- [ ] SESSION 3: Theme Enums
- [ ] SESSION 4: Theme Models & Relationships
- [ ] SESSION 5: Theme Repositories
- [ ] SESSION 6: Theme DTOs & Actions
- [ ] SESSION 7: Theme API Controllers & Routes
- [ ] SESSION 8: Storefront Theme API
- [ ] SESSION 9: Default Theme Seeder
- [ ] SESSION 10: Dashboard - Navigation Builder UI
- [ ] SESSION 11: Dashboard - Asset Library & Logo Uploader
- [ ] SESSION 12: Dashboard - Theme Overview & Settings

---

## 🎯 Success Criteria (After All Sessions)

### Backend
- ✅ 9 database tables created and migrated
- ✅ 7 models with relationships
- ✅ 4 enums
- ✅ 5 repositories
- ✅ ~15 DTOs and actions
- ✅ ~30 API endpoints
- ✅ Default theme seeder working

### Dashboard
- ✅ 6 theme management pages
- ✅ ~20 reusable components
- ✅ Navigation menu builder (drag-and-drop)
- ✅ Asset library (upload/manage images)
- ✅ Theme settings (colors, fonts)

### Storefront
- ✅ Dynamic theme loading from database
- ✅ Dynamic navigation from database
- ✅ Logo/favicon from theme settings

---

## 📝 Notes

1. **Run migrations after Session 2**: `php artisan migrate`
2. **Run seeder after Session 9**: `php artisan db:seed --class=DefaultThemeSeeder`
3. **Test API after Session 7**: Use Postman or curl
4. **Test UI after Sessions 10-12**: Use browser

5. **Parallel Sessions**: You can run SESSION 1 and SESSION 2 simultaneously (different developers)

6. **Skip Optional Sessions**: SESSION 13-16 are for advanced features (post-MVP)

---

## 🚨 Common Pitfalls to Avoid

1. **Don't skip sessions** - They build on each other
2. **Don't modify migration files** after running them (create new migrations)
3. **Don't forget foreign keys** - Relationships break without them
4. **Don't hardcode store IDs** - Use route model binding
5. **Don't skip verification steps** - They catch errors early

---

## 🎓 Learning Resources

- Laravel Migrations: https://laravel.com/docs/migrations
- Eloquent Relationships: https://laravel.com/docs/eloquent-relationships
- Repository Pattern: https://asperbrothers.com/blog/repository-pattern-in-laravel/
- React DnD Kit: https://dndkit.com/
- Next.js App Router: https://nextjs.org/docs/app

---

**Ready to start? Say:**
```
Hi, run SESSION 1 from THEME_SYSTEM_SESSION_PLAN.md
```
