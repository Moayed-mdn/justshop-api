<?php

declare(strict_types=1);

namespace Database\Seeders\Theme;

use App\Models\Store;
use App\Models\Theme\Theme;
use App\Models\Navigation\NavigationMenu;
use App\Models\Navigation\NavigationMenuItem;
use Database\Seeders\Concerns\GeneratesBrandAssets;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Rich Theme Seeder - Creates the store's theme "skins".
 *
 * This seeder is responsible for:
 * - 3 professionally designed theme variations per store, each with a full,
 *   harmonious color-token set (not just primary/secondary/accent), matching
 *   typography, and button styling derived from — not independent of — the
 *   palette.
 * - Rich navigation menus with nested items.
 *
 * It is deliberately NOT responsible for section/block content anymore.
 * Earlier revisions also created a second, handle-less copy of the header/
 * hero/content/footer sections here — but SystemTemplateSeeder's
 * associateSectionsWithTemplates() always prefers the handle-based sections
 * that DefaultSectionSeeder creates, so those extra copies were never
 * attached to any page and never rendered. They were pure dead weight (and
 * showed up as confusing duplicate "Header" / "Footer" entries in the
 * merchant theme editor), so they were removed rather than patched.
 * DefaultSectionSeeder is now the single, theme-aware source of chrome
 * content, reading the color tokens seeded here back out per theme.
 */
class RichThemeSeeder extends Seeder
{
    use GeneratesBrandAssets;

    /** @var array<int, array{name: string, style: string, description: string, colors: array, typography: array}> */
    private array $themeVariations;

    public function __construct()
    {
        $this->themeVariations = [
            [
                'name' => 'Aurora',
                // "Active"/published theme — this palette is also the store's
                // canonical brand palette (see GeneratesBrandAssets::brandPalette()),
                // so the logo/favicon/banners always match the live storefront.
                'style' => 'modern',
                'description' => 'Bright, confident, and built to convert — a clean modern palette for everyday retail.',
                'colors' => $this->brandPalette(),
                'typography' => [
                    'headingFont' => 'Sora',
                    'bodyFont' => 'Inter',
                    'headingWeight' => 'bold',
                    'bodyWeight' => 'normal',
                    'baseFontSize' => 'base',
                    'lineHeight' => 'normal',
                    'letterSpacing' => 'normal',
                ],
            ],
            [
                'name' => 'Midnight Bloom',
                'style' => 'dark',
                'description' => 'A moody, upscale dark theme with a warm gold accent for premium and fashion-forward brands.',
                'colors' => [
                    'primary' => '#A78BFA',
                    'secondary' => '#F472B6',
                    'accent' => '#FBBF24',
                    'background' => '#0B0B12',
                    'surface' => '#16161F',
                    'text' => '#F5F3FF',
                    'textMuted' => '#A1A1AA',
                    'border' => '#2E2E3A',
                    'success' => '#34D399',
                    'warning' => '#FBBF24',
                    'error' => '#F87171',
                ],
                'typography' => [
                    'headingFont' => 'Playfair Display',
                    'bodyFont' => 'Lato',
                    'headingWeight' => 'bold',
                    'bodyWeight' => 'normal',
                    'baseFontSize' => 'base',
                    'lineHeight' => 'relaxed',
                    'letterSpacing' => 'wide',
                ],
            ],
            [
                'name' => 'Studio Mono',
                'style' => 'minimal',
                'description' => 'Ultra-minimal, content-first design with a single confident accent color.',
                'colors' => [
                    'primary' => '#111827',
                    'secondary' => '#6B7280',
                    'accent' => '#C2410C',
                    'background' => '#FAFAF9',
                    'surface' => '#F5F5F4',
                    'text' => '#1C1917',
                    'textMuted' => '#78716C',
                    'border' => '#E7E5E4',
                    'success' => '#15803D',
                    'warning' => '#B45309',
                    'error' => '#B91C1C',
                ],
                'typography' => [
                    'headingFont' => 'Montserrat',
                    'bodyFont' => 'Work Sans',
                    'headingWeight' => 'semibold',
                    'bodyWeight' => 'normal',
                    'baseFontSize' => 'base',
                    'lineHeight' => 'normal',
                    'letterSpacing' => 'tight',
                ],
            ],
        ];
    }

    public function run(): void
    {
        DB::transaction(function (): void {
            $stores = Store::all();

            foreach ($stores as $store) {
                $this->command->info("Creating rich themes for store: {$store->name}");
                $this->seedThemesForStore($store);
            }
        });

        $this->command->info('✅ Rich themes seeded successfully for all stores');
    }

