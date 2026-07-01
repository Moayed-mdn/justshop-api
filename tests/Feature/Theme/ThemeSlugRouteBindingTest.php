<?php

declare(strict_types=1);

namespace Tests\Feature\Theme;

use App\Models\Store;
use App\Models\Theme\Theme;
use App\Models\Theme\ThemeBlock;
use App\Models\Theme\ThemeBlockInstance;
use App\Models\Theme\ThemeSection;
use App\Models\Theme\ThemeSectionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeSlugRouteBindingTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private Theme $theme;
    private User $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = User::factory()->merchant()->verified()->create();
        $this->store = Store::factory()->create(['owner_id' => $this->merchant->id]);
        $this->merchant->stores()->attach($this->store->id, ['role' => 'store_admin']);

        $this->theme = Theme::create([
            'store_id' => $this->store->id,
            'name' => 'Default Theme',
            'slug' => 'default',
            'version' => '1.0.0',
            'is_active' => true,
            'is_published' => true,
            'published_at' => now(),
            'settings' => ['primary_color' => '#000000'],
            'metadata' => [],
        ]);

        $this->actingAs($this->merchant);
    }

    private function themePath(string $themeSegment): string
    {
        return "/api/v1/merchant/stores/{$this->store->slug}/themes/{$themeSegment}";
    }

    public function test_section_groups_list_resolves_theme_by_slug(): void
    {
        ThemeSectionGroup::create([
            'theme_id' => $this->theme->id,
            'name' => 'Header',
            'handle' => 'header',
            'sections' => [],
            'order' => [],
        ]);

        $response = $this->getJson($this->themePath($this->theme->slug).'/section-groups');

        $response->assertOk()
            ->assertJsonPath('data.0.theme_id', $this->theme->id)
            ->assertJsonPath('data.0.theme_slug', $this->theme->slug)
            ->assertJsonPath('data.0.theme_identifier', $this->theme->slug);
    }

    public function test_section_groups_list_rejects_numeric_theme_id_after_slug_cutover(): void
    {
        ThemeSectionGroup::create([
            'theme_id' => $this->theme->id,
            'name' => 'Header',
            'handle' => 'header',
            'sections' => [],
            'order' => [],
        ]);

        $this->getJson($this->themePath((string) $this->theme->id).'/section-groups')
            ->assertNotFound();
    }

    public function test_blocks_list_resolves_theme_by_slug(): void
    {
        $section = ThemeSection::create([
            'theme_id' => $this->theme->id,
            'name' => 'Header',
            'type' => 'header',
            'handle' => 'header',
            'settings' => ['sticky' => true],
            'position' => 0,
            'is_enabled' => true,
        ]);

        $block = ThemeBlock::create([
            'section_id' => $section->id,
            'name' => 'Logo',
            'type' => 'logo',
            'handle' => 'logo',
            'settings' => ['width' => 120],
            'content' => [],
            'position' => 0,
            'is_enabled' => true,
            'is_removable' => false,
        ]);

        $response = $this->getJson($this->themePath($this->theme->slug)."/sections/{$section->id}/blocks");

        $response->assertOk()
            ->assertJsonPath('data.0.id', $block->id)
            ->assertJsonPath('data.0.section_id', $section->id);
    }

    public function test_blocks_list_rejects_numeric_theme_id_after_slug_cutover(): void
    {
        $section = ThemeSection::create([
            'theme_id' => $this->theme->id,
            'name' => 'Header',
            'type' => 'header',
            'handle' => 'header',
            'settings' => ['sticky' => true],
            'position' => 0,
            'is_enabled' => true,
        ]);

        $this->getJson($this->themePath((string) $this->theme->id)."/sections/{$section->id}/blocks")
            ->assertNotFound();
    }

    public function test_block_instances_list_resolves_theme_by_slug(): void
    {
        $section = ThemeSection::create([
            'theme_id' => $this->theme->id,
            'name' => 'Hero',
            'type' => 'hero',
            'handle' => 'hero',
            'settings' => ['title' => 'Welcome'],
            'position' => 0,
            'is_enabled' => true,
        ]);

        $blockInstance = ThemeBlockInstance::create([
            'container_type' => $section->getMorphClass(),
            'container_id' => $section->id,
            'name' => 'Hero Copy',
            'type' => 'text',
            'settings' => ['align' => 'center'],
            'content' => ['text' => 'Hello world'],
            'position' => 0,
            'is_enabled' => true,
        ]);

        $response = $this->getJson($this->themePath($this->theme->slug)."/sections/{$section->id}/block-instances");

        $response->assertOk()
            ->assertJsonPath('data.0.id', $blockInstance->id)
            ->assertJsonPath('data.0.container_id', $section->id);
    }

    public function test_block_instances_list_rejects_numeric_theme_id_after_slug_cutover(): void
    {
        $section = ThemeSection::create([
            'theme_id' => $this->theme->id,
            'name' => 'Hero',
            'type' => 'hero',
            'handle' => 'hero',
            'settings' => ['title' => 'Welcome'],
            'position' => 0,
            'is_enabled' => true,
        ]);

        $this->getJson($this->themePath((string) $this->theme->id)."/sections/{$section->id}/block-instances")
            ->assertNotFound();
    }
}
