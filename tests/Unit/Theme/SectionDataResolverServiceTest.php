<?php

declare(strict_types=1);

namespace Tests\Unit\Theme;

use App\Models\Cms\Marketing\Store\Store\NavigationMenu;
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
        $this->service = app(SectionDataResolverService::class);
    }

    public function test_resolves_header_section_data(): void
    {
        $store = Store::factory()->create();
        $menu = NavigationMenu::factory()->create([
            'store_id' => $store->id,
            'handle' => 'main-menu',
            'name' => 'Main Menu',
        ]);

        $settings = ['menu' => 'main-menu'];
        $locale = 'en';

        $data = $this->service->resolveSectionData('header', $settings, $locale, $store);

        $this->assertArrayHasKey('navigation', $data);
        $this->assertIsArray($data['navigation']);
    }

    public function test_resolves_footer_section_data(): void
    {
        $store = Store::factory()->create();
        $menu = NavigationMenu::factory()->create([
            'store_id' => $store->id,
            'handle' => 'footer-menu',
            'name' => 'Footer Menu',
        ]);

        $settings = ['menu' => 'footer-menu'];
        $locale = 'en';

        $data = $this->service->resolveSectionData('footer', $settings, $locale, $store);

        $this->assertArrayHasKey('navigation', $data);
        $this->assertArrayHasKey('store', $data);
        $this->assertIsArray($data['navigation']);
    }

    public function test_resolves_page_content_section_data(): void
    {
        $store = Store::factory()->create();
        $settings = [
            'page_title' => 'About Us',
            'page_content' => '<p>This is our story</p>',
        ];
        $locale = 'en';

        $data = $this->service->resolveSectionData('page_content', $settings, $locale, $store);

        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('content', $data);
    }

    public function test_resolves_hero_section_data(): void
    {
        $store = Store::factory()->create();
        $settings = [
            'heading' => 'Welcome',
            'subheading' => 'To our store',
            'image' => 'hero.jpg',
        ];
        $locale = 'en';

        $data = $this->service->resolveSectionData('hero', $settings, $locale, $store);

        $this->assertArrayHasKey('heading', $data);
        $this->assertArrayHasKey('subheading', $data);
        $this->assertArrayHasKey('image', $data);
    }

    public function test_falls_back_gracefully_for_unknown_section_types(): void
    {
        $store = Store::factory()->create();
        $settings = ['foo' => 'bar'];
        $locale = 'en';

        $data = $this->service->resolveSectionData('unknown_type', $settings, $locale, $store);

        // Should return settings as-is for unknown types
        $this->assertIsArray($data);
    }

    public function test_handles_missing_menu_gracefully(): void
    {
        $store = Store::factory()->create();
        $settings = ['menu' => 'nonexistent-menu'];
        $locale = 'en';

        $data = $this->service->resolveSectionData('header', $settings, $locale, $store);

        $this->assertArrayHasKey('navigation', $data);
        $this->assertEmpty($data['navigation']); // Empty array when menu not found
    }
}
