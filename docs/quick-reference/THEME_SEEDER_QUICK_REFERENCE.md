# Theme Seeder Quick Reference Card

Quick commands and tips for theme fake data seeding.

---

## 🚀 Quick Commands

```bash
# Full reset + seed everything
php artisan migrate:fresh --seed

# Theme data only (recommended)
php artisan theme:seed --fresh

# Just assets
php artisan theme:seed --assets-only

# Just themes
php artisan theme:seed --themes-only

# Add theme data (preserve existing)
php artisan theme:seed
```

---

## 📊 What Gets Created

**Per Store**:
- 3 Themes (Modern Light, Dark Elegance, Minimalist Pro)
- 12 Sections (4 per theme)
- 48 Blocks (16 per theme)
- 2 Navigation Menus (Main + Footer)
- 19 Navigation Items (nested structure)
- 10 Assets (logo, favicon, 5 banners, 3 images)

**Total per store**: ~94 records

---

## 🎨 Theme Names

1. **Modern Light** - Blue/Green/Amber, Poppins/Inter (Active)
2. **Dark Elegance** - Purple/Pink/Amber, Playfair/Lato (Draft)
3. **Minimalist Pro** - Black/Gray/Red, Montserrat/OpenSans (Draft)

---

## ✅ Quick Verification

```bash
# Check counts
php artisan tinker

\App\Models\Theme\Theme::count();           # Should be 9 (3 stores)
\App\Models\Theme\ThemeSection::count();    # Should be 36
\App\Models\Theme\ThemeBlock::count();      # Should be 153
\App\Models\Navigation\NavigationMenu::count(); # Should be 6
\App\Models\Asset\StoreAsset::count();      # Should be 30
```

---

## 🔧 File Locations

- **Seeders**: `database/seeders/Theme/`
  - `RichThemeSeeder.php` - Main theme seeder
  - `StoreAssetsSeeder.php` - Assets seeder
  - `DefaultThemeSeeder.php` - Basic version (not used)

- **Command**: `app/Console/Commands/SeedThemeData.php`

- **Docs**: 
  - `THEME_FAKE_DATA_GUIDE.md` - Full guide
  - `FAKE_DATA_IMPLEMENTATION_COMPLETE.md` - Implementation details

---

## 🐛 Common Fixes

**Issue**: Navigation already exists  
**Fix**: Use `--fresh` flag

**Issue**: Logo not showing  
**Fix**: Run assets seeder first

**Issue**: Colors not applying  
**Fix**: Check theme is published, clear cache

---

## 📝 Login Credentials

```
Email: merchant@test.com
Password: password
```

---

## 🌐 Test URLs

**Dashboard**:
- Themes: `/en/merchant/theme`
- Settings: `/en/merchant/theme/settings`
- Navigation: `/en/merchant/navigation`
- Assets: `/en/merchant/assets`

**Storefront**:
- Home: `http://localhost:3000`

**API**:
- Theme: `/api/v1/storefront/runtime/theme`
- Navigation: `/api/v1/storefront/runtime/navigation`

---

## 💡 Quick Tips

- Always use `--fresh` for clean slate
- Assets must be seeded before themes
- First theme is always active
- All content is bilingual (EN/AR)
- Navigation has parent-child structure

---

**Full Documentation**: See `THEME_FAKE_DATA_GUIDE.md`
