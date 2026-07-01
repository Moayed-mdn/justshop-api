<?php

declare(strict_types=1);

namespace Tests\Feature\Cms\Marketing;

use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Enums\Cms\Marketing\MarketingPageTemplateEnum;
use App\Enums\Cms\Marketing\MarketingSectionTypeEnum;
use App\Enums\PermissionEnum;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StoreMarketingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed all permissions/roles so givePermissionTo() works
        $this->seed(PermissionSeeder::class);

        // Clear Spatie's in-memory permission cache after seeding
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function merchantWithStore(): array
    {
        /** @var User $user */
        $user = User::factory()->merchant()->create();

        /** @var Store $store */
        $store = Store::factory()->create(['owner_id' => $user->id]);

        // Attach with 'staff' role — no marketing permissions by default.
        // Tests that need permissions call givePermissions() which creates a
        // custom role with exactly the requested permissions and updates the
        // pivot so LegacyPermissionAuthority resolves them correctly.
        $user->stores()->attach($store->id, ['role' => 'staff']);

        return [$user, $store];
    }

    /**
     * Authenticate as a merchant using the correct Sanctum merchant guard.
     * This satisfies the identity.route:merchant_admin middleware which
     * rejects requests where the resolved guard is a fallback (web).
     */
    private function asMerchant(User $user): static
    {
        Sanctum::actingAs($user, ['*'], 'merchant');
        return $this;
    }

    /**
     * Grant exactly the specified permissions to a user by:
     * 1. Creating a unique test role with only those permissions.
     * 2. Updating the user's store pivot to use that role.
     *
     * This is necessary because LegacyPermissionAuthority resolves permissions
     * from the pivot role — direct givePermissionTo() on the user is bypassed
     * when currentStore is bound in the container.
     */
    private function givePermissions(User $user, array $permissions): void
    {
        // Build a deterministic role name from the sorted permission list
        $roleName = 'test_role_' . md5(implode(',', array_map(
            fn ($p) => is_string($p) ? $p : (string) $p,
            $permissions
        )));

        $role = \Spatie\Permission\Models\Role::firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web'],
        );

        // Ensure all requested permissions exist and are assigned to the role
        $permissionNames = array_map(fn ($p) => is_string($p) ? $p : (string) $p, $permissions);
        foreach ($permissionNames as $permName) {
            \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => $permName, 'guard_name' => 'web'],
            );
        }
        $role->syncPermissions($permissionNames);

        // Update every store membership pivot to use this custom role
        foreach ($user->stores as $store) {
            $user->stores()->updateExistingPivot($store->id, ['role' => $roleName]);
        }

        // Flush Spatie cache so the new role/permissions are visible immediately
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title'  => ['en' => 'Summer Sale 2026', 'ar' => 'تخفيضات الصيف 2026'],
            'slug'   => ['en' => 'summer-sale-2026', 'ar' => 'summer-sale-2026-ar'],
            'status' => MarketingPageStatusEnum::DRAFT->value,
        ], $overrides);
    }

    private function baseUrl(Store $store): string
    {
        return "/api/v1/merchant/stores/{$store->id}/cms/pages";
    }

    // ── Authorization ─────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_list_pages(): void
    {
        [, $store] = $this->merchantWithStore();

        // No Sanctum — raw unauthenticated request
        $this->getJson($this->baseUrl($store))
            ->assertStatus(401);
    }

    public function test_user_without_permission_cannot_list_pages(): void
    {
        [$user, $store] = $this->merchantWithStore();
        // Authenticated via merchant guard but no permissions granted
        $this->asMerchant($user)
            ->getJson($this->baseUrl($store))
            ->assertStatus(403);
    }

    public function test_user_not_member_of_store_cannot_list_pages(): void
    {
        /** @var User $outsider */
        $outsider = User::factory()->merchant()->create();
        $this->givePermissions($outsider, [PermissionEnum::MARKETING_STORE_VIEW]);

        [, $store] = $this->merchantWithStore();

        // outsider has the permission but is NOT a member of $store
        $this->asMerchant($outsider)
            ->getJson($this->baseUrl($store))
            ->assertStatus(403);
    }

    public function test_member_with_view_permission_can_list_pages(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_VIEW]);

        $this->asMerchant($user)
            ->getJson($this->baseUrl($store))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    // ── Create ────────────────────────────────────────────────────────────

    public function test_can_create_a_draft_page(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $response = $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.title.en', 'Summer Sale 2026')
            ->assertJsonPath('data.store_id', $store->id);

        $this->assertDatabaseHas('store_marketing_pages', [
            'store_id' => $store->id,
            'status'   => 'draft',
        ]);
    }

    public function test_create_requires_title_and_slug(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), ['status' => 'draft'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'slug']);
    }

    public function test_create_rejects_invalid_status(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload(['status' => 'invalid']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_create_rejects_platform_template(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'template' => MarketingPageTemplateEnum::HOME->value, // platform-only
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['template']);
    }

    public function test_create_accepts_store_template(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'template' => MarketingPageTemplateEnum::CAMPAIGN->value,
            ]))
            ->assertStatus(201)
            ->assertJsonPath('data.template', 'campaign');
    }

    public function test_create_allows_null_page_template_id(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $response = $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'page_template_id' => null,
            ]));

        $response->assertStatus(201)
            ->assertJsonPath('data.page_template_id', null);

        $this->assertDatabaseHas('store_marketing_pages', [
            'id' => $response->json('data.id'),
            'page_template_id' => null,
        ]);
    }

    public function test_create_with_sections_persists_sections(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $payload = $this->validPayload([
            'sections' => [
                [
                    'section_type' => MarketingSectionTypeEnum::HERO->value,
                    'identifier'   => 'hero-main',
                    'title'        => ['en' => 'Big Sale', 'ar' => 'تخفيض كبير'],
                    'sort_order'   => 0,
                    'is_active'    => true,
                ],
            ],
        ]);

        $response = $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $payload);

        $response->assertStatus(201);

        $pageId = $response->json('data.id');

        $this->assertDatabaseHas('store_marketing_sections', [
            'store_marketing_page_id' => $pageId,
            'section_type'            => 'hero',
            'identifier'              => 'hero-main',
            'store_id'                => $store->id,
        ]);
    }

    public function test_create_accepts_frontend_type_alias_for_section_type(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        // Frontend sends 'type' instead of 'section_type' — both are accepted
        $payload = $this->validPayload([
            'sections' => [
                [
                    'type'       => MarketingSectionTypeEnum::FEATURES->value,
                    'identifier' => 'features-block',
                    'sort_order' => 0,
                ],
            ],
        ]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $payload)
            ->assertStatus(201);
    }

    // ── Slug uniqueness ───────────────────────────────────────────────────

    public function test_slug_must_be_unique_within_store(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $payload = $this->validPayload();

        $this->asMerchant($user)->postJson($this->baseUrl($store), $payload)->assertStatus(201);

        // Second request with same slug — must fail uniqueness
        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug.en']);
    }

    public function test_same_slug_is_allowed_across_different_stores(): void
    {
        [$user1, $store1] = $this->merchantWithStore();
        [$user2, $store2] = $this->merchantWithStore();

        $this->givePermissions($user1, [PermissionEnum::MARKETING_STORE_CREATE]);
        $this->givePermissions($user2, [PermissionEnum::MARKETING_STORE_CREATE]);

        $payload = $this->validPayload();

        $this->asMerchant($user1)->postJson($this->baseUrl($store1), $payload)->assertStatus(201);
        $this->asMerchant($user2)->postJson($this->baseUrl($store2), $payload)->assertStatus(201);
    }

    // ── Tenant isolation ──────────────────────────────────────────────────

    public function test_merchant_cannot_see_pages_from_another_store(): void
    {
        [$user1, $store1] = $this->merchantWithStore();
        [$user2, $store2] = $this->merchantWithStore();

        $this->givePermissions($user1, [PermissionEnum::MARKETING_STORE_VIEW, PermissionEnum::MARKETING_STORE_CREATE]);
        $this->givePermissions($user2, [PermissionEnum::MARKETING_STORE_VIEW]);

        // user1 creates a page in store1
        $this->asMerchant($user1)
            ->postJson($this->baseUrl($store1), $this->validPayload())
            ->assertStatus(201);

        // user2 tries to list store1's pages — must 403 (not a member)
        $this->asMerchant($user2)
            ->getJson($this->baseUrl($store1))
            ->assertStatus(403);
    }

    public function test_merchant_cannot_show_page_from_another_store(): void
    {
        [$user1, $store1] = $this->merchantWithStore();
        [$user2, $store2] = $this->merchantWithStore();

        $this->givePermissions($user1, [PermissionEnum::MARKETING_STORE_VIEW, PermissionEnum::MARKETING_STORE_CREATE]);
        $this->givePermissions($user2, [PermissionEnum::MARKETING_STORE_VIEW]);

        $createResponse = $this->asMerchant($user1)
            ->postJson($this->baseUrl($store1), $this->validPayload())
            ->assertStatus(201);

        $pageId = $createResponse->json('data.id');

        // user2 tries to access store1's page via store2's URL — must 404
        // (repository scopes by store_id so the page is invisible)
        $this->asMerchant($user2)
            ->getJson($this->baseUrl($store2) . "/{$pageId}")
            ->assertStatus(404);
    }

    // ── Update ────────────────────────────────────────────────────────────

    public function test_can_update_a_page(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_UPDATE,
        ]);

        $createResponse = $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload())
            ->assertStatus(201);

        $pageId = $createResponse->json('data.id');

        $this->asMerchant($user)
            ->putJson($this->baseUrl($store) . "/{$pageId}", $this->validPayload([
                'title' => ['en' => 'Updated Title', 'ar' => 'عنوان محدث'],
            ]))
            ->assertOk()
            ->assertJsonPath('data.title.en', 'Updated Title');
    }

    public function test_update_slug_uniqueness_excludes_self(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_UPDATE,
        ]);

        $createResponse = $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload())
            ->assertStatus(201);

        $pageId = $createResponse->json('data.id');

        // Updating with the same slug must not fail uniqueness (self-exclusion)
        $this->asMerchant($user)
            ->putJson($this->baseUrl($store) . "/{$pageId}", $this->validPayload([
                'title' => ['en' => 'New Title', 'ar' => 'عنوان جديد'],
            ]))
            ->assertOk();
    }

    public function test_update_allows_clearing_page_template_id(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_UPDATE,
        ]);

        $createResponse = $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'page_template_id' => null,
            ]))
            ->assertStatus(201);

        $pageId = $createResponse->json('data.id');

        $this->asMerchant($user)
            ->putJson($this->baseUrl($store) . "/{$pageId}", $this->validPayload([
                'page_template_id' => null,
            ]))
            ->assertOk()
            ->assertJsonPath('data.page_template_id', null);

        $this->assertDatabaseHas('store_marketing_pages', [
            'id' => $pageId,
            'page_template_id' => null,
        ]);
    }

    // ── Delete ────────────────────────────────────────────────────────────

    public function test_can_soft_delete_a_page(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_DELETE,
        ]);

        $createResponse = $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload())
            ->assertStatus(201);

        $pageId = $createResponse->json('data.id');

        $this->asMerchant($user)
            ->deleteJson($this->baseUrl($store) . "/{$pageId}")
            ->assertOk();

        $this->assertSoftDeleted('store_marketing_pages', ['id' => $pageId]);
    }

    // ── Publish workflow ──────────────────────────────────────────────────

    public function test_can_publish_a_draft_page(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_PUBLISH,
        ]);

        $createResponse = $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload())
            ->assertStatus(201);

        $pageId = $createResponse->json('data.id');

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store) . "/{$pageId}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('store_marketing_pages', [
            'id'     => $pageId,
            'status' => 'published',
        ]);
    }

    public function test_can_unpublish_a_published_page(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_PUBLISH,
        ]);

        $createResponse = $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload())
            ->assertStatus(201);

        $pageId = $createResponse->json('data.id');

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store) . "/{$pageId}/publish")
            ->assertOk();

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store) . "/{$pageId}/unpublish")
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_publish_requires_publish_permission(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_UPDATE, // no publish permission
        ]);

        $createResponse = $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload())
            ->assertStatus(201);

        $pageId = $createResponse->json('data.id');

        // Publish request must be denied — FormRequest::authorize() checks
        // MARKETING_STORE_PUBLISH which this user does not have
        $this->asMerchant($user)
            ->postJson($this->baseUrl($store) . "/{$pageId}/publish")
            ->assertStatus(403);
    }

    // ── Scheduled publishing ──────────────────────────────────────────────

    public function test_scheduled_status_requires_future_published_at(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'status'       => MarketingPageStatusEnum::SCHEDULED->value,
                'published_at' => now()->subDay()->toDateTimeString(), // past date
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['published_at']);
    }

    public function test_scheduled_status_with_future_date_is_accepted(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'status'       => MarketingPageStatusEnum::SCHEDULED->value,
                'published_at' => now()->addDays(7)->toDateTimeString(),
            ]))
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'scheduled');
    }

    // ── Response shape ────────────────────────────────────────────────────

    public function test_response_includes_expected_fields(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $response = $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'seo' => [
                    'meta_title'       => ['en' => 'SEO Title'],
                    'meta_description' => ['en' => 'SEO Desc'],
                ],
            ]))
            ->assertStatus(201);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'store_id',
                'title',
                'slug',
                'status',
                'published_at',
                'template',
                'sort_order',
                'seo',
                'created_at',
                'updated_at',
                'sections',
            ],
        ]);
    }

    public function test_sections_are_included_in_show_response(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_VIEW,
        ]);

        $createResponse = $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload([
                'sections' => [
                    [
                        'section_type' => 'hero',
                        'identifier'   => 'hero-main',
                        'sort_order'   => 0,
                    ],
                ],
            ]))
            ->assertStatus(201);

        $pageId = $createResponse->json('data.id');

        $this->asMerchant($user)
            ->getJson($this->baseUrl($store) . "/{$pageId}")
            ->assertOk()
            ->assertJsonPath('data.sections.0.section_type', 'hero')
            ->assertJsonPath('data.sections.0.identifier', 'hero-main');
    }

    // ── Localization ──────────────────────────────────────────────────────

    public function test_title_and_slug_are_stored_as_locale_maps(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_CREATE]);

        $response = $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload())
            ->assertStatus(201);

        $response->assertJsonPath('data.title.en', 'Summer Sale 2026')
            ->assertJsonPath('data.title.ar', 'تخفيضات الصيف 2026')
            ->assertJsonPath('data.slug.en', 'summer-sale-2026');
    }

    // ── Soft delete isolation ─────────────────────────────────────────────

    public function test_deleted_page_is_not_returned_in_list(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [
            PermissionEnum::MARKETING_STORE_CREATE,
            PermissionEnum::MARKETING_STORE_DELETE,
            PermissionEnum::MARKETING_STORE_VIEW,
        ]);

        $createResponse = $this->asMerchant($user)
            ->postJson($this->baseUrl($store), $this->validPayload())
            ->assertStatus(201);

        $pageId = $createResponse->json('data.id');

        $this->asMerchant($user)
            ->deleteJson($this->baseUrl($store) . "/{$pageId}")
            ->assertOk();

        $listResponse = $this->asMerchant($user)
            ->getJson($this->baseUrl($store))
            ->assertOk();

        $ids = collect($listResponse->json('data'))->pluck('id')->toArray();
        $this->assertNotContains($pageId, $ids);
    }
}
