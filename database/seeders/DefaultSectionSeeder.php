<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Theme\BlockTypeEnum;
use App\Enums\Theme\SectionTypeEnum;
use App\Enums\Theme\TemplateTypeEnum;
use App\Models\Theme\Theme;
use App\Models\Theme\ThemeBlock;
use App\Models\Theme\ThemeSection;
use App\Models\Theme\ThemeSectionGroup;
use App\Models\Theme\ThemeTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultSectionSeeder extends Seeder
{
    public function run(): void
    {
        $themes = Theme::all();

        if ($themes->isEmpty()) {
            $this->command?->warn('No themes found. Skipping default section seeder.');
            return;
        }

        foreach ($themes as $theme) {
            $this->seedDefaultSections($theme);
        }

        $this->command?->info("Default sections seeded for {$themes->count()} themes.");
    }

    private function seedDefaultSections(Theme $theme): void
    {
        $sectionDefinitions = $this->getSectionDefinitions();
        $createdSections = [];

        foreach ($sectionDefinitions as $def) {
            $handle = $def['handle'] ?? $def['type'];

            $section = ThemeSection::updateOrCreate(
                [
                    'theme_id' => $theme->id,
                    'handle' => $handle,
                ],
                [
                    'name' => $def['name'],
                    'type' => $def['type'],
                    'description' => $def['description'] ?? '',
                    'settings' => $def['settings'] ?? [],
                    'position' => $def['position'] ?? 0,
                    'is_enabled' => true,
                    'is_removable' => $def['removable'] ?? true,
                ]
            );

            $this->seedBlocksForSection($section, $def['blocks'] ?? []);

            $createdSections[$handle] = $section;
        }

        $this->seedSectionGroups($theme, $createdSections);
        $this->associateSectionsWithTemplates($theme, $createdSections);
    }

    private function seedBlocksForSection(ThemeSection $section, array $blockDefs): void
    {
        foreach ($blockDefs as $position => $def) {
            ThemeBlock::updateOrCreate(
                [
                    'section_id' => $section->id,
                    'handle' => $def['handle'] ?? $def['type'],
                ],
                [
                    'name' => $def['name'],
                    'type' => $def['type'],
                    'description' => $def['description'] ?? '',
                    'settings' => $def['settings'] ?? [],
                    'content' => $def['content'] ?? [],
                    'position' => $position,
                    'is_enabled' => true,
                    'is_removable' => $def['removable'] ?? true,
                ]
            );
        }
    }

    private function seedSectionGroups(Theme $theme, array $sections): void
    {
        $headerKeys = ['header', 'announcement_bar'];
        $footerKeys = ['footer', 'copyright_bar'];

        $headerSections = [];
        foreach ($headerKeys as $key) {
            if (isset($sections[$key])) {
                $section = $sections[$key];
                $headerSections[] = [
                    'id' => (string) $section->id,
                    'type' => $section->type->value,
                    'settings' => $section->settings,
                    'data' => [],
                ];
            }
        }

        $footerSections = [];
        foreach ($footerKeys as $key) {
            if (isset($sections[$key])) {
                $section = $sections[$key];
                $footerSections[] = [
                    'id' => (string) $section->id,
                    'type' => $section->type->value,
                    'settings' => $section->settings,
                    'data' => [],
                ];
            }
        }

        if (!empty($headerSections)) {
            ThemeSectionGroup::updateOrCreate(
                ['theme_id' => $theme->id, 'handle' => 'header'],
                [
                    'name' => 'Header Section Group',
                    'sections' => $headerSections,
                    'order' => collect($headerSections)->pluck('id')->values()->toArray(),
                ]
            );
        }

        if (!empty($footerSections)) {
            ThemeSectionGroup::updateOrCreate(
                ['theme_id' => $theme->id, 'handle' => 'footer'],
                [
                    'name' => 'Footer Section Group',
                    'sections' => $footerSections,
                    'order' => collect($footerSections)->pluck('id')->values()->toArray(),
                ]
            );
        }
    }

    private function associateSectionsWithTemplates(Theme $theme, array $sections): void
    {
        $templates = ThemeTemplate::where('theme_id', $theme->id)->get();

        foreach ($templates as $template) {
            $sectionTypes = $template->settings['section_types'] ?? [];
            if (empty($sectionTypes)) {
                continue;
            }

            $sectionIds = [];
            $position = 0;

            foreach ($sectionTypes as $type) {
                $section = ThemeSection::where('theme_id', $theme->id)
                    ->where('type', $type)
                    ->orderByRaw('CASE WHEN handle IS NOT NULL THEN 0 ELSE 1 END')
                    ->orderBy('id', 'desc')
                    ->first();

                if (!$section) {
                    continue;
                }

                $sectionIds[$section->id] = ['position' => $position];
                $position++;
            }

            if (!empty($sectionIds)) {
                $template->sections()->sync($sectionIds);
            }
        }
    }

    private function getSectionDefinitions(): array
    {
        return [
            // ── Header ──
            [
                'type' => SectionTypeEnum::HEADER->value,
                'handle' => 'header',
                'name' => 'Header',
                'description' => 'Main site header with logo, navigation, and actions.',
                'position' => 0,
                'removable' => false,
                'settings' => [
                    'sticky' => true,
                    'transparent' => false,
                    'full_width' => true,
                    'show_topbar' => true,
                    'show_search' => true,
                    'show_cart' => true,
                    'show_account' => true,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::LOGO->value,
                        'handle' => 'logo',
                        'name' => 'Site Logo',
                        'removable' => false,
                        'settings' => [
                            'logo_url' => '',
                            'logo_alt_text' => 'Store logo',
                            'logo_link' => '/',
                            'logo_max_width' => 180,
                        ],
                    ],
                    [
                        'type' => BlockTypeEnum::NAVIGATION->value,
                        'handle' => 'main_navigation',
                        'name' => 'Main Navigation',
                        'removable' => false,
                        'settings' => ['menu_handle' => 'main', 'show_children' => true],
                    ],
                    [
                        'type' => BlockTypeEnum::SEARCH->value,
                        'handle' => 'header_search',
                        'name' => 'Search Bar',
                        'removable' => true,
                        'settings' => ['placeholder' => 'Search products...'],
                    ],
                    [
                        'type' => BlockTypeEnum::CART->value,
                        'handle' => 'cart_icon',
                        'name' => 'Cart Icon',
                        'removable' => false,
                        'settings' => ['icon_style' => 'default'],
                    ],
                    [
                        'type' => BlockTypeEnum::LANGUAGE_SELECTOR->value,
                        'handle' => 'language_selector',
                        'name' => 'Language Selector',
                        'removable' => true,
                        'settings' => ['show_labels' => true],
                    ],
                ],
            ],
            // ── Announcement Bar ──
            [
                'type' => SectionTypeEnum::ANNOUNCEMENT_BAR->value,
                'handle' => 'announcement_bar',
                'name' => 'Announcement Bar',
                'description' => 'Top announcement bar for promotions and notices.',
                'position' => 1,
                'removable' => true,
                'settings' => [
                    'enabled' => true,
                    'text' => 'Free shipping on orders over $50!',
                    'phone' => '+001234567890',
                    'offer_text' => 'Free shipping on orders over $50!',
                    'shop_now_text' => 'Shop Now',
                    'shop_now_link' => '/en/shop',
                    'show_language_switcher' => true,
                    'dismissible' => true,
                    'bg_color' => '#1F2937',
                    'text_color' => '#FFFFFF',
                ],
                'blocks' => [],
            ],
            // ── Footer ──
            [
                'type' => SectionTypeEnum::FOOTER->value,
                'handle' => 'footer',
                'name' => 'Footer',
                'description' => 'Main site footer with links and brand info.',
                'position' => 10,
                'removable' => false,
                'settings' => [
                    'show_newsletter' => true,
                    'columns' => 4,
                    'full_width' => false,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::NAVIGATION->value,
                        'handle' => 'footer_navigation_1',
                        'name' => 'Footer Column 1 - Shop',
                        'removable' => false,
                        'settings' => ['menu_handle' => 'footer_shop', 'title' => 'Shop'],
                    ],
                    [
                        'type' => BlockTypeEnum::NAVIGATION->value,
                        'handle' => 'footer_navigation_2',
                        'name' => 'Footer Column 2 - Support',
                        'removable' => false,
                        'settings' => ['menu_handle' => 'footer_support', 'title' => 'Support'],
                    ],
                    [
                        'type' => BlockTypeEnum::SOCIAL_LINKS->value,
                        'handle' => 'social_links',
                        'name' => 'Social Media Links',
                        'removable' => true,
                        'settings' => [
                            'platforms' => ['facebook', 'twitter', 'instagram'],
                        ],
                    ],
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'newsletter_signup',
                        'name' => 'Newsletter Signup',
                        'removable' => true,
                        'settings' => [
                            'html' => '<div class="newsletter"><h3>Stay in touch</h3><p>Subscribe to our newsletter.</p></div>',
                        ],
                    ],
                ],
            ],
            // ── Copyright Bar ──
            [
                'type' => SectionTypeEnum::COPYRIGHT_BAR->value,
                'handle' => 'copyright_bar',
                'name' => 'Copyright Bar',
                'description' => 'Copyright notice and payment icons.',
                'position' => 11,
                'removable' => true,
                'settings' => [
                    'text' => '© 2026 All rights reserved.',
                    'show_payment_icons' => true,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::COPYRIGHT->value,
                        'handle' => 'copyright_text',
                        'name' => 'Copyright Text',
                        'removable' => false,
                        'settings' => ['text' => '© 2026 Your Store. All rights reserved.'],
                    ],
                    [
                        'type' => BlockTypeEnum::PAYMENT_ICONS->value,
                        'handle' => 'payment_icons',
                        'name' => 'Payment Icons',
                        'removable' => true,
                        'settings' => [
                            'icons' => ['visa', 'mastercard', 'paypal', 'amex'],
                        ],
                    ],
                ],
            ],
            // ── Content ──
            [
                'type' => SectionTypeEnum::CONTENT->value,
                'handle' => 'content',
                'name' => 'Content Area',
                'description' => 'Generic content area for page body.',
                'position' => 5,
                'removable' => true,
                'settings' => [
                    'max_width' => '1200px',
                    'padding' => 'py-8',
                ],
                'blocks' => [],
            ],
            // ── Hero Banner ──
            [
                'type' => SectionTypeEnum::HERO->value,
                'handle' => 'hero',
                'name' => 'Hero Banner',
                'description' => 'Full-width hero banner with image, heading, and CTA.',
                'position' => 2,
                'removable' => true,
                'settings' => [
                    'full_width' => true,
                    'min_height' => '500px',
                    'overlay_opacity' => 0.3,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::IMAGE->value,
                        'handle' => 'hero_background',
                        'name' => 'Hero Background Image',
                        'removable' => false,
                        'settings' => [
                            'src' => '',
                            'alt' => 'Hero background',
                            'lazy' => false,
                        ],
                    ],
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'hero_heading',
                        'name' => 'Hero Heading',
                        'removable' => false,
                        'settings' => ['html' => '<h1 class="text-5xl font-bold text-white">Welcome to Our Store</h1>'],
                    ],
                    [
                        'type' => BlockTypeEnum::TEXT->value,
                        'handle' => 'hero_subheading',
                        'name' => 'Hero Subheading',
                        'removable' => true,
                        'settings' => ['text' => 'Discover amazing products at great prices.', 'alignment' => 'center'],
                    ],
                    [
                        'type' => BlockTypeEnum::BUTTON->value,
                        'handle' => 'hero_cta',
                        'name' => 'Hero CTA Button',
                        'removable' => false,
                        'settings' => ['text' => 'Shop Now', 'style' => 'primary', 'url' => '/en/collections/all'],
                    ],
                ],
            ],
            // ── Cart Items ──
            [
                'type' => SectionTypeEnum::CART_ITEMS->value,
                'handle' => 'cart_items',
                'name' => 'Cart Items List',
                'description' => 'Lists all items in the shopping cart.',
                'position' => 20,
                'removable' => true,
                'settings' => [
                    'show_images' => true,
                    'show_prices' => true,
                    'show_quantities' => true,
                    'show_remove' => true,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'cart_heading',
                        'name' => 'Cart Heading',
                        'removable' => false,
                        'settings' => ['html' => '<h1 class="text-2xl font-bold mb-6">Shopping Cart</h1>'],
                    ],
                    [
                        'type' => BlockTypeEnum::TEXT->value,
                        'handle' => 'cart_empty_text',
                        'name' => 'Empty Cart Text',
                        'removable' => true,
                        'settings' => ['text' => 'Your cart is empty.', 'alignment' => 'center'],
                    ],
                ],
            ],
            // ── Cart Summary ──
            [
                'type' => SectionTypeEnum::CART_SUMMARY->value,
                'handle' => 'cart_summary',
                'name' => 'Cart Order Summary',
                'description' => 'Order totals, discounts, and checkout button.',
                'position' => 21,
                'removable' => true,
                'settings' => [
                    'show_shipping' => true,
                    'show_tax' => true,
                    'show_discount' => true,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'summary_heading',
                        'name' => 'Summary Heading',
                        'removable' => false,
                        'settings' => ['html' => '<h2 class="text-xl font-semibold mb-4">Order Summary</h2>'],
                    ],
                    [
                        'type' => BlockTypeEnum::BUTTON->value,
                        'handle' => 'checkout_button',
                        'name' => 'Checkout Button',
                        'removable' => false,
                        'settings' => ['text' => 'Proceed to Checkout', 'style' => 'primary', 'full_width' => true],
                    ],
                ],
            ],
            // ── Cart Empty ──
            [
                'type' => SectionTypeEnum::CART_EMPTY->value,
                'handle' => 'cart_empty',
                'name' => 'Cart Empty State',
                'description' => 'Displayed when cart has no items.',
                'position' => 22,
                'removable' => true,
                'settings' => [
                    'show_browse_link' => true,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'empty_heading',
                        'name' => 'Empty Heading',
                        'removable' => false,
                        'settings' => ['html' => '<div class="text-center py-12"><h2 class="text-2xl font-bold mb-4">Your cart is empty</h2><p class="text-gray-600 mb-6">Looks like you have not added any items yet.</p></div>'],
                    ],
                    [
                        'type' => BlockTypeEnum::BUTTON->value,
                        'handle' => 'continue_shopping',
                        'name' => 'Continue Shopping Button',
                        'removable' => false,
                        'settings' => ['text' => 'Continue Shopping', 'style' => 'primary', 'url' => '/en/collections/all'],
                    ],
                ],
            ],
            // ── Search Form ──
            [
                'type' => SectionTypeEnum::SEARCH_FORM->value,
                'handle' => 'search_form',
                'name' => 'Search Form',
                'description' => 'Search input and filters trigger.',
                'position' => 30,
                'removable' => true,
                'settings' => [
                    'placeholder' => 'Search products...',
                    'show_filters' => true,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'search_input',
                        'name' => 'Search Input',
                        'removable' => false,
                        'settings' => ['html' => '<div class="relative max-w-2xl mx-auto"><input type="search" placeholder="Search products..." class="w-full px-4 py-3 border rounded-lg" /></div>'],
                    ],
                ],
            ],
            // ── Search Results ──
            [
                'type' => SectionTypeEnum::SEARCH_RESULTS->value,
                'handle' => 'search_results',
                'name' => 'Search Results',
                'description' => 'Search results listing with count.',
                'position' => 31,
                'removable' => true,
                'settings' => [
                    'results_per_page' => 24,
                    'show_pagination' => true,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'results_heading',
                        'name' => 'Results Heading',
                        'removable' => false,
                        'settings' => ['html' => '<p class="text-lg text-gray-600 mb-4"><span class="font-semibold" data-results-count>0</span> results found</p>'],
                    ],
                    [
                        'type' => BlockTypeEnum::TEXT->value,
                        'handle' => 'no_results',
                        'name' => 'No Results Text',
                        'removable' => true,
                        'settings' => ['text' => 'No products found matching your search. Try adjusting your search terms.', 'alignment' => 'center'],
                    ],
                ],
            ],
            // ── Search Filters ──
            [
                'type' => SectionTypeEnum::SEARCH_FILTERS->value,
                'handle' => 'search_filters',
                'name' => 'Search Filters',
                'description' => 'Sidebar filters for search refinement.',
                'position' => 32,
                'removable' => true,
                'settings' => [
                    'show_price_range' => true,
                    'show_categories' => true,
                    'show_brands' => true,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'filter_heading',
                        'name' => 'Filter Heading',
                        'removable' => false,
                        'settings' => ['html' => '<h3 class="font-semibold mb-3">Filters</h3>'],
                    ],
                ],
            ],
            // ── Account Profile ──
            [
                'type' => SectionTypeEnum::ACCOUNT_PROFILE->value,
                'handle' => 'account_profile',
                'name' => 'Account Profile',
                'description' => 'Customer profile information and edit form.',
                'position' => 40,
                'removable' => true,
                'settings' => [
                    'show_name' => true,
                    'show_email' => true,
                    'show_phone' => true,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'profile_heading',
                        'name' => 'Profile Heading',
                        'removable' => false,
                        'settings' => ['html' => '<h1 class="text-2xl font-bold">My Profile</h1>'],
                    ],
                    [
                        'type' => BlockTypeEnum::TEXT->value,
                        'handle' => 'profile_description',
                        'name' => 'Profile Description',
                        'removable' => true,
                        'settings' => ['text' => 'Manage your personal information and preferences.', 'alignment' => 'left'],
                    ],
                ],
            ],
            // ── Account Orders ──
            [
                'type' => SectionTypeEnum::ACCOUNT_ORDERS->value,
                'handle' => 'account_orders',
                'name' => 'Account Orders',
                'description' => 'List of customer orders.',
                'position' => 41,
                'removable' => true,
                'settings' => [
                    'orders_per_page' => 10,
                    'show_pagination' => true,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'orders_heading',
                        'name' => 'Orders Heading',
                        'removable' => false,
                        'settings' => ['html' => '<h1 class="text-2xl font-bold">My Orders</h1>'],
                    ],
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'orders_table',
                        'name' => 'Orders Table',
                        'removable' => true,
                        'settings' => ['html' => '<div class="overflow-x-auto"><table class="w-full"><thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th></tr></thead><tbody data-orders-list></tbody></table></div>'],
                    ],
                    [
                        'type' => BlockTypeEnum::TEXT->value,
                        'handle' => 'no_orders_text',
                        'name' => 'No Orders Text',
                        'removable' => true,
                        'settings' => ['text' => 'You have not placed any orders yet.', 'alignment' => 'center'],
                    ],
                ],
            ],
            // ── Account Addresses ──
            [
                'type' => SectionTypeEnum::ACCOUNT_ADDRESSES->value,
                'handle' => 'account_addresses',
                'name' => 'Account Addresses',
                'description' => 'Saved shipping and billing addresses.',
                'position' => 42,
                'removable' => true,
                'settings' => [
                    'max_addresses' => 10,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'addresses_heading',
                        'name' => 'Addresses Heading',
                        'removable' => false,
                        'settings' => ['html' => '<h1 class="text-2xl font-bold">My Addresses</h1>'],
                    ],
                    [
                        'type' => BlockTypeEnum::TEXT->value,
                        'handle' => 'addresses_description',
                        'name' => 'Addresses Description',
                        'removable' => true,
                        'settings' => ['text' => 'Manage your shipping and billing addresses.', 'alignment' => 'left'],
                    ],
                ],
            ],
            // ── Order Detail ──
            [
                'type' => SectionTypeEnum::ORDER_DETAIL->value,
                'handle' => 'order_detail',
                'name' => 'Order Detail',
                'description' => 'Detailed view of a single order.',
                'position' => 50,
                'removable' => true,
                'settings' => [
                    'show_items' => true,
                    'show_totals' => true,
                    'show_timeline' => true,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'order_heading',
                        'name' => 'Order Heading',
                        'removable' => false,
                        'settings' => ['html' => '<h1 class="text-2xl font-bold">Order Details</h1>'],
                    ],
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'order_info',
                        'name' => 'Order Information',
                        'removable' => false,
                        'settings' => ['html' => '<div class="grid grid-cols-2 gap-4 mb-6" data-order-info></div>'],
                    ],
                ],
            ],
            // ── Order Tracking ──
            [
                'type' => SectionTypeEnum::ORDER_TRACKING->value,
                'handle' => 'order_tracking',
                'name' => 'Order Tracking',
                'description' => 'Track order shipment status.',
                'position' => 51,
                'removable' => true,
                'settings' => [
                    'show_timeline' => true,
                    'show_carrier' => true,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'tracking_heading',
                        'name' => 'Tracking Heading',
                        'removable' => false,
                        'settings' => ['html' => '<h1 class="text-2xl font-bold">Order Tracking</h1>'],
                    ],
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'tracking_timeline',
                        'name' => 'Tracking Timeline',
                        'removable' => false,
                        'settings' => ['html' => '<div class="space-y-4" data-tracking-timeline></div>'],
                    ],
                ],
            ],
            // ── Category Grid ──
            [
                'type' => SectionTypeEnum::CATEGORY_GRID->value,
                'handle' => 'category_grid',
                'name' => 'Category Grid',
                'description' => 'Grid layout of product categories.',
                'position' => 60,
                'removable' => true,
                'settings' => [
                    'columns' => 3,
                    'show_product_count' => true,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'categories_heading',
                        'name' => 'Categories Heading',
                        'removable' => false,
                        'settings' => ['html' => '<h1 class="text-2xl font-bold">Shop by Category</h1>'],
                    ],
                    [
                        'type' => BlockTypeEnum::TEXT->value,
                        'handle' => 'categories_description',
                        'name' => 'Categories Description',
                        'removable' => true,
                        'settings' => ['text' => 'Browse our collection of categories.', 'alignment' => 'center'],
                    ],
                ],
            ],
            // ── Error 404 ──
            [
                'type' => SectionTypeEnum::ERROR_404->value,
                'handle' => 'error_404',
                'name' => '404 Error Page',
                'description' => 'Page not found error content.',
                'position' => 70,
                'removable' => false,
                'settings' => [
                    'show_search' => true,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'error_404_heading',
                        'name' => '404 Heading',
                        'removable' => false,
                        'settings' => ['html' => '<h1 class="text-4xl font-bold text-center">Page Not Found</h1>'],
                    ],
                    [
                        'type' => BlockTypeEnum::TEXT->value,
                        'handle' => 'error_404_text',
                        'name' => '404 Description',
                        'removable' => false,
                        'settings' => ['text' => 'The page you are looking for does not exist or has been moved.', 'alignment' => 'center'],
                    ],
                    [
                        'type' => BlockTypeEnum::BUTTON->value,
                        'handle' => 'back_home_button',
                        'name' => 'Back to Home Button',
                        'removable' => false,
                        'settings' => ['text' => 'Back to Home', 'style' => 'primary', 'url' => '/'],
                    ],
                ],
            ],
            // ── Error 500 ──
            [
                'type' => SectionTypeEnum::ERROR_500->value,
                'handle' => 'error_500',
                'name' => '500 Error Page',
                'description' => 'Server error content.',
                'position' => 71,
                'removable' => false,
                'settings' => [
                    'auto_refresh' => false,
                ],
                'blocks' => [
                    [
                        'type' => BlockTypeEnum::HTML->value,
                        'handle' => 'error_500_heading',
                        'name' => '500 Heading',
                        'removable' => false,
                        'settings' => ['html' => '<h1 class="text-4xl font-bold text-center">Something Went Wrong</h1>'],
                    ],
                    [
                        'type' => BlockTypeEnum::TEXT->value,
                        'handle' => 'error_500_text',
                        'name' => '500 Description',
                        'removable' => false,
                        'settings' => ['text' => 'We encountered an unexpected error. Please try again later.', 'alignment' => 'center'],
                    ],
                    [
                        'type' => BlockTypeEnum::BUTTON->value,
                        'handle' => 'refresh_button',
                        'name' => 'Refresh Button',
                        'removable' => false,
                        'settings' => ['text' => 'Try Again', 'style' => 'primary', 'url' => '#'],
                    ],
                ],
            ],
        ];
    }
}