    private function seedThemesForStore(Store $store): void
    {
        foreach ($this->themeVariations as $index => $variation) {
            $isActive = $index === 0; // First theme ("Aurora") is active/published
            $colors = $variation['colors'];

            $theme = Theme::create([
                'store_id' => $store->id,
                'name' => $variation['name'],
                'slug' => Str::slug($variation['name']),
                'version' => '1.0.0',
                'description' => $variation['description'],
                'is_active' => $isActive,
                'is_published' => $isActive,
                'settings' => [
                    'colors' => $colors,
                    'fonts' => [
                        'heading' => $variation['typography']['headingFont'],
                        'body' => $variation['typography']['bodyFont'],
                    ],
                    'typography' => $variation['typography'],
                    'buttons' => $this->buttonSettingsFor($colors),
                    // Shopify-style color schemes, all derived from the same
                    // palette so nothing drifts out of harmony.
                    'color_schemes' => $this->colorSchemesFor($colors),
                ],
            ]);

            if ($isActive) {
                $store->update(['active_theme_id' => $theme->id]);
            }

            $this->command->info("  ✓ Created theme: {$variation['name']}");
        }

        // Create rich navigation menus (once per store)
        $this->createRichNavigationMenus($store);
    }

    /**
     * Button styling derived from the palette rather than hardcoded: the
     * primary CTA always uses the theme's accent color (so it pops against
     * the brand color), and text color is picked for contrast instead of
     * assumed — a light accent like a gold (#FBBF24) needs dark text, a
     * deep one like a terracotta (#C2410C) needs white text.
     */
    private function buttonSettingsFor(array $colors): array
    {
        return [
            'primary' => [
                'backgroundColor' => $colors['accent'],
                'textColor' => $this->readableTextColor($colors['accent']),
                'borderColor' => $colors['accent'],
                'borderWidth' => 0,
                'borderRadius' => 'full',
                'paddingX' => 'lg',
                'paddingY' => 'md',
                'fontSize' => 'base',
                'fontWeight' => 'semibold',
                'hoverEffect' => 'opacity',
            ],
            'secondary' => [
                'backgroundColor' => 'transparent',
                'textColor' => $colors['primary'],
                'borderColor' => $colors['primary'],
                'borderWidth' => 2,
                'borderRadius' => 'full',
                'paddingX' => 'lg',
                'paddingY' => 'md',
                'fontSize' => 'base',
                'fontWeight' => 'semibold',
                'hoverEffect' => 'opacity',
            ],
            'outline' => [
                'backgroundColor' => 'transparent',
                'textColor' => $colors['text'],
                'borderColor' => $colors['border'],
                'borderWidth' => 1,
                'borderRadius' => 'full',
                'paddingX' => 'lg',
                'paddingY' => 'md',
                'fontSize' => 'base',
                'fontWeight' => 'medium',
                'hoverEffect' => 'darken',
            ],
        ];
    }

    private function colorSchemesFor(array $colors): array
    {
        return [
            'default' => [
                'name' => 'Default',
                'background' => $colors['background'],
                'text' => $colors['text'],
                'button_background' => $colors['primary'],
                'button_text' => $this->readableTextColor($colors['primary']),
                'secondary_background' => $colors['surface'],
                'border' => $colors['border'],
            ],
            'brand' => [
                'name' => 'Brand',
                'background' => $colors['primary'],
                'text' => $this->readableTextColor($colors['primary']),
                'button_background' => $colors['accent'],
                'button_text' => $this->readableTextColor($colors['accent']),
                'secondary_background' => $this->darkenColor($colors['primary'], 12),
                'border' => 'rgba(255, 255, 255, 0.2)',
            ],
            'dark' => [
                'name' => 'Dark',
                'background' => '#111827',
                'text' => '#FFFFFF',
                'button_background' => $colors['accent'],
                'button_text' => $this->readableTextColor($colors['accent']),
                'secondary_background' => '#374151',
                'border' => '#4B5563',
            ],
            'light' => [
                'name' => 'Light',
                'background' => $colors['surface'],
                'text' => $colors['text'],
                'button_background' => $colors['primary'],
                'button_text' => $this->readableTextColor($colors['primary']),
                'secondary_background' => $colors['background'],
                'border' => $colors['border'],
            ],
        ];
    }

