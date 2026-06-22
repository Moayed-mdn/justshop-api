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

class DefaultThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $stores = Store::all();

            foreach ($stores as $store) {
                $this->seedThemeForStore($store);
            }
        });

        $this->command->info('✅ Default themes seeded successfully for all stores');
    }

    /**
     * Seed default theme for a single store
     */
    private function seedThemeForStore(Store $store): void
    {
        // Check if store already has an active theme
        if ($store->activeTheme()->exists()) {
            $this->command->warn("⚠️  Store {$store->name} already has an active theme, skipping...");
            return;
        }

        // Create default theme
        $theme = Theme::create([
            'store_id' => $store->id,
            'name' => 'Default Theme',
            'version' => '1.0.0',
            'description' => 'Default storefront theme with header and footer',
            'is_active' => true,
            'is_published' => true,
            'settings' => [
                'colors' => [
                    'primary' => '#3B82F6',
                    'secondary' => '#10B981',
                    'accent' => '#F59E0B',
                    'background' => '#FFFFFF',
                    'text' => '#1F2937',
                ],
                'fonts' => [
                    'heading' => 'Inter',
                    'body' => 'Inter',
                ],
            ],
        ]);

        // Create header section
        $headerSection = $this->createHeaderSection($theme);
        
        // Create footer section
        $footerSection = $this->createFooterSection($theme);

        // Create navigation menus
        $this->createNavigationMenus($store);

        // Update store to set active theme
        $store->update(['active_theme_id' => $theme->id]);

        $this->command->info("✅ Created default theme for store: {$store->name}");
    }

    /**
     * Create header section with blocks
     */
    private function createHeaderSection(Theme $theme): ThemeSection
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
            ],
        ]);

        // 1. Logo Block
        ThemeBlock::create([
            'section_id' => $headerSection->id,
            'type' => BlockTypeEnum::LOGO,
            'name' => 'Store Logo',
            'position' => 1,
            'is_enabled' => true,
            'is_removable' => false,
            'settings' => [
                'width' => 120,
                'height' => 40,
                'linkToHome' => true,
            ],
        ]);

        // 2. Navigation Block
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
            ],
        ]);

        // 3. Search Block
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
            ],
        ]);

        // 4. Cart Block
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
            ],
        ]);

        return $headerSection;
    }

    /**
     * Create footer section with blocks
     */
    private function createFooterSection(Theme $theme): ThemeSection
    {
        $footerSection = ThemeSection::create([
            'theme_id' => $theme->id,
            'name' => 'Footer',
            'type' => SectionTypeEnum::FOOTER,
            'position' => 2,
            'is_enabled' => true,
            'is_removable' => false,
            'settings' => [
                'background' => '#1F2937',
                'textColor' => '#FFFFFF',
            ],
        ]);

        // 1. Footer Navigation Block
        ThemeBlock::create([
            'section_id' => $footerSection->id,
            'type' => BlockTypeEnum::NAVIGATION,
            'name' => 'Footer Navigation',
            'position' => 1,
            'is_enabled' => true,
            'is_removable' => true,
            'settings' => [
                'menu_handle' => 'footer-menu',
                'columns' => 3,
            ],
        ]);

        // 2. Social Links Block
        ThemeBlock::create([
            'section_id' => $footerSection->id,
            'type' => BlockTypeEnum::SOCIAL_LINKS,
            'name' => 'Social Media Links',
            'position' => 2,
            'is_enabled' => true,
            'is_removable' => true,
            'settings' => [
                'platforms' => [
                    'facebook' => '',
                    'twitter' => '',
                    'instagram' => '',
                    'linkedin' => '',
                ],
                'iconSize' => 24,
            ],
        ]);

        // 3. Copyright Block
        ThemeBlock::create([
            'section_id' => $footerSection->id,
            'type' => BlockTypeEnum::TEXT,
            'name' => 'Copyright',
            'position' => 3,
            'is_enabled' => true,
            'is_removable' => false,
            'settings' => [
                'content' => [
                    'en' => '© ' . date('Y') . ' All rights reserved.',
                    'ar' => '© ' . date('Y') . ' جميع الحقوق محفوظة.',
                ],
                'alignment' => 'center',
                'fontSize' => 14,
            ],
        ]);

        return $footerSection;
    }

    /**
     * Create default navigation menus
     */
    private function createNavigationMenus(Store $store): void
    {
        // Create Main Menu
        $mainMenu = NavigationMenu::create([
            'store_id' => $store->id,
            'name' => 'Main Menu',
            'handle' => 'main-menu',
            'description' => 'Primary navigation menu for the header',
        ]);

        // Create main menu items
        NavigationMenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => null,
            'label' => json_encode([
                'en' => 'Home',
                'ar' => 'الرئيسية',
            ]),
            'type' => 'page',
            'url' => '/',
            'target' => '_self',
            'resource_type' => null,
            'resource_id' => null,
            'position' => 1,
            'is_active' => true,
        ]);

        NavigationMenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => null,
            'label' => json_encode([
                'en' => 'Shop',
                'ar' => 'المتجر',
            ]),
            'type' => 'page',
            'url' => '/shop',
            'target' => '_self',
            'resource_type' => null,
            'resource_id' => null,
            'position' => 2,
            'is_active' => true,
        ]);

        NavigationMenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => null,
            'label' => json_encode([
                'en' => 'About',
                'ar' => 'من نحن',
            ]),
            'type' => 'page',
            'url' => '/about-us',
            'target' => '_self',
            'resource_type' => null,
            'resource_id' => null,
            'position' => 3,
            'is_active' => true,
        ]);

        NavigationMenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => null,
            'label' => json_encode([
                'en' => 'Contact',
                'ar' => 'اتصل بنا',
            ]),
            'type' => 'page',
            'url' => '/contact',
            'target' => '_self',
            'resource_type' => null,
            'resource_id' => null,
            'position' => 4,
            'is_active' => true,
        ]);

        NavigationMenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => null,
            'label' => json_encode([
                'en' => 'FAQ',
                'ar' => 'الأسئلة الشائعة',
            ]),
            'type' => 'page',
            'url' => '/faq',
            'target' => '_self',
            'resource_type' => null,
            'resource_id' => null,
            'position' => 5,
            'is_active' => true,
        ]);

        NavigationMenuItem::create([
            'menu_id' => $mainMenu->id,
            'parent_id' => null,
            'label' => json_encode([
                'en' => 'Summer Sale',
                'ar' => 'تخفيضات الصيف',
            ]),
            'type' => 'page',
            'url' => '/summer-sale',
            'target' => '_self',
            'resource_type' => null,
            'resource_id' => null,
            'position' => 6,
            'is_active' => true,
        ]);

        // Create Footer Menu
        $footerMenu = NavigationMenu::create([
            'store_id' => $store->id,
            'name' => 'Footer Menu',
            'handle' => 'footer-menu',
            'description' => 'Footer navigation menu',
        ]);

        // Create footer menu items with groups and flat links
        
        // Group 1: About Us
        $aboutGroup = NavigationMenuItem::create([
            'menu_id' => $footerMenu->id,
            'parent_id' => null,
            'label' => json_encode([
                'en' => 'About Us',
                'ar' => 'من نحن',
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'group',
            'url' => null,
            'target' => '_self',
            'resource_type' => null,
            'resource_id' => null,
            'position' => 0,
            'is_active' => true,
        ]);

        NavigationMenuItem::create([
            'menu_id' => $footerMenu->id,
            'parent_id' => $aboutGroup->id,
            'label' => json_encode([
                'en' => 'Our Story',
                'ar' => 'قصتنا',
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'link',
            'url' => '/about',
            'target' => '_self',
            'resource_type' => null,
            'resource_id' => null,
            'position' => 0,
            'is_active' => true,
        ]);

        NavigationMenuItem::create([
            'menu_id' => $footerMenu->id,
            'parent_id' => $aboutGroup->id,
            'label' => json_encode([
                'en' => 'Careers',
                'ar' => 'الوظائف',
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'link',
            'url' => '/careers',
            'target' => '_self',
            'resource_type' => null,
            'resource_id' => null,
            'position' => 1,
            'is_active' => true,
        ]);

        NavigationMenuItem::create([
            'menu_id' => $footerMenu->id,
            'parent_id' => $aboutGroup->id,
            'label' => json_encode([
                'en' => 'Press',
                'ar' => 'الصحافة',
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'link',
            'url' => '/press',
            'target' => '_self',
            'resource_type' => null,
            'resource_id' => null,
            'position' => 2,
            'is_active' => true,
        ]);

        // Group 2: Customer Service
        $serviceGroup = NavigationMenuItem::create([
            'menu_id' => $footerMenu->id,
            'parent_id' => null,
            'label' => json_encode([
                'en' => 'Customer Service',
                'ar' => 'خدمة العملاء',
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'group',
            'url' => null,
            'target' => '_self',
            'resource_type' => null,
            'resource_id' => null,
            'position' => 1,
            'is_active' => true,
        ]);

        NavigationMenuItem::create([
            'menu_id' => $footerMenu->id,
            'parent_id' => $serviceGroup->id,
            'label' => json_encode([
                'en' => 'Contact Us',
                'ar' => 'اتصل بنا',
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'link',
            'url' => '/contact',
            'target' => '_self',
            'resource_type' => null,
            'resource_id' => null,
            'position' => 0,
            'is_active' => true,
        ]);

        NavigationMenuItem::create([
            'menu_id' => $footerMenu->id,
            'parent_id' => $serviceGroup->id,
            'label' => json_encode([
                'en' => 'Shipping & Returns',
                'ar' => 'الشحن والاسترجاع',
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'link',
            'url' => '/shipping',
            'target' => '_self',
            'resource_type' => null,
            'resource_id' => null,
            'position' => 1,
            'is_active' => true,
        ]);

        NavigationMenuItem::create([
            'menu_id' => $footerMenu->id,
            'parent_id' => $serviceGroup->id,
            'label' => json_encode([
                'en' => 'Track Order',
                'ar' => 'تتبع الطلب',
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'link',
            'url' => '/track-order',
            'target' => '_self',
            'resource_type' => null,
            'resource_id' => null,
            'position' => 2,
            'is_active' => true,
        ]);

        // Flat link: Privacy Policy (no children, renders as single link)
        NavigationMenuItem::create([
            'menu_id' => $footerMenu->id,
            'parent_id' => null,
            'label' => json_encode([
                'en' => 'Privacy Policy',
                'ar' => 'سياسة الخصوصية',
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'link',
            'url' => '/privacy',
            'target' => '_self',
            'resource_type' => null,
            'resource_id' => null,
            'position' => 2,
            'is_active' => true,
        ]);

        // Flat link: Terms of Service (no children, renders as single link)
        NavigationMenuItem::create([
            'menu_id' => $footerMenu->id,
            'parent_id' => null,
            'label' => json_encode([
                'en' => 'Terms of Service',
                'ar' => 'شروط الخدمة',
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'link',
            'url' => '/terms',
            'target' => '_self',
            'resource_type' => null,
            'resource_id' => null,
            'position' => 3,
            'is_active' => true,
        ]);
    }
}
