<?php

declare(strict_types=1);

namespace Tests\Feature\Theme;

use App\Enums\Billing\BillingAccountStatusEnum;
use App\Enums\Entitlement\EntitlementStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Enums\Theme\TemplateTypeEnum;
use App\Models\BillingAccount;
use App\Models\Store;
use App\Models\StoreEntitlementSnapshot;
use App\Models\Theme\Theme;
use App\Models\Theme\ThemeSection;
use App\Models\Theme\ThemeTemplate;
use App\Models\User;
use App\Services\Storefront\Runtime\RuntimeCacheService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SystemTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Theme $theme;

    private User $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // SQLite (used for tests) lacks the GREATEST() function that
        // StoreObserver relies on when adjusting BillingAccount store counts.
        Store::unsetEventDispatcher();

        $this->merchant = User::factory()->merchant()->verified()->create();
        // SystemTemplatePolicy requires both the store-membership pivot "admin"
        // role AND the Spatie THEME_* permissions (via RoleEnum::STORE_ADMIN);
        // an admin without the Spatie role is still denied by canView/canManage.
        $this->merchant->assignRole(RoleEnum::STORE_ADMIN->value);
        $this->store = Store::factory()->create(['owner_id' => $this->merchant->id]);
        $this->merchant->stores()->attach($this->store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $this->merchant = $this->merchant->fresh();

        // Template write endpoints are gated by the `subscription.active`
        // middleware (EnsureActiveSubscription -> FeatureGateService), which
        // requires a StoreEntitlementSnapshot granting write access.
        $this->grantActiveEntitlement($this->store);

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

    /**
     * Give a store an entitlement snapshot that satisfies the
     * `subscription.active` middleware used on template write endpoints.
     */
    private function grantActiveEntitlement(Store $store): StoreEntitlementSnapshot
    {
        $billingAccount = BillingAccount::create([
            'owner_user_id' => $store->owner_id,
            'billing_email' => 'billing+'.$store->owner_id.'@example.test',
            'legal_name' => 'Test Billing Account',
            'country_code' => 'US',
            'default_currency' => 'USD',
            'status' => BillingAccountStatusEnum::ACTIVE,
            'trial_used' => false,
            'stores_count' => 1,
            'stores_max' => null,
            'metadata' => [],
        ]);

        return StoreEntitlementSnapshot::create([
            'store_id' => $store->id,
            'billing_account_id' => $billingAccount->id,
            'entitlement_status' => EntitlementStatusEnum::ENTITLED,
            'features' => [],
            'products_count' => 0,
            'refreshed_at' => now(),
        ]);
    }

    private function templatePath(?int $templateId = null): string
    {
        $path = "/api/v1/merchant/stores/{$this->store->slug}/themes/{$this->theme->slug}/system-templates";
        if ($templateId !== null) {
            $path .= "/{$templateId}";
        }

        return $path;
    }

    public function test_list_templates_returns_all_templates_for_theme(): void
    {
        ThemeTemplate::create([
            'theme_id' => $this->theme->id,
            'name' => 'Cart Template',
            'handle' => 'system.cart',
            'type' => TemplateTypeEnum::CART,
            'is_default' => true,
        ]);
        ThemeTemplate::create([
            'theme_id' => $this->theme->id,
            'name' => 'Search Template',
            'handle' => 'system.search',
            'type' => TemplateTypeEnum::SEARCH,
            'is_default' => false,
        ]);

        $otherTheme = Theme::create([
            'store_id' => $this->store->id,
            'name' => 'Other Theme',
            'slug' => 'other',
            'is_active' => true,
            'is_published' => true,
            'published_at' => now(),
            'settings' => [],
            'metadata' => [],
        ]);
        ThemeTemplate::create([
            'theme_id' => $otherTheme->id,
            'name' => 'Other Template',
            'handle' => 'other.template',
            'type' => TemplateTypeEnum::CART,
            'is_default' => false,
        ]);

        $response = $this->getJson($this->templatePath());

        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $this->assertCount(2, $templates);
        foreach ($templates as $t) {
            $this->assertEquals($this->theme->id, $t['theme_id']);
            $this->assertEquals($this->theme->slug, $t['theme_slug']);
            $this->assertEquals($this->theme->slug, $t['theme_identifier']);
        }
    }

    public function test_create_template_successfully(): void
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

        $payload = [
            'name' => 'Cart Template',
            'handle' => 'system.cart',
            'type' => 'cart',
            'description' => 'Template for cart page',
            'section_ids' => [$section->id],
            'settings' => ['layout' => 'full'],
            'is_default' => true,
        ];

        $response = $this->postJson($this->templatePath(), $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Cart Template');
        $response->assertJsonPath('data.handle', 'system.cart');
        $response->assertJsonPath('data.type', 'cart');

        $this->assertDatabaseHas('theme_templates', [
            'theme_id' => $this->theme->id,
            'name' => 'Cart Template',
            'handle' => 'system.cart',
        ]);
    }

    public function test_create_template_validates_required_fields(): void
    {
        $response = $this->postJson($this->templatePath(), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'handle', 'type']);
    }

    public function test_create_template_validates_type_is_system_page(): void
    {
        $payload = [
            'name' => 'Invalid',
            'handle' => 'invalid.type',
            'type' => 'custom',
        ];

        $response = $this->postJson($this->templatePath(), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    public function test_create_template_validates_handle_uniqueness_per_theme(): void
    {
        ThemeTemplate::create([
            'theme_id' => $this->theme->id,
            'name' => 'Existing',
            'handle' => 'system.cart',
            'type' => TemplateTypeEnum::CART,
            'is_default' => false,
        ]);

        $payload = [
            'name' => 'Duplicate',
            'handle' => 'system.cart',
            'type' => 'cart',
        ];

        $response = $this->postJson($this->templatePath(), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['handle']);
    }

    public function test_create_template_allows_same_handle_for_different_themes(): void
    {
        $otherTheme = Theme::create([
            'store_id' => $this->store->id,
            'name' => 'Other',
            'slug' => 'other',
            'is_active' => true,
            'is_published' => true,
            'published_at' => now(),
            'settings' => [],
            'metadata' => [],
        ]);

        ThemeTemplate::create([
            'theme_id' => $otherTheme->id,
            'name' => 'Existing',
            'handle' => 'system.cart',
            'type' => TemplateTypeEnum::CART,
            'is_default' => false,
        ]);

        $payload = [
            'name' => 'Cart Template',
            'handle' => 'system.cart',
            'type' => 'cart',
        ];

        $response = $this->postJson($this->templatePath(), $payload);

        $response->assertStatus(201);
    }

    public function test_show_template_returns_template_with_sections(): void
    {
        $template = ThemeTemplate::create([
            'theme_id' => $this->theme->id,
            'name' => 'Cart Template',
            'handle' => 'system.cart',
            'type' => TemplateTypeEnum::CART,
            'is_default' => true,
        ]);

        $section = ThemeSection::create([
            'theme_id' => $this->theme->id,
            'name' => 'Header',
            'type' => 'header',
            'handle' => 'header',
            'settings' => ['sticky' => true],
            'position' => 0,
            'is_enabled' => true,
        ]);

        $template->sections()->attach($section->id, ['position' => 0]);

        $response = $this->getJson($this->templatePath($template->id));

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $template->id);
        $response->assertJsonPath('data.name', 'Cart Template');
        $response->assertJsonPath('data.type', 'cart');
        $response->assertJsonPath('data.sections.0.id', $section->id);
    }

    public function test_update_template_name_and_description(): void
    {
        $template = ThemeTemplate::create([
            'theme_id' => $this->theme->id,
            'name' => 'Original Name',
            'handle' => 'system.cart',
            'type' => TemplateTypeEnum::CART,
            'is_default' => false,
        ]);

        $payload = [
            'name' => 'Updated Cart Template',
            'description' => 'Updated description',
        ];

        $response = $this->putJson($this->templatePath($template->id), $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated Cart Template');

        $this->assertDatabaseHas('theme_templates', [
            'id' => $template->id,
            'name' => 'Updated Cart Template',
            'description' => 'Updated description',
        ]);
    }

    public function test_update_template_with_section_overrides(): void
    {
        $template = ThemeTemplate::create([
            'theme_id' => $this->theme->id,
            'name' => 'Cart Template',
            'handle' => 'system.cart',
            'type' => TemplateTypeEnum::CART,
            'is_default' => false,
        ]);

        $section1 = ThemeSection::create([
            'theme_id' => $this->theme->id,
            'name' => 'Header',
            'type' => 'header',
            'handle' => 'header',
            'settings' => ['sticky' => true],
            'position' => 0,
            'is_enabled' => true,
        ]);

        $section2 = ThemeSection::create([
            'theme_id' => $this->theme->id,
            'name' => 'Footer',
            'type' => 'footer',
            'handle' => 'footer',
            'settings' => ['columns' => 4],
            'position' => 2,
            'is_enabled' => true,
        ]);

        $payload = [
            'section_ids' => [$section1->id, $section2->id],
            'section_overrides' => [
                $section1->id => ['sticky' => false, 'background' => 'dark'],
                $section2->id => ['columns' => 3],
            ],
        ];

        $response = $this->putJson($this->templatePath($template->id), $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('theme_template_sections', [
            'template_id' => $template->id,
            'section_id' => $section1->id,
            'position' => 0,
        ]);

        $this->assertDatabaseHas('theme_template_sections', [
            'template_id' => $template->id,
            'section_id' => $section2->id,
            'position' => 1,
        ]);
    }

    public function test_update_template_invalidates_storefront_runtime_cache(): void
    {
        $template = ThemeTemplate::create([
            'theme_id' => $this->theme->id,
            'name' => 'Cart Template',
            'handle' => 'system.cart',
            'type' => TemplateTypeEnum::CART,
            'is_default' => false,
        ]);

        /** @var RuntimeCacheService $runtimeCache */
        $runtimeCache = app(RuntimeCacheService::class);
        $pageCacheKey = $runtimeCache->key($this->store, 'en', 'page', '/');
        $registryKey = 'storefront_runtime_registry:tenant:'.$this->store->slug;

        Cache::put($pageCacheKey, ['page' => ['id' => 'cached']], now()->addHour());
        Cache::forever($registryKey, [
            $pageCacheKey => [
                'key' => $pageCacheKey,
                'artifact' => 'page',
            ],
        ]);

        $response = $this->putJson($this->templatePath($template->id), [
            'name' => 'Updated Cart Template',
        ]);

        $response->assertStatus(200);
        $this->assertFalse(Cache::has($pageCacheKey));
    }

    public function test_update_template_makes_previous_default_false(): void
    {
        ThemeTemplate::create([
            'theme_id' => $this->theme->id,
            'name' => 'Default Cart',
            'handle' => 'system.cart.default',
            'type' => TemplateTypeEnum::CART,
            'is_default' => true,
        ]);

        $newDefault = ThemeTemplate::create([
            'theme_id' => $this->theme->id,
            'name' => 'New Default Cart',
            'handle' => 'system.cart.new',
            'type' => TemplateTypeEnum::CART,
            'is_default' => false,
        ]);

        $payload = ['is_default' => true];

        $response = $this->putJson($this->templatePath($newDefault->id), $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('theme_templates', [
            'id' => $newDefault->id,
            'is_default' => true,
        ]);
    }

    public function test_delete_template_successfully(): void
    {
        $template = ThemeTemplate::create([
            'theme_id' => $this->theme->id,
            'name' => 'Cart Template',
            'handle' => 'system.cart',
            'type' => TemplateTypeEnum::CART,
            'is_default' => false,
        ]);

        $response = $this->deleteJson($this->templatePath($template->id));

        $response->assertStatus(204);
        $this->assertSoftDeleted('theme_templates', ['id' => $template->id]);
    }

    public function test_cannot_access_templates_from_different_theme(): void
    {
        $otherTheme = Theme::create([
            'store_id' => $this->store->id,
            'name' => 'Other',
            'slug' => 'other',
            'is_active' => true,
            'is_published' => true,
            'published_at' => now(),
            'settings' => [],
            'metadata' => [],
        ]);

        $template = ThemeTemplate::create([
            'theme_id' => $otherTheme->id,
            'name' => 'Other Template',
            'handle' => 'other.template',
            'type' => TemplateTypeEnum::CART,
            'is_default' => false,
        ]);

        $response = $this->getJson($this->templatePath($template->id));

        $response->assertStatus(404);
    }

    public function test_list_templates_rejects_numeric_theme_id_after_slug_cutover(): void
    {
        ThemeTemplate::create([
            'theme_id' => $this->theme->id,
            'name' => 'Cart Template',
            'handle' => 'system.cart',
            'type' => TemplateTypeEnum::CART,
            'is_default' => true,
        ]);

        $response = $this->getJson(
            "/api/v1/merchant/stores/{$this->store->slug}/themes/{$this->theme->id}/system-templates"
        );

        $response->assertNotFound();
    }
}
