# Theme System - Fake Data Guide

**Date**: June 7, 2026  
**Purpose**: Guide for generating rich, realistic theme fake data

---

## 📋 Overview

Two new seeders have been created to generate comprehensive fake data for the theme system:

1. **`RichThemeSeeder`** - Creates 3 theme variations per store with rich content
2. **`StoreAssetsSeeder`** - Creates logos, favicons, and banner images

---

## 🎨 What Gets Created

### Per Store

#### Themes (3 variations)
1. **Modern Light Theme**
   - Colors: Blue primary, Green secondary, Amber accent
   - Fonts: Poppins headings, Inter body
   - Status: Active & Published

2. **Dark Elegance Theme**
   - Colors: Purple primary, Pink secondary, Amber accent
   - Dark background (#111827)
   - Fonts: Playfair Display headings, Lato body
   - Status: Draft

3. **Minimalist Pro Theme**
   - Colors: Black primary, Gray secondary, Red accent
   - Light gray background
   - Fonts: Montserrat headings, Open Sans body
   - Status: Draft

#### Each Theme Includes

**Header Section** (5 blocks):
- Logo block
- Navigation menu block (main-menu)
- Search bar block
- Language selector block
- Shopping cart block

**Hero Section** (4 blocks):
- Hero image block (Unsplash image)
- Hero heading text
- Hero subtext
- Call-to-action button

**Content Section** (3 blocks):
- Free shipping feature
- 24/7 support feature
- Easy returns feature

**Footer Section** (5 blocks):
- About us text
- Footer navigation (footer-menu)
- Social media links (Facebook, Twitter, Instagram, LinkedIn)
- Newsletter signup text
- Copyright text


#### Navigation Menus (2 menus)

**Main Menu** (5 parent items, 6 nested items):
```
Home
Shop
  ├─ All Products
  ├─ New Arrivals
  └─ Sale
Categories
  ├─ Electronics
  ├─ Fashion
  └─ Home & Garden
About
Contact
```

**Footer Menu** (8 items):
- About Us
- Contact
- Privacy Policy
- Terms of Service
- Shipping & Returns
- FAQ
- Size Guide
- Track Order

#### Store Assets (10 assets)

**Logo** (1):
- Type: LOGO
- Dimensions: 400x150
- Used in header blocks

**Favicon** (1):
- Type: FAVICON
- Dimensions: 32x32
- Browser tab icon

**Banners** (5):
- Type: BANNER
- Dimensions: 1920x1080
- Used in hero sections
- Variety of Unsplash images

**Showcase Images** (3):
- Type: OTHER
- Dimensions: 800x600
- General purpose images

---

## 🚀 How to Run

### Option 1: Run Full Database Seeder

```bash
cd laratenant-backend

# Fresh migration + seed everything
php artisan migrate:fresh --seed
```

This will seed:
- Users & Stores
- Categories & Brands
- Products & Variants
- Reviews & Sales
- Hero Banners
- CMS Content
- **Store Assets** (logos, favicons, banners)
- **Rich Themes** (3 per store with sections/blocks)


### Option 2: Run Only Theme Seeders

```bash
cd laratenant-backend

# Seed only assets and themes (preserves existing data)
php artisan db:seed --class=Database\\Seeders\\Theme\\StoreAssetsSeeder
php artisan db:seed --class=Database\\Seeders\\Theme\\RichThemeSeeder
```

### Option 3: Artisan Command (Recommended)

Create a custom artisan command for easy theme reseeding:

```bash
php artisan make:command SeedThemeData
```

---

## 📊 Data Statistics

### Per Store Summary

| Item | Count | Total Items |
|------|-------|-------------|
| Themes | 3 | 3 |
| Sections per theme | 4 | 12 |
| Blocks per theme | 17 | 51 |
| Navigation menus | 2 | 2 |
| Navigation items | 13 | 13 |
| Assets | 10 | 10 |

**Total Database Records per Store**: ~91 records

### For 3 Stores (Default)

- **Themes**: 9 total
- **Sections**: 36 total
- **Blocks**: 153 total
- **Navigation Menus**: 6 total
- **Navigation Items**: 39 total
- **Assets**: 30 total

**Grand Total**: ~273 theme-related records

---

## 🎨 Theme Variations Details

### 1. Modern Light Theme

**Visual Style**: Clean, bright, contemporary

**Colors**:
```json
{
  "primary": "#3B82F6",      // Bright Blue
  "secondary": "#10B981",    // Emerald Green
  "accent": "#F59E0B",       // Amber
  "background": "#FFFFFF",   // White
  "text": "#1F2937"          // Dark Gray
}
```

**Typography**:
- Headings: Poppins (modern sans-serif)
- Body: Inter (clean sans-serif)

**Best For**: Modern e-commerce, tech products, fashion


### 2. Dark Elegance Theme

**Visual Style**: Sophisticated, luxurious, dramatic

**Colors**:
```json
{
  "primary": "#8B5CF6",      // Purple
  "secondary": "#EC4899",    // Pink
  "accent": "#F59E0B",       // Amber
  "background": "#111827",   // Very Dark Gray
  "text": "#F3F4F6"          // Light Gray
}
```

**Typography**:
- Headings: Playfair Display (elegant serif)
- Body: Lato (readable sans-serif)

**Best For**: Luxury brands, jewelry, premium products, nightlife

---

### 3. Minimalist Pro Theme

**Visual Style**: Ultra-clean, content-focused, professional

**Colors**:
```json
{
  "primary": "#000000",      // Pure Black
  "secondary": "#6B7280",    // Medium Gray
  "accent": "#EF4444",       // Red
  "background": "#F9FAFB",   // Off-white
  "text": "#111827"          // Near Black
}
```

**Typography**:
- Headings: Montserrat (geometric sans-serif)
- Body: Open Sans (highly readable)

**Best For**: Corporate, minimal design, portfolios, professional services

---

## 🔧 Customization

### Adding More Theme Variations

Edit `RichThemeSeeder.php`:

```php
private array $themeVariations = [
    // Add new variation here
    [
        'name' => 'Vibrant Summer',
        'description' => 'Bright and energetic theme',
        'colors' => [
            'primary' => '#FF6B6B',
            'secondary' => '#4ECDC4',
            'accent' => '#FFE66D',
            'background' => '#FFFFFF',
            'text' => '#2C3E50',
        ],
        'fonts' => [
            'heading' => 'Bebas Neue',
            'body' => 'Raleway',
        ],
    ],
];
```


### Changing Image Sources

Edit `StoreAssetsSeeder.php`:

```php
private array $sampleAssets = [
    'logos' => [
        'https://your-cdn.com/logo1.png',
        'https://your-cdn.com/logo2.png',
    ],
    'banners' => [
        'https://your-cdn.com/banner1.jpg',
        'https://your-cdn.com/banner2.jpg',
    ],
];
```

### Adding More Navigation Items

Edit `createRichNavigationMenus()` method:

```php
// Add new menu item
NavigationMenuItem::create([
    'menu_id' => $mainMenu->id,
    'parent_id' => null,
    'label' => json_encode(['en' => 'Blog', 'ar' => 'المدونة']),
    'type' => 'page',
    'url' => '/blog',
    'target' => '_self',
    'position' => 6,
    'is_active' => true,
]);
```

---

## 🧪 Testing the Fake Data

### 1. Verify Database Records

```bash
php artisan tinker
```

```php
// Check themes
\App\Models\Theme\Theme::with('sections.blocks')->get();

// Check navigation
\App\Models\Navigation\NavigationMenu::with('items.children')->get();

// Check assets
\App\Models\Asset\StoreAsset::all();

// Check store's active theme
$store = \App\Models\Store::first();
$store->activeTheme;
$store->logo_url;
$store->favicon_url;
```

### 2. Test API Endpoints

```bash
# Get active theme
curl http://localhost:8000/api/v1/storefront/runtime/theme \
  -H "X-Store-Domain: demo.justshop.test"

# Get navigation
curl http://localhost:8000/api/v1/storefront/runtime/navigation \
  -H "X-Store-Domain: demo.justshop.test"

# Get all themes (merchant)
curl http://localhost:8000/api/v1/merchant/stores/1/themes \
  -H "Authorization: Bearer {token}"
```


### 3. View in Dashboard

```
# Login as merchant
Email: merchant@test.com
Password: password

# Navigate to theme management
http://localhost:3000/en/merchant/theme

# You should see:
- 3 theme cards (Modern Light is active)
- Each theme with name, description, status
- Ability to publish/duplicate/delete

# View theme settings
http://localhost:3000/en/merchant/theme/settings

# You should see:
- 5 color pickers
- 2 font selectors
- Current active theme's settings

# View navigation builder
http://localhost:3000/en/merchant/navigation

# You should see:
- Main Menu with nested items
- Footer Menu with 8 items
- Drag-and-drop functionality

# View assets
http://localhost:3000/en/merchant/assets

# You should see:
- 10 assets (logo, favicon, 5 banners, 3 showcases)
- Grid view with thumbnails
```

### 4. View in Storefront

```
http://localhost:3000

# You should see:
- Dynamic header with logo from database
- Navigation menu from database
- Hero section with banner image
- Features section
- Dynamic footer
- Theme colors applied
- Theme fonts applied
```

---

## 📝 Sample Data Content

### English Content Examples

**Hero Heading**: "Discover Your Style"  
**Hero Subtext**: "Shop the latest trends with exclusive offers and fast shipping"  
**CTA Button**: "Shop Now"

**Features**:
- ✓ Free Shipping on Orders Over $50
- ✓ 24/7 Customer Support
- ✓ 30-Day Easy Returns

**Footer Text**: "Your trusted online shopping destination"  
**Copyright**: "© 2026 JustShop. All rights reserved."


### Arabic Content Examples

**Hero Heading**: "اكتشف أسلوبك"  
**Hero Subtext**: "تسوق أحدث الصيحات مع عروض حصرية وشحن سريع"  
**CTA Button**: "تسوق الآن"

**Features**:
- ✓ شحن مجاني للطلبات فوق 50 دولار
- ✓ دعم العملاء على مدار الساعة
- ✓ إرجاع سهل لمدة 30 يوم

**Footer Text**: "وجهتك الموثوقة للتسوق عبر الإنترنت"  
**Copyright**: "© 2026 JustShop. جميع الحقوق محفوظة."

---

## 🎯 Use Cases

### Development
- Test theme switching functionality
- Verify color/font changes propagate
- Test navigation menu rendering
- Debug section/block components

### Demo/Presentation
- Show multiple theme options to clients
- Demonstrate customization capabilities
- Present realistic storefront examples
- Showcase multilingual support

### QA/Testing
- Test theme CRUD operations
- Verify data relationships
- Test API endpoints with real data
- Performance testing with realistic data volume

---

## 🔄 Resetting Data

### Clear Only Theme Data

```sql
-- Clear all theme data (keep other data)
DELETE FROM theme_blocks;
DELETE FROM theme_sections;
DELETE FROM themes;
DELETE FROM navigation_menu_items;
DELETE FROM navigation_menus;
DELETE FROM store_assets;

-- Reset active_theme_id
UPDATE stores SET active_theme_id = NULL, logo_url = NULL, favicon_url = NULL;
```

Then reseed:
```bash
php artisan db:seed --class=Database\\Seeders\\Theme\\StoreAssetsSeeder
php artisan db:seed --class=Database\\Seeders\\Theme\\RichThemeSeeder
```

### Full Database Reset

```bash
php artisan migrate:fresh --seed
```

---

## 📚 File Reference

### Seeder Files
- `database/seeders/Theme/RichThemeSeeder.php` - Main theme seeder
- `database/seeders/Theme/StoreAssetsSeeder.php` - Assets seeder
- `database/seeders/Theme/DefaultThemeSeeder.php` - Basic version (not used)
- `database/seeders/DatabaseSeeder.php` - Master seeder

### Order of Execution
1. PermissionSeeder
2. StoreSeeder
3. CategorySeeder, BrandSeeder, ProductSeeder
4. ... (other seeders)
5. **StoreAssetsSeeder** ← Creates logos/images first
6. **RichThemeSeeder** ← Uses logos created above

---

## ⚠️ Important Notes

1. **Assets Before Themes**: `StoreAssetsSeeder` must run before `RichThemeSeeder` because themes reference logo URLs

2. **Navigation Collision**: If you run `RichThemeSeeder` multiple times, navigation menus will be skipped (already exist check)

3. **Active Theme**: Only the first theme variation is set as active/published per store

4. **Unsplash Images**: Using Unsplash placeholder URLs - replace with your own CDN in production

5. **Multilingual**: All user-facing content is stored in JSON with `en` and `ar` keys

6. **Store Scoping**: All data is properly scoped to stores - no cross-tenant data leakage

---

## 🎨 Color Palette Reference

### Modern Light
- Primary: `#3B82F6` (Blue-500)
- Secondary: `#10B981` (Emerald-500)
- Accent: `#F59E0B` (Amber-500)

### Dark Elegance
- Primary: `#8B5CF6` (Purple-500)
- Secondary: `#EC4899` (Pink-500)
- Accent: `#F59E0B` (Amber-500)

### Minimalist Pro
- Primary: `#000000` (Black)
- Secondary: `#6B7280` (Gray-500)
- Accent: `#EF4444` (Red-500)

---

## ✅ Verification Checklist

After seeding, verify:

- [ ] 3 themes created per store
- [ ] First theme is active/published
- [ ] Each theme has 4 sections
- [ ] Each theme has 17 blocks total
- [ ] Main menu has 5 parent items + 6 children
- [ ] Footer menu has 8 items
- [ ] Logo and favicon URLs set on store
- [ ] 10 assets created per store
- [ ] All content is multilingual (EN/AR)
- [ ] Theme colors are different for each variation
- [ ] Navigation is hierarchical (nested)

---

**Seeding Complete!** 🎉

Your theme system now has rich, realistic fake data for development and testing.

For questions or issues, see:
- `THEME_SYSTEM_MASTER_REPORT.md`
- `STOREFRONT_INTEGRATION_PLAN.md`
- `PROJECT_STATUS_SUMMARY.md`
