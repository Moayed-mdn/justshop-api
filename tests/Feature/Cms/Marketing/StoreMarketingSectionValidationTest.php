<?php

declare(strict_types=1);

namespace Tests\Feature\Cms\Marketing;

use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Enums\PermissionEnum;
use App\Models\PageTemplate;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Covers validation behaviour of the store marketing page create/update
 * endpoints that is NOT exercised by StoreMarketingPageTest.php:
 * per-section-type content rules (Cms/Marketing/Store/Admin/{Create,Update}
 * StoreMarketingPageRequest::validateSectionContent()), slug format, and
 * page_template_id existence.
 */
class StoreMarketingSectionValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function merchantWithStore(): array
    {
        $user = User::factory()->merchant()->create();

        $store = Store::factory()->create(['owner_id' => $user->id]);

        $user->stores()->attach($store->id, ['role' => 'staff']);

        return [$user, $store];
    }

    private function asMerchant(User $user): static
    {
        Sanctum::actingAs($user, ['*'], 'merchant');

        return $this;
    }

    private function givePermissions(User $user, array $permissions): void
    {
        $roleName = 'test_role_' . md5(implode(',', array_map(
            fn ($p) => is_string($p) ? $p : (string) $p,
            $permissions
        )));

        $role = \Spatie\Permission\Models\Role::firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web'],
        );

        $permissionNames = array_map(fn ($p) => is_string($p) ? $p : (string) $p, $permissions);
        foreach ($permissionNames as $permName) {
            \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => $permName, 'guard_name' => 'web'],
            );
        }
        $role->syncPermissions($permissionNames);

        foreach ($user->stores as $store) {
            $user->stores()->updateExistingPivot($store->id, ['role' => $roleName]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function baseUrl(Store $store): string
    {
        return "/api/v1/merchant/stores/{$store->id}/cms/pages";
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title'  => ['en' => 'Summer Sale 2026', 'ar' => 'تخفيضات الصيف 2026'],
            'slug'   => ['en' => 'summer-sale-2026', 'ar' => 'summer-sale-2026-ar'],
            'status' => MarketingPageStatusEnum::DRAFT->value,
        ], $overrides);
    }

    private function createPage(User $user, Store $store, array $overrides = []): int
    {
        $response = $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload($overrides))
            ->assertStatus(201);

        return (int) $response->json('data.id');
    }

    // ── section_type / type acceptance ─────────────────────────────────────

    public function test_create_rejects_unknown_section_type(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'sections' => [
                    ['section_type' => 'not_a_real_type', 'identifier' => 'x', 'sort_order' => 0],
                ],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sections.0.section_type']);
    }

    public function test_create_rejects_section_missing_both_type_keys(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'sections' => [
                    ['identifier' => 'x', 'sort_order' => 0],
                ],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sections.0.section_type', 'sections.0.type']);
    }

    // ── CTA section content ─────────────────────────────────────────────────

    public function test_create_rejects_cta_section_missing_label_and_url(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'sections' => [
                    [
                        'section_type' => 'cta',
                        'identifier'   => 'cta-main',
                        'sort_order'   => 0,
                        'content'      => ['ctas' => [['label' => '', 'url' => '']]],
                    ],
                ],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sections.0.content.ctas.0']);
    }

    public function test_create_accepts_cta_section_with_label_and_url(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'sections' => [
                    [
                        'section_type' => 'cta',
                        'identifier'   => 'cta-main',
                        'sort_order'   => 0,
                        'content'      => ['ctas' => [['label' => 'Shop now', 'url' => 'https://example.com/shop']]],
                    ],
                ],
            ]))
            ->assertStatus(201);
    }

    // ── Video section content ────────────────────────────────────────────────

    public function test_create_rejects_video_section_with_non_string_url(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'sections' => [
                    [
                        'section_type' => 'video',
                        'identifier'   => 'video-main',
                        'sort_order'   => 0,
                        'content'      => ['video_url' => ['not', 'a', 'string']],
                    ],
                ],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sections.0.content.video_url']);
    }

    // ── Content section: create vs update behave differently ────────────────
    // CreateStoreMarketingPageRequest::validateContentSectionContent()
    // requires a non-empty `body` key specifically. Update's version only
    // requires the content array to be non-empty (any shape). This test
    // pins down that real, current difference.

    public function test_create_rejects_content_section_without_body_key(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'sections' => [
                    [
                        'section_type' => 'content',
                        'identifier'   => 'content-main',
                        'sort_order'   => 0,
                        // Non-empty content, but no 'body' key — Create requires 'body'.
                        'content'      => ['promises' => ['Fast shipping']],
                    ],
                ],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sections.0.content.body']);
    }

    public function test_create_accepts_content_section_with_body_key(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'sections' => [
                    [
                        'section_type' => 'content',
                        'identifier'   => 'content-main',
                        'sort_order'   => 0,
                        'content'      => ['body' => ['en' => 'Some body text']],
                    ],
                ],
            ]))
            ->assertStatus(201);
    }

    public function test_update_accepts_content_section_without_body_key_if_non_empty(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_UPDATE,
        ]);

        $pageId = $this->createPage($user, $store);

        // Unlike create, update's validateContentSectionContent() only
        // requires the content array to be non-empty — it does not require
        // a 'body' key specifically.
        $this->asMerchant($user)
            ->putJson($this->baseUrl($store) . "/{$pageId}", $this->validPayload([
                'sections' => [
                    [
                        'section_type' => 'content',
                        'identifier'   => 'content-main',
                        'sort_order'   => 0,
                        'content'      => ['promises' => ['Fast shipping']],
                    ],
                ],
            ]))
            ->assertStatus(200);
    }

    public function test_update_rejects_content_section_with_completely_empty_content(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_UPDATE,
        ]);

        $pageId = $this->createPage($user, $store);

        $this->asMerchant($user)
            ->putJson($this->baseUrl($store) . "/{$pageId}", $this->validPayload([
                'sections' => [
                    [
                        'section_type' => 'content',
                        'identifier'   => 'content-main',
                        'sort_order'   => 0,
                        'content'      => [],
                    ],
                ],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sections.0.content']);
    }

    // ── Slug format ───────────────────────────────────────────────────────

    public function test_create_rejects_slug_with_uppercase_and_spaces(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'slug' => ['en' => 'Not A Valid Slug', 'ar' => 'summer-sale-ar'],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug.en']);
    }

    // ── page_template_id existence ───────────────────────────────────────

    public function test_create_rejects_page_template_id_that_does_not_exist(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'page_template_id' => 999999,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['page_template_id']);
    }

    public function test_create_accepts_page_template_id_that_exists(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $template = PageTemplate::factory()->create(['store_id' => $store->id]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'page_template_id' => $template->id,
            ]))
            ->assertStatus(201)
            ->assertJsonPath('data.page_template_id', $template->id);
    }

    // ── SEO canonical URL ─────────────────────────────────────────────────

    public function test_create_rejects_invalid_seo_canonical_url(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'seo' => ['canonical_url' => 'not-a-url'],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['seo.canonical_url']);
    }
}
