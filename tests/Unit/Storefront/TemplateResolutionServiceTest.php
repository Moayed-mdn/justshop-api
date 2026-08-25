<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\PageTemplate;
use App\Models\PageTemplateOverride;
use App\Models\Store;
use App\Services\Theme\TemplateResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateResolutionServiceTest extends TestCase
{
    use RefreshDatabase;

    private TemplateResolutionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Store::unsetEventDispatcher();
        $this->service = app(TemplateResolutionService::class);
    }

    public function test_resolves_template_from_page(): void
    {
        $store = Store::factory()->create();
        $template = PageTemplate::factory()->create([
            'store_id' => $store->id,
            'name' => 'Test Template',
            'sections' => [
                'header' => ['type' => 'header', 'settings' => ['menu' => 'main-menu']],
                'main' => ['type' => 'page_content'],
            ],
            'section_order' => ['header', 'main'],
        ]);

        $page = StoreMarketingPage::factory()->create([
            'store_id' => $store->id,
            'page_template_id' => $template->id,
        ]);

        $resolved = $this->service->resolveTemplate($page);

        $this->assertEquals($template->id, $resolved->id);
        $this->assertEquals($template->name, $resolved->name);
        $this->assertArrayHasKey('header', $resolved->sections);
        $this->assertEquals(['header', 'main'], $resolved->sectionOrder);
    }

    public function test_resolves_template_with_page_overrides(): void
    {
        $store = Store::factory()->create();
        $template = PageTemplate::factory()->create([
            'store_id' => $store->id,
            'sections' => [
                'header' => ['type' => 'header', 'settings' => ['menu' => 'main-menu']],
                'footer' => ['type' => 'footer', 'settings' => ['menu' => 'footer-menu']],
            ],
            'section_order' => ['header', 'footer'],
        ]);

        $page = StoreMarketingPage::factory()->create([
            'store_id' => $store->id,
            'page_template_id' => $template->id,
        ]);

        // Create page-specific override for footer
        PageTemplateOverride::create([
            'page_id' => $page->id,
            'section_id' => 'footer',
            'settings' => ['menu' => 'minimal-footer'],
        ]);

        $resolved = $this->service->resolveTemplate($page);

        // Header should have original menu
        $this->assertEquals('main-menu', $resolved->sections['header']['settings']['menu']);

        // Footer should have overridden menu
        $this->assertEquals('minimal-footer', $resolved->sections['footer']['settings']['menu']);
    }

    public function test_falls_back_to_default_template_when_none_assigned(): void
    {
        $store = Store::factory()->create();
        $defaultTemplate = PageTemplate::factory()->create([
            'store_id' => $store->id,
            'name' => 'Default Page',
            'type' => 'page',
            'is_default' => true,
        ]);

        $page = StoreMarketingPage::factory()->create([
            'store_id' => $store->id,
            'page_template_id' => null, // No template assigned
        ]);

        $resolved = $this->service->resolveTemplate($page);

        $this->assertEquals($defaultTemplate->id, $resolved->id);
        $this->assertEquals('Default Page', $resolved->name);
    }

    public function test_override_merges_with_template_settings(): void
    {
        $store = Store::factory()->create();
        $template = PageTemplate::factory()->create([
            'store_id' => $store->id,
            'sections' => [
                'header' => [
                    'type' => 'header',
                    'settings' => [
                        'menu' => 'main-menu',
                        'show_search' => true,
                        'logo_position' => 'left',
                    ],
                ],
            ],
            'section_order' => ['header'],
        ]);

        $page = StoreMarketingPage::factory()->create([
            'store_id' => $store->id,
            'page_template_id' => $template->id,
        ]);

        // Override only show_search, keep other settings
        PageTemplateOverride::create([
            'page_id' => $page->id,
            'section_id' => 'header',
            'settings' => ['show_search' => false],
        ]);

        $resolved = $this->service->resolveTemplate($page);
        $headerSettings = $resolved->sections['header']['settings'];

        // Original settings preserved
        $this->assertEquals('main-menu', $headerSettings['menu']);
        $this->assertEquals('left', $headerSettings['logo_position']);

        // Overridden setting applied
        $this->assertFalse($headerSettings['show_search']);
    }

    public function test_resolved_template_provides_helper_methods(): void
    {
        $store = Store::factory()->create();
        $template = PageTemplate::factory()->create([
            'store_id' => $store->id,
            'sections' => [
                'header' => ['type' => 'header', 'settings' => []],
                'main' => ['type' => 'page_content', 'settings' => []],
            ],
            'section_order' => ['header', 'main'],
        ]);

        $page = StoreMarketingPage::factory()->create([
            'store_id' => $store->id,
            'page_template_id' => $template->id,
        ]);

        $resolved = $this->service->resolveTemplate($page);

        $this->assertNotNull($resolved->getSection('header'));
        $this->assertEquals('header', $resolved->getSectionType('header'));
        $this->assertEquals([], $resolved->getSectionSettings('header'));
        $this->assertNull($resolved->getSection('nonexistent'));
    }
}
