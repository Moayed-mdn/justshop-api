<?php

declare(strict_types=1);

namespace Tests\Feature\Theme;

use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\PageTemplate;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ThemeTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private User $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->merchant = User::factory()->merchant()->verified()->create();
        $this->merchant->assignRole(RoleEnum::STORE_ADMIN->value);
        $this->store = Store::factory()->create(['owner_id' => $this->merchant->id]);
        
        $this->merchant->stores()->attach($this->store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $this->merchant = $this->merchant->fresh();
        
        $this->actingAs($this->merchant);
    }

    private function templatesPath(?int $templateId = null, bool $duplicate = false): string
    {
        $path = "/api/v1/merchant/stores/{$this->store->slug}/templates";

        if ($templateId !== null) {
            $path .= "/{$templateId}";
        }

        if ($duplicate) {
            $path .= '/duplicate';
        }

        return $path;
    }

    public function test_list_templates_returns_all_templates_for_store(): void
    {
        PageTemplate::factory()->count(3)->create(['store_id' => $this->store->id]);
        
        // Create template for another store (should not be included)
        $otherStore = Store::factory()->create();
        PageTemplate::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->getJson($this->templatesPath());

        $response->assertStatus(200);
        
        // Should only return templates for this store
        $templates = $response->json('data.data');
        $this->assertCount(3, $templates);
        
        // All should belong to this store
        foreach ($templates as $template) {
            $this->assertEquals($this->store->id, $template['store_id']);
        }
    }

    public function test_list_templates_orders_by_default_then_name(): void
    {
        PageTemplate::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Z Template',
            'is_default' => false,
        ]);
        
        PageTemplate::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'A Template',
            'is_default' => true,
        ]);
        
        PageTemplate::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'B Template',
            'is_default' => false,
        ]);

        $response = $this->getJson($this->templatesPath());

        $response->assertStatus(200);
        $names = collect($response->json('data.data'))->pluck('name')->toArray();
        
        // Default first, then alphabetical
        $this->assertEquals(['A Template', 'B Template', 'Z Template'], $names);
    }

    public function test_create_template_successfully(): void
    {
        $payload = [
            'name' => 'Landing Page',
            'handle' => 'page.landing',
            'type' => 'page',
            'sections' => [
                'header' => ['type' => 'header', 'settings' => ['menu' => 'main-menu']],
                'main' => ['type' => 'page_content'],
            ],
            'section_order' => ['header', 'main'],
            'is_default' => false,
        ];

        $response = $this->postJson($this->templatesPath(), $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Landing Page');
        $response->assertJsonPath('data.handle', 'page.landing');
        
        $this->assertDatabaseHas('page_templates', [
            'store_id' => $this->store->id,
            'name' => 'Landing Page',
            'handle' => 'page.landing',
        ]);
    }

    public function test_create_template_validates_required_fields(): void
    {
        $response = $this->postJson($this->templatesPath(), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'handle', 'type']);
    }

    public function test_create_template_validates_handle_uniqueness_per_store(): void
    {
        PageTemplate::factory()->create([
            'store_id' => $this->store->id,
            'handle' => 'page.unique',
        ]);

        $payload = [
            'name' => 'Test Template',
            'handle' => 'page.unique', // Duplicate
            'type' => 'page',
            'sections' => [],
            'section_order' => [],
        ];

        $response = $this->postJson($this->templatesPath(), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['handle']);
    }

    public function test_create_template_allows_same_handle_for_different_stores(): void
    {
        $otherStore = Store::factory()->create();
        PageTemplate::factory()->create([
            'store_id' => $otherStore->id,
            'handle' => 'page.common',
        ]);

        $payload = [
            'name' => 'Test Template',
            'handle' => 'page.common', // Same as other store
            'type' => 'page',
            'sections' => [],
            'section_order' => [],
        ];

        $response = $this->postJson($this->templatesPath(), $payload);

        $response->assertStatus(201);
    }

    public function test_show_template_returns_template_data(): void
    {
        $template = PageTemplate::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Test Template',
        ]);

        $response = $this->getJson($this->templatesPath($template->id));

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $template->id);
        $response->assertJsonPath('data.name', 'Test Template');
    }

    public function test_show_template_prevents_access_to_other_stores(): void
    {
        $otherStore = Store::factory()->create();
        $template = PageTemplate::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->getJson($this->templatesPath($template->id));

        $response->assertStatus(404);
    }

    public function test_update_template_successfully(): void
    {
        $template = PageTemplate::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Original Name',
        ]);

        $payload = [
            'name' => 'Updated Name',
            'sections' => [
                'header' => ['type' => 'header', 'settings' => ['menu' => 'new-menu']],
            ],
        ];

        $response = $this->putJson($this->templatesPath($template->id), $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated Name');
        
        $this->assertDatabaseHas('page_templates', [
            'id' => $template->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_update_template_validates_handle_uniqueness(): void
    {
        $template1 = PageTemplate::factory()->create([
            'store_id' => $this->store->id,
            'handle' => 'page.one',
        ]);
        
        $template2 = PageTemplate::factory()->create([
            'store_id' => $this->store->id,
            'handle' => 'page.two',
        ]);

        $payload = ['handle' => 'page.one']; // Try to use template1's handle

        $response = $this->putJson($this->templatesPath($template2->id), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['handle']);
    }

    public function test_delete_template_successfully(): void
    {
        $template = PageTemplate::factory()->create([
            'store_id' => $this->store->id,
            'is_default' => false,
        ]);

        $response = $this->deleteJson($this->templatesPath($template->id));

        $response->assertStatus(204);
        $this->assertDatabaseMissing('page_templates', ['id' => $template->id]);
    }

    public function test_delete_template_prevents_deleting_default_template(): void
    {
        $template = PageTemplate::factory()->create([
            'store_id' => $this->store->id,
            'is_default' => true,
        ]);

        $response = $this->deleteJson($this->templatesPath($template->id));

        $response->assertStatus(422);
        $this->assertDatabaseHas('page_templates', ['id' => $template->id]);
    }

    public function test_delete_template_prevents_deleting_template_in_use(): void
    {
        $template = PageTemplate::factory()->create([
            'store_id' => $this->store->id,
            'is_default' => false,
        ]);
        
        // Page using this template
        StoreMarketingPage::factory()->create([
            'store_id' => $this->store->id,
            'page_template_id' => $template->id,
        ]);

        $response = $this->deleteJson($this->templatesPath($template->id));

        $response->assertStatus(422);
        $this->assertDatabaseHas('page_templates', ['id' => $template->id]);
    }

    public function test_duplicate_template_successfully(): void
    {
        $template = PageTemplate::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Original Template',
            'handle' => 'page.original',
            'sections' => [
                'header' => ['type' => 'header', 'settings' => ['menu' => 'main-menu']],
            ],
        ]);

        $response = $this->postJson($this->templatesPath($template->id, true));

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Original Template (Copy)');
        $this->assertNotEquals($template->handle, $response->json('data.handle'));
        
        // Should have copied sections
        $this->assertEquals($template->sections, $response->json('data.sections'));
    }

    public function test_duplicate_template_creates_unique_handle(): void
    {
        $template = PageTemplate::factory()->create([
            'store_id' => $this->store->id,
            'handle' => 'page.original',
        ]);

        $response = $this->postJson($this->templatesPath($template->id, true));

        $response->assertStatus(201);
        
        $newHandle = $response->json('data.handle');
        $this->assertNotEquals('page.original', $newHandle);
        $this->assertStringContainsString('copy', $newHandle);
    }

    public function test_cannot_access_templates_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $template = PageTemplate::factory()->create(['store_id' => $otherStore->id]);

        $endpoints = [
            'GET' => $this->templatesPath($template->id),
            'PUT' => $this->templatesPath($template->id),
            'DELETE' => $this->templatesPath($template->id),
            'POST' => $this->templatesPath($template->id, true),
        ];

        foreach ($endpoints as $method => $url) {
            $response = $this->json($method, $url, $method === 'PUT' ? ['name' => 'Test'] : []);
            $response->assertStatus(404);
        }
    }
}