    private function createRichNavigationMenus(Store $store): void
    {
        // Skip if menus already exist
        if (NavigationMenu::where('store_id', $store->id)->exists()) {
            $this->command->warn("  ⚠️  Navigation menus already exist for {$store->name}, skipping...");
            return;
        }

        // Main Menu with nested items
        $mainMenu = NavigationMenu::create([
            'store_id' => $store->id,
            'name' => 'Main Menu',
            'handle' => 'main-menu',
            'description' => 'Primary navigation menu for the header',
        ]);

        // Home
        NavigationMenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => null,
            'label' => json_encode(['en' => 'Home', 'ar' => 'الرئيسية']),
            'type' => 'page',
            'url' => '/',
            'target' => '_self',
            'position' => 1,
            'is_active' => true,
        ]);

        // Shop (parent)
        $shopParent = NavigationMenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => null,
            'label' => json_encode(['en' => 'Shop', 'ar' => 'المتجر']),
            'type' => 'page',
            'url' => '/shop',
            'target' => '_self',
            'position' => 2,
            'is_active' => true,
        ]);

        // Shop > All Products (child)
        NavigationMenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => $shopParent->id,
            'label' => json_encode(['en' => 'All Products', 'ar' => 'جميع المنتجات']),
            'type' => 'page',
            'url' => '/shop',
            'target' => '_self',
            'position' => 1,
            'is_active' => true,
        ]);

        // Shop > New Arrivals (child)
        NavigationMenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => $shopParent->id,
            'label' => json_encode(['en' => 'New Arrivals', 'ar' => 'وصل حديثاً']),
            'type' => 'page',
            'url' => '/shop/new',
            'target' => '_self',
            'position' => 2,
            'is_active' => true,
        ]);

        // Shop > Sale (child)
        NavigationMenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => $shopParent->id,
            'label' => json_encode(['en' => 'Sale', 'ar' => 'تخفيضات']),
            'type' => 'page',
            'url' => '/shop/sale',
            'target' => '_self',
            'position' => 3,
            'is_active' => true,
        ]);

        // About
        NavigationMenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => null,
            'label' => json_encode(['en' => 'About', 'ar' => 'من نحن']),
            'type' => 'page',
            'url' => '/about-us',
            'target' => '_self',
            'position' => 3,
            'is_active' => true,
        ]);

        // Contact
        NavigationMenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => null,
            'label' => json_encode(['en' => 'Contact', 'ar' => 'اتصل بنا']),
            'type' => 'page',
            'url' => '/contact',
            'target' => '_self',
            'position' => 4,
            'is_active' => true,
        ]);

        // FAQ
        NavigationMenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => null,
            'label' => json_encode(['en' => 'FAQ', 'ar' => 'الأسئلة الشائعة']),
            'type' => 'page',
            'url' => '/faq',
            'target' => '_self',
            'position' => 5,
            'is_active' => true,
        ]);

        // Summer Sale
        NavigationMenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => null,
            'label' => json_encode(['en' => 'Summer Sale', 'ar' => 'تخفيضات الصيف']),
            'type' => 'page',
            'url' => '/summer-sale',
            'target' => '_self',
            'position' => 6,
            'is_active' => true,
        ]);

        // Footer Menu
        $footerMenu = NavigationMenu::create([
            'store_id' => $store->id,
            'name' => 'Footer Menu',
            'handle' => 'footer-menu',
            'description' => 'Footer navigation menu',
        ]);

        // Create footer menu items with groups (Department, Services) and flat links

        // Group 1: Department
        $departmentGroup = NavigationMenuItem::create([
            'menu_id' => $footerMenu->id,
            'parent_id' => null,
            'label' => json_encode(['en' => 'Department', 'ar' => 'الأقسام'], JSON_UNESCAPED_UNICODE),
            'type' => 'group',
            'url' => null,
            'target' => '_self',
            'position' => 0,
            'is_active' => true,
        ]);

        $departmentItems = [
            ['en' => 'Fashion', 'ar' => 'الأزياء', 'url' => '/shop/category/fashion'],
            ['en' => 'Education', 'ar' => 'التعليم', 'url' => '/shop/category/education'],
            ['en' => 'Frozen Food', 'ar' => 'الأطعمة المجمدة', 'url' => '/shop/category/frozen-food'],
            ['en' => 'Beverages', 'ar' => 'المشروبات', 'url' => '/shop/category/beverages'],
            ['en' => 'Organic Grocery', 'ar' => 'البقالة العضوية', 'url' => '/shop/category/organic'],
            ['en' => 'Office Supplies', 'ar' => 'اللوازم المكتبية', 'url' => '/shop/category/office'],
            ['en' => 'Beauty Products', 'ar' => 'منتجات التجميل', 'url' => '/shop/category/beauty'],
            ['en' => 'Books', 'ar' => 'الكتب', 'url' => '/shop/category/books'],
            ['en' => 'Electronics & Gadget', 'ar' => 'الإلكترونيات', 'url' => '/shop/category/electronics'],
            ['en' => 'Travel Accessories', 'ar' => 'إكسسوارات السفر', 'url' => '/shop/category/travel'],
            ['en' => 'Fitness', 'ar' => 'اللياقة البدنية', 'url' => '/shop/category/fitness'],
            ['en' => 'Sneakers', 'ar' => 'الأحذية الرياضية', 'url' => '/shop/category/sneakers'],
            ['en' => 'Toys', 'ar' => 'الألعاب', 'url' => '/shop/category/toys'],
            ['en' => 'Furniture', 'ar' => 'الأثاث', 'url' => '/shop/category/furniture'],
        ];

        foreach ($departmentItems as $index => $item) {
            NavigationMenuItem::create([
                'menu_id' => $footerMenu->id,
                'parent_id' => $departmentGroup->id,
                'label' => json_encode(['en' => $item['en'], 'ar' => $item['ar']], JSON_UNESCAPED_UNICODE),
                'type' => 'link',
                'url' => $item['url'],
                'target' => '_self',
                'position' => $index,
                'is_active' => true,
            ]);
        }

        // Group 2: Services
        $servicesGroup = NavigationMenuItem::create([
            'menu_id' => $footerMenu->id,
            'parent_id' => null,
            'label' => json_encode(['en' => 'Services', 'ar' => 'الخدمات'], JSON_UNESCAPED_UNICODE),
            'type' => 'group',
            'url' => null,
            'target' => '_self',
            'position' => 1,
            'is_active' => true,
        ]);

        $serviceItems = [
            ['en' => 'Gift Card', 'ar' => 'بطاقة الهدايا', 'url' => '/gift-card'],
            ['en' => 'Mobile App', 'ar' => 'تطبيق الجوال', 'url' => '/mobile-app'],
            ['en' => 'Shipping & Delivery', 'ar' => 'الشحن والتوصيل', 'url' => '/shipping'],
            ['en' => 'Order Pickup', 'ar' => 'استلام الطلب', 'url' => '/pickup'],
            ['en' => 'Account Signup', 'ar' => 'إنشاء حساب', 'url' => '/register'],
        ];

        foreach ($serviceItems as $index => $item) {
            NavigationMenuItem::create([
                'menu_id' => $footerMenu->id,
                'parent_id' => $servicesGroup->id,
                'label' => json_encode(['en' => $item['en'], 'ar' => $item['ar']], JSON_UNESCAPED_UNICODE),
                'type' => 'link',
                'url' => $item['url'],
                'target' => '_self',
                'position' => $index,
                'is_active' => true,
            ]);
        }

        // Flat links (no children, render as single links)
        $flatLinks = [
            ['en' => 'About Us', 'ar' => 'من نحن', 'url' => '/about-us'],
            ['en' => 'Contact', 'ar' => 'اتصل بنا', 'url' => '/contact'],
            ['en' => 'Privacy Policy', 'ar' => 'سياسة الخصوصية', 'url' => '/privacy'],
            ['en' => 'Terms of Service', 'ar' => 'شروط الخدمة', 'url' => '/terms'],
            ['en' => 'FAQ', 'ar' => 'الأسئلة الشائعة', 'url' => '/faq'],
            ['en' => 'Track Order', 'ar' => 'تتبع الطلب', 'url' => '/track-order'],
        ];

        foreach ($flatLinks as $index => $item) {
            NavigationMenuItem::create([
                'menu_id' => $footerMenu->id,
                'parent_id' => null,
                'label' => json_encode(['en' => $item['en'], 'ar' => $item['ar']], JSON_UNESCAPED_UNICODE),
                'type' => 'link',
                'url' => $item['url'],
                'target' => '_self',
                'position' => $index + 2, // Start after the 2 groups
                'is_active' => true,
            ]);
        }

        $this->command->info("  ✓ Created rich navigation menus");
    }
}
