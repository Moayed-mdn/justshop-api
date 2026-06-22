<?php

declare(strict_types=1);

namespace Database\Seeders\Theme;

use App\Enums\Theme\BlockTypeEnum;
use App\Enums\Theme\SectionTypeEnum;
use App\Models\Store;
use App\Models\Theme\Theme;
use App\Models\Theme\ThemeBlock;
use App\Models\Theme\ThemeSection;
use App\Models\Navigation\NavigationMenu;
use App\Models\Navigation\NavigationMenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Rich Theme Seeder - Creates Multiple Realistic Themes with Variations
 * 
 * This seeder creates:
 * - 3 different theme variations per store
 * - Rich navigation menus with nested items
 * - Multiple section types
 * - Varied color schemes
 * - Realistic content
 */
class RichThemeSeeder extends Seeder
{
    private array $themeVariations = [
        [
            'name' => 'Modern Light',
            'description' => 'Clean and modern theme with bright colors',
            'colors' => [
                'primary' => '#3B82F6',
                'secondary' => '#10B981',
                'accent' => '#F59E0B',
                'background' => '#FFFFFF',
                'text' => '#1F2937',
            ],
            'fonts' => [
                'heading' => 'Poppins',
                'body' => 'Inter',
            ],
        ],
        [
            'name' => 'Dark Elegance',
            'description' => 'Sophisticated dark theme with elegant styling',
            'colors' => [
                'primary' => '#8B5CF6',
                'secondary' => '#EC4899',
                'accent' => '#F59E0B',
                'background' => '#111827',
                'text' => '#F3F4F6',
            ],
            'fonts' => [
                'heading' => 'Playfair Display',
                'body' => 'Lato',
            ],
        ],
        [
            'name' => 'Minimalist Pro',
            'description' => 'Ultra-minimal design focused on content',
            'colors' => [
                'primary' => '#000000',
                'secondary' => '#6B7280',
                'accent' => '#EF4444',
                'background' => '#F9FAFB',
                'text' => '#111827',
            ],
            'fonts' => [
                'heading' => 'Montserrat',
                'body' => 'Open Sans',
            ],
        ],
    ];

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
            $isActive = $index === 0; // First theme is active

            $theme = Theme::create([
                'store_id' => $store->id,
                'name' => $variation['name'],
                'version' => '1.0.0',
                'description' => $variation['description'],
                'is_active' => $isActive,
                'is_published' => $isActive,
                'settings' => [
                    'colors' => $variation['colors'],
                    'fonts' => $variation['fonts'],
                ],
            ]);

            // Create sections and blocks
            $this->createHeaderSection($theme, $variation);
            $this->createHeroSection($theme, $variation);
            $this->createContentSection($theme, $variation);
            $this->createFooterSection($theme, $variation);

            if ($isActive) {
                $store->update(['active_theme_id' => $theme->id]);
            }

