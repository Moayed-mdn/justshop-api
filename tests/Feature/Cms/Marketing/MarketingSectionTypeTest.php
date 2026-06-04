<?php

declare(strict_types=1);

namespace Tests\Feature\Cms\Marketing;

use App\Enums\Cms\Marketing\MarketingSectionTypeEnum;
use App\Enums\PermissionEnum;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MarketingSectionTypeTest extends TestCase
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

    private function endpointUrl(Store $store): string
    {
        return "/api/v1/merchant/stores/{$store->id}/cms/section-types";
    }

    // ── Test 1: HTTP 200 ────────────────────────────────────

    public function test_authenticated_user_with_permission_can_list_section_types(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_VIEW]);

        $this->asMerchant($user)
            ->getJson($this->endpointUrl($store))
            ->assertOk();
    }

    // ── Test 2: All enum cases returned ─────────────────────

    public function test_returns_all_enum_cases(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_VIEW]);

        $response = $this->asMerchant($user)
            ->getJson($this->endpointUrl($store))
            ->assertOk();

        $returnedValues = collect($response->json('data'))->pluck('value')->sort()->values()->toArray();

        $expectedValues = collect(MarketingSectionTypeEnum::cases())
            ->map(fn (MarketingSectionTypeEnum $case) => $case->value)
            ->sort()
            ->values()
            ->toArray();

        $this->assertSame($expectedValues, $returnedValues);
    }

    // ── Test 3: Response structure ──────────────────────────

    public function test_response_structure_contains_value_and_label(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_VIEW]);

        $response = $this->asMerchant($user)
            ->getJson($this->endpointUrl($store))
            ->assertOk();

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'value',
                    'label',
                ],
            ],
        ]);
    }

    // ── Test 4: Synchronized with enum ──────────────────────

    public function test_values_are_dynamically_derived_from_enum(): void
    {
        [$user, $store] = $this->merchantWithStore();
        $this->givePermissions($user, [PermissionEnum::MARKETING_STORE_VIEW]);

        $response = $this->asMerchant($user)
            ->getJson($this->endpointUrl($store))
            ->assertOk();

        $returned = $response->json('data');

        $this->assertCount(count(MarketingSectionTypeEnum::cases()), $returned);

        foreach (MarketingSectionTypeEnum::cases() as $case) {
            $match = collect($returned)->firstWhere('value', $case->value);
            $this->assertNotNull($match, "Missing enum case: {$case->value}");
            $this->assertSame($case->label(), $match['label']);
        }
    }

    // ── Test 5: Authorization ───────────────────────────────

    public function test_unauthenticated_user_cannot_access_section_types(): void
    {
        [, $store] = $this->merchantWithStore();

        $this->getJson($this->endpointUrl($store))
            ->assertStatus(401);
    }

    public function test_user_without_permission_cannot_access_section_types(): void
    {
        [$user, $store] = $this->merchantWithStore();

        $this->asMerchant($user)
            ->getJson($this->endpointUrl($store))
            ->assertStatus(403);
    }

    public function test_user_not_member_of_store_cannot_access_section_types(): void
    {
        $outsider = User::factory()->merchant()->create();
        $this->givePermissions($outsider, [PermissionEnum::MARKETING_STORE_VIEW]);

        [, $store] = $this->merchantWithStore();

        $this->asMerchant($outsider)
            ->getJson($this->endpointUrl($store))
            ->assertStatus(403);
    }

    // ── Tenant isolation ────────────────────────────────────

    public function test_section_types_are_returned_for_valid_store(): void
    {
        [$user1, $store1] = $this->merchantWithStore();
        [$user2, $store2] = $this->merchantWithStore();

        $this->givePermissions($user1, [PermissionEnum::MARKETING_STORE_VIEW]);
        $this->givePermissions($user2, [PermissionEnum::MARKETING_STORE_VIEW]);

        // Both users should see the same section types (they are global)
        $response1 = $this->asMerchant($user1)
            ->getJson($this->endpointUrl($store1))
            ->assertOk();

        $response2 = $this->asMerchant($user2)
            ->getJson($this->endpointUrl($store2))
            ->assertOk();

        $this->assertSame(
            collect($response1->json('data'))->pluck('value')->sort()->values()->toArray(),
            collect($response2->json('data'))->pluck('value')->sort()->values()->toArray(),
        );
    }
}
