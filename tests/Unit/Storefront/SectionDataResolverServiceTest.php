<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Enums\Cms\Marketing\MarketingPageTemplateEnum;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\Navigation\NavigationMenu;
use App\Models\Navigation\NavigationMenuItem;
use App\Models\Store;
use App\Services\Theme\SectionDataResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionDataResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    private SectionDataResolverService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // SQLite (used for tests) lacks the GREATEST() function that
        // StoreObserver relies on when adjusting BillingAccount store counts.
        Store::unsetEventDispatcher();

        $this->service = app(SectionDataResolverService::class);
    }

    /**
     * NavigationMenu has no factory in database/factories, so menus/items are
     * created directly via the real models (matching the columns defined in
     * the navigation_menus / navigation_menu_items migrations).
     */
    private function createMenuWithRootItem(Store $store, string $handle, string $label, string $url): NavigationMenu
    {
        $menu = NavigationMenu::create([
            'store_id' => $store->id,
            'name' => ucfirst($handle),
            'handle' => $handle,
            'is_active' => true,
        ]);

        NavigationMenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => null,
            'label' => $label,
            'type' => 'custom',
            'url' => $url,
            'target' => '_self',
            'position' => 0,
            'is_active' => true,
        ]);

        return $menu;
    }

    public function test_resolves_header_section_data_with_navigation_and_store_identity(): void
    {
        $store = Store::factory()->create(['name' => 'Alpha Store']);
        $this->createMenuWithRootItem($store, 'main-menu', 'About', '/about');

        $result = $this->service->resolveSectionData(
            'header',
            ['menu' => 'main-menu'],
            $store,
            'en'
        );

        $this->assertSame('header', $result['type']);
        $this->assertSame(['menu' => 'main-menu'], $result['settings']);
        $this->assertSame('Alpha Store', $result['data']['store_name']);
        $this->assertCount(1, $result['data']['navigation']);
        $this->assertSame('About', $result['data']['navigation'][0]['label']);
        $this->assertSame('/about', $result['data']['navigation'][0]['path']);
    }

    public function test_resolves_footer_section_data_with_navigation_and_copyright_year(): void
    {
        $store = Store::factory()->create(['name' => 'Beta Store']);
        $this->createMenuWithRootItem($store, 'footer-menu', 'Contact', '/contact');

        $result = $this->service->resolveSectionData(
            'footer',
            ['menu' => 'footer-menu'],
            $store,
            'en'
        );

        $this->assertSame('footer', $result['type']);
        $this->assertSame('Beta Store', $result['data']['store_name']);
        $this->assertSame(date('Y'), $result['data']['copyright_year']);
        $this->assertCount(1, $result['data']['navigation']);
        $this->assertSame('Contact', $result['data']['navigation'][0]['label']);
    }

    public function test_footer_section_defaults_to_footer_menu_handle_when_settings_omit_menu(): void
    {
        $store = Store::factory()->create();
        $this->createMenuWithRootItem($store, 'footer-menu', 'Terms', '/terms');

        // No 'menu' key in settings at all: resolveFooterData() must fall
        // back to the 'footer-menu' handle rather than returning no items.
        $result = $this->service->resolveSectionData('footer', [], $store, 'en');

        $this->assertCount(1, $result['data']['navigation']);
        $this->assertSame('Terms', $result['data']['navigation'][0]['label']);
    }

    public function test_resolves_footer_minimal_section_data_shape(): void
    {
        $store = Store::factory()->create();
        $this->createMenuWithRootItem($store, 'footer-menu', 'Privacy', '/privacy');

        $result = $this->service->resolveSectionData('footer-minimal', [], $store, 'en');

        $this->assertSame('footer-minimal', $result['type']);
        $this->assertArrayHasKey('navigation', $result['data']);
        // footer-minimal deliberately omits store_name/copyright_year, unlike 'footer'.
        $this->assertArrayNotHasKey('store_name', $result['data']);
        $this->assertCount(1, $result['data']['navigation']);
    }

    public function test_resolves_footer_legal_section_using_legal_footer_menu_handle(): void
    {
        $store = Store::factory()->create();
        $this->createMenuWithRootItem($store, 'legal-footer', 'Cookies', '/cookies');

        $result = $this->service->resolveSectionData('footer-legal', [], $store, 'en');

        $this->assertSame('footer-legal', $result['type']);
        $this->assertCount(1, $result['data']['navigation']);
        $this->assertSame('Cookies', $result['data']['navigation'][0]['label']);
    }

    public function test_resolves_page_content_section_data_localized_to_requested_locale(): void
    {
        $store = Store::factory()->create();
        $page = StoreMarketingPage::create([
            'store_id' => $store->id,
            'title' => ['en' => 'About Us', 'ar' => 'من نحن'],
            'slug' => 'about-us',
            'content' => ['en' => '<p>Our story</p>', 'ar' => '<p>قصتنا</p>'],
            'status' => MarketingPageStatusEnum::PUBLISHED->value,
            'template' => MarketingPageTemplateEnum::GENERIC->value,
        ]);

        $result = $this->service->resolveSectionData('page_content', [], $store, 'ar', $page);

        $this->assertSame('page_content', $result['type']);
        $this->assertSame('من نحن', $result['data']['title']);
        $this->assertSame('<p>قصتنا</p>', $result['data']['content']);
    }

    public function test_page_content_section_returns_empty_strings_when_no_page_is_bound(): void
    {
        $store = Store::factory()->create();

        // The 'page' argument is optional (used for template previews without
        // a bound marketing page); resolvePageContentData() must degrade to
        // empty strings rather than erroring when it is null.
        $result = $this->service->resolveSectionData('page_content', [], $store, 'en', null);

        $this->assertSame('', $result['data']['title']);
        $this->assertSame('', $result['data']['content']);
    }

    public function test_resolves_hero_section_data_from_settings(): void
    {
        $store = Store::factory()->create();
        $settings = [
            'heading' => 'Welcome',
            'text' => 'To our store',
            'image_url' => 'https://cdn.example.test/hero.jpg',
        ];

        $result = $this->service->resolveSectionData('hero', $settings, $store, 'en');

        $this->assertSame('hero', $result['type']);
        $this->assertSame('Welcome', $result['data']['heading']);
        $this->assertSame('To our store', $result['data']['text']);
        $this->assertSame('https://cdn.example.test/hero.jpg', $result['data']['image_url']);
    }

    public function test_hero_section_falls_back_to_default_heading_when_settings_are_empty(): void
    {
        $store = Store::factory()->create();

        $result = $this->service->resolveSectionData('hero', [], $store, 'en');

        $this->assertSame('Welcome', $result['data']['heading']);
        $this->assertSame('', $result['data']['text']);
        $this->assertNull($result['data']['image_url']);
    }

    public function test_resolves_announcement_bar_localizing_text_fields(): void
    {
        $store = Store::factory()->create();
        $settings = ['text' => 'Free shipping today', 'offer_text' => '20% off'];

        $result = $this->service->resolveSectionData('announcement_bar', $settings, $store, 'en');

        $this->assertSame('announcement_bar', $result['type']);
        $this->assertSame('Free shipping today', $result['settings']['text']);
        $this->assertSame('20% off', $result['settings']['offer_text']);
        $this->assertSame([], $result['data']);
    }

    public function test_falls_back_gracefully_for_unknown_section_types(): void
    {
        $store = Store::factory()->create();
        $settings = ['foo' => 'bar'];

        $result = $this->service->resolveSectionData('some-unrecognized-type', $settings, $store, 'en');

        // resolveGenericSectionData() echoes the type/settings back and
        // returns an empty 'data' payload rather than throwing.
        $this->assertSame('some-unrecognized-type', $result['type']);
        $this->assertSame($settings, $result['settings']);
        $this->assertSame([], $result['data']);
    }

    public function test_header_navigation_is_empty_array_when_referenced_menu_does_not_exist(): void
    {
        $store = Store::factory()->create();
        // Deliberately no NavigationMenu created for 'main-menu'.

        $result = $this->service->resolveSectionData('header', ['menu' => 'main-menu'], $store, 'en');

        $this->assertIsArray($result['data']['navigation']);
        $this->assertSame([], $result['data']['navigation']);
    }

    public function test_header_navigation_excludes_inactive_items(): void
    {
        $store = Store::factory()->create();
        $menu = $this->createMenuWithRootItem($store, 'main-menu', 'Active Link', '/active');

        NavigationMenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => null,
            'label' => 'Hidden Link',
            'type' => 'custom',
            'url' => '/hidden',
            'target' => '_self',
            'position' => 1,
            'is_active' => false,
        ]);

        $result = $this->service->resolveSectionData('header', ['menu' => 'main-menu'], $store, 'en');

        $this->assertCount(1, $result['data']['navigation']);
        $this->assertSame('Active Link', $result['data']['navigation'][0]['label']);
    }

    public function test_header_navigation_is_scoped_to_the_requested_store(): void
    {
        $storeA = Store::factory()->create();
        $storeB = Store::factory()->create();

        $this->createMenuWithRootItem($storeA, 'main-menu', 'Store A Link', '/a');
        $this->createMenuWithRootItem($storeB, 'main-menu', 'Store B Link', '/b');

        $result = $this->service->resolveSectionData('header', ['menu' => 'main-menu'], $storeA, 'en');

        $this->assertCount(1, $result['data']['navigation']);
        $this->assertSame('Store A Link', $result['data']['navigation'][0]['label']);
    }
}