            $this->command->info("  ✓ Created theme: {$variation['name']}");
        }

        // Create rich navigation menus (once per store)
        $this->createRichNavigationMenus($store);
    }

    private function createHeaderSection(Theme $theme, array $variation): ThemeSection
    {
        $headerSection = ThemeSection::create([
            'theme_id' => $theme->id,
            'name' => 'Header',
            'type' => SectionTypeEnum::HEADER,
            'position' => 1,
            'is_enabled' => true,
            'is_removable' => false,
            'settings' => [
                'sticky' => true,
                'transparent' => false,
                'height' => 'auto',
                'backgroundColor' => $variation['colors']['background'],
                'textColor' => $variation['colors']['text'],
            ],
        ]);

        // Logo Block
        ThemeBlock::create([
            'section_id' => $headerSection->id,
            'type' => BlockTypeEnum::LOGO,
            'name' => 'Store Logo',
            'position' => 1,
            'is_enabled' => true,
            'is_removable' => false,
            'settings' => [
                'width' => 150,
                'height' => 50,
                'linkToHome' => true,
            ],
        ]);

        // Navigation Block
        ThemeBlock::create([
            'section_id' => $headerSection->id,
            'type' => BlockTypeEnum::NAVIGATION,
            'name' => 'Main Navigation',
            'position' => 2,
            'is_enabled' => true,
            'is_removable' => false,
            'settings' => [
                'menu_handle' => 'main-menu',
                'alignment' => 'center',
                'showIcons' => false,
                'textColor' => $variation['colors']['text'],
            ],
        ]);

        // Search Block
        ThemeBlock::create([
            'section_id' => $headerSection->id,
            'type' => BlockTypeEnum::SEARCH,
            'name' => 'Search Bar',
            'position' => 3,
            'is_enabled' => true,
            'is_removable' => true,
            'settings' => [
                'placeholder' => [
                    'en' => 'Search products...',
                    'ar' => 'البحث عن المنتجات...',
                ],
                'showSuggestions' => true,
                'minCharacters' => 2,
            ],
        ]);

        // Cart Block
        ThemeBlock::create([
            'section_id' => $headerSection->id,
            'type' => BlockTypeEnum::CART,
            'name' => 'Shopping Cart',
            'position' => 4,
            'is_enabled' => true,
            'is_removable' => false,
            'settings' => [
                'showItemCount' => true,
                'iconStyle' => 'outline',
                'color' => $variation['colors']['primary'],
            ],
        ]);

        return $headerSection;
    }

    private function createHeroSection(Theme $theme, array $variation): ThemeSection
    {
        $heroSection = ThemeSection::create([
            'theme_id' => $theme->id,
            'name' => 'Hero Banner',
            'type' => SectionTypeEnum::HERO,
            'position' => 2,
            'is_enabled' => true,
            'is_removable' => true,
            'settings' => [
                'height' => '500px',
                'overlayOpacity' => 0.3,
                'backgroundColor' => $variation['colors']['primary'],
            ],
        ]);

        // Hero Image Block
        ThemeBlock::create([
            'section_id' => $heroSection->id,
            'type' => BlockTypeEnum::IMAGE,
            'name' => 'Hero Image',
            'position' => 1,
            'is_enabled' => true,
            'is_removable' => false,
            'settings' => [
                'imageUrl' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8',
                'altText' => [
                    'en' => 'Shop the latest collection',
                    'ar' => 'تسوق أحدث المجموعات',
                ],
                'objectFit' => 'cover',
            ],
        ]);

        // Hero Text Block
        ThemeBlock::create([
            'section_id' => $heroSection->id,
            'type' => BlockTypeEnum::TEXT,
            'name' => 'Hero Heading',
            'position' => 2,
            'is_enabled' => true,
            'is_removable' => false,
            'settings' => [
                'content' => [
                    'en' => 'Discover Your Style',
                    'ar' => 'اكتشف أسلوبك',
                ],
                'fontSize' => 48,
                'fontWeight' => 'bold',
                'textAlign' => 'center',
                'color' => '#FFFFFF',
            ],
        ]);

        // Hero Subtext Block
        ThemeBlock::create([
            'section_id' => $heroSection->id,
            'type' => BlockTypeEnum::TEXT,
            'name' => 'Hero Subtext',
            'position' => 3,
            'is_enabled' => true,
            'is_removable' => true,
            'settings' => [
                'content' => [
                    'en' => 'Shop the latest trends with exclusive offers and fast shipping',
                    'ar' => 'تسوق أحدث الصيحات مع عروض حصرية وشحن سريع',
                ],
                'fontSize' => 18,
                'fontWeight' => 'normal',
                'textAlign' => 'center',
                'color' => '#F3F4F6',
            ],
        ]);

        // Hero CTA Button
        ThemeBlock::create([
            'section_id' => $heroSection->id,
            'type' => BlockTypeEnum::BUTTON,
            'name' => 'Shop Now Button',
            'position' => 4,
            'is_enabled' => true,
            'is_removable' => true,
            'settings' => [
                'label' => [
                    'en' => 'Shop Now',
                    'ar' => 'تسوق الآن',
                ],
                'url' => '/shop',
                'style' => 'primary',
                'size' => 'large',
                'backgroundColor' => $variation['colors']['accent'],
            ],
        ]);

        return $heroSection;
    }

    private function createContentSection(Theme $theme, array $variation): ThemeSection
    {
        $contentSection = ThemeSection::create([
            'theme_id' => $theme->id,
            'name' => 'Features Section',
            'type' => SectionTypeEnum::FEATURED,
            'position' => 3,
            'is_enabled' => true,
            'is_removable' => true,
            'settings' => [
                'padding' => '60px 0',
                'backgroundColor' => $variation['colors']['background'],
            ],
        ]);

        // Feature 1
        ThemeBlock::create([
            'section_id' => $contentSection->id,
            'type' => BlockTypeEnum::TEXT,
            'name' => 'Free Shipping',
            'position' => 1,
            'is_enabled' => true,
            'is_removable' => true,
            'settings' => [
                'content' => [
                    'en' => '✓ Free Shipping on Orders Over $50',
                    'ar' => '✓ شحن مجاني للطلبات فوق 50 دولار',
                ],
                'fontSize' => 18,
                'textAlign' => 'center',
                'color' => $variation['colors']['text'],
            ],
        ]);

        // Feature 2
        ThemeBlock::create([
            'section_id' => $contentSection->id,
            'type' => BlockTypeEnum::TEXT,
            'name' => '24/7 Support',
            'position' => 2,
            'is_enabled' => true,
            'is_removable' => true,
            'settings' => [
                'content' => [
                    'en' => '✓ 24/7 Customer Support',
                    'ar' => '✓ دعم العملاء على مدار الساعة',
                ],
                'fontSize' => 18,
                'textAlign' => 'center',
                'color' => $variation['colors']['text'],
            ],
        ]);

        // Feature 3
        ThemeBlock::create([
            'section_id' => $contentSection->id,
            'type' => BlockTypeEnum::TEXT,
            'name' => 'Easy Returns',
            'position' => 3,
            'is_enabled' => true,
            'is_removable' => true,
            'settings' => [
                'content' => [
                    'en' => '✓ 30-Day Easy Returns',
                    'ar' => '✓ إرجاع سهل لمدة 30 يوم',
                ],
                'fontSize' => 18,
                'textAlign' => 'center',
                'color' => $variation['colors']['text'],
            ],
        ]);

        return $contentSection;
    }

    private function createFooterSection(Theme $theme, array $variation): ThemeSection
    {
        $isDark = $variation['name'] === 'Dark Elegance';
        
        $footerSection = ThemeSection::create([
            'theme_id' => $theme->id,
            'name' => 'Footer',
            'type' => SectionTypeEnum::FOOTER,
            'position' => 4,
            'is_enabled' => true,
            'is_removable' => false,
            'settings' => [
                'background' => $isDark ? '#000000' : '#1F2937',
                'textColor' => '#FFFFFF',
                'padding' => '40px 0',
            ],
        ]);

        // Footer About Text
        ThemeBlock::create([
            'section_id' => $footerSection->id,
            'type' => BlockTypeEnum::TEXT,
            'name' => 'About Us',
            'position' => 1,
            'is_enabled' => true,
            'is_removable' => true,
            'settings' => [
                'content' => [
                    'en' => 'Your trusted online shopping destination',
                    'ar' => 'وجهتك الموثوقة للتسوق عبر الإنترنت',
                ],
                'fontSize' => 14,
                'color' => '#9CA3AF',
            ],
        ]);

        // Footer Navigation
        ThemeBlock::create([
            'section_id' => $footerSection->id,
            'type' => BlockTypeEnum::NAVIGATION,
            'name' => 'Footer Links',
            'position' => 2,
            'is_enabled' => true,
            'is_removable' => true,
            'settings' => [
                'menu_handle' => 'footer-menu',
                'columns' => 3,
                'textColor' => '#FFFFFF',
            ],
        ]);

        // Social Links
        ThemeBlock::create([
            'section_id' => $footerSection->id,
            'type' => BlockTypeEnum::SOCIAL_LINKS,
            'name' => 'Social Media',
            'position' => 3,
            'is_enabled' => true,
            'is_removable' => true,
            'settings' => [
                'platforms' => [
                    'facebook' => 'https://facebook.com/justshop',
                    'twitter' => 'https://twitter.com/justshop',
                    'instagram' => 'https://instagram.com/justshop',
                    'linkedin' => 'https://linkedin.com/company/justshop',
                ],
                'iconSize' => 24,
                'iconColor' => '#FFFFFF',
            ],
        ]);

        // Newsletter Signup
        ThemeBlock::create([
            'section_id' => $footerSection->id,
            'type' => BlockTypeEnum::TEXT,
            'name' => 'Newsletter',
            'position' => 4,
            'is_enabled' => true,
            'is_removable' => true,
            'settings' => [
                'content' => [
                    'en' => 'Subscribe to our newsletter for exclusive offers',
                    'ar' => 'اشترك في نشرتنا الإخبارية للحصول على عروض حصرية',
                ],
                'fontSize' => 16,
                'color' => '#FFFFFF',
            ],
        ]);

        // Copyright
        ThemeBlock::create([
            'section_id' => $footerSection->id,
            'type' => BlockTypeEnum::TEXT,
            'name' => 'Copyright',
            'position' => 5,
            'is_enabled' => true,
            'is_removable' => false,
            'settings' => [
                'content' => [
                    'en' => '© ' . date('Y') . ' JustShop. All rights reserved.',
                    'ar' => '© ' . date('Y') . ' JustShop. جميع الحقوق محفوظة.',
                ],
                'fontSize' => 14,
                'textAlign' => 'center',
                'color' => '#6B7280',
            ],
        ]);

        return $footerSection;
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

