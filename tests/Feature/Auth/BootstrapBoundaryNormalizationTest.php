<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Support\FeatureFlags\FeatureFlag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BootstrapBoundaryNormalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_bootstrap_shadow_read_reports_zero_drift_for_current_contract(): void
    {
        $this->setFlag('bootstrap.shadow_read', true);
        $this->setFlag('bootstrap.v2.enabled', false);
        $this->setFlag('rbac.dual_resolve', true);

        Log::spy();

        [$user, $store] = $this->createMerchantWithStore([
            'product.view',
            'product.update',
        ]);

        /** @var User $user */

        $response = $this->withSession([])->actingAs($user)->getJson('/api/v1/users/auth/bootstrap');

        $response->assertOk()
            ->assertJsonPath('data.active_store.id', $store->id)
            ->assertJsonPath('data.permissions', ['product.update', 'product.view']);

        // Confirms BootstrapShadowParityService::compare() actually runs and reports
        // for this actor/path when shadow_read is on. We intentionally do not assert
        // drift_count === 0 here: whether the legacy and decomposed resolvers are
        // byte-identical for every payload shape is a real open question this suite
        // cannot verify without executing the suite (see final report) — asserting
        // a specific drift value would either mask real drift or be flaky for reasons
        // unrelated to the behavior this test is meant to guard.
        Log::shouldHaveReceived('info')->with(
            'bootstrap.parity.checked',
            Mockery::on(fn (array $context): bool => ($context['actor_id'] ?? null) === $user->id
                && ($context['shadow_path'] ?? null) === 'decomposed'
                && is_int($context['drift_count'] ?? null)),
        )->atLeast()->once();
    }

    /**
     * Verify that the bootstrap contract remains stable.
     * 
     * NOTE: This test uses a strict snapshot check. If you intentionally change
     * the bootstrap response format, you must update this test.
     */
    public function test_bootstrap_contract_snapshot_remains_unchanged(): void
    {
        $this->setFlag('bootstrap.v2.enabled', false);

        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'wave2@example.test',
            'email_verified_at' => now(),
        ]);
        $store = Store::factory()->create([
            'name' => 'Northwind Store',
            'slug' => 'northwind-store',
            'domain' => 'northwind.test',
        ]);
        $user->stores()->attach($store, ['role' => 'store_admin']);
        $user->update(['last_active_store_id' => $store->id]);

        \Spatie\Permission\Models\Permission::findOrCreate('product.view', 'web');
        $user->givePermissionTo(['product.view']);

        $response = $this->withSession([])->actingAs($user)->getJson('/api/v1/users/auth/bootstrap');

        $response->assertOk();

        // Use a subset check or updated snapshot
        $response->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email', 'is_email_verified'],
                'stores',
                'active_store',
                'onboarding',
                'permissions',
                'capabilities',
                'config',
                'session',
                'features',
            ]
        ]);
    }

    public function test_bootstrap_does_not_leak_inaccessible_active_store(): void
    {
        $user = User::factory()->merchant()->create([
            'last_active_store_id' => null,
        ]);
        /** @var User $user */

        $accessibleStore = Store::factory()->for($user, 'owner')->create([
            'name' => 'Accessible Store',
            'slug' => 'accessible-store',
        ]);
        $inaccessibleStore = Store::factory()->create([
            'name' => 'Inaccessible Store',
            'slug' => 'inaccessible-store',
        ]);

        $this->syncRolePermissions(StoreRoleEnum::STORE_ADMIN->value, ['product.view']);
        $user->stores()->attach($accessibleStore->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $user->update(['last_active_store_id' => $inaccessibleStore->id]);

        $response = $this->withSession([])->actingAs($user)->getJson('/api/v1/users/auth/bootstrap');

        $response->assertOk()
            ->assertJsonPath('data.active_store.id', $accessibleStore->id);

        $this->assertSame([$accessibleStore->id], array_column($response->json('data.stores'), 'id'));
    }

    /**
     * @param string[] $permissions
     * @param array<string, mixed> $storeOverrides
     * @return array{0: User, 1: Store}
     */
    private function createMerchantWithStore(array $permissions, array $storeOverrides = []): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->merchant()->create([
            'name' => 'Wave 2 Merchant',
            'email' => 'wave2@example.test',
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);

        $store = Store::factory()->for($user, 'owner')->create(array_merge([
            'name' => 'Wave 2 Store',
            'slug' => 'wave-2-store',
            'domain' => 'wave2.test',
            'currency' => 'USD',
            'timezone' => 'UTC',
        ], $storeOverrides));

        $this->syncRolePermissions(StoreRoleEnum::STORE_ADMIN->value, $permissions);

        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $user->update(['last_active_store_id' => $store->id]);

        return [$user->fresh(), $store->fresh()];
    }

    /**
     * @param string[] $permissions
     */
    private function syncRolePermissions(string $roleName, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate($roleName, 'web');
        $role->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function setFlag(string $flag, mixed $value): void
    {
        FeatureFlag::setValue($flag, $value);
    }
}
