<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BootstrapBoundaryNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_shadow_read_reports_zero_drift_for_current_contract(): void
    {
        config()->set('migration.bootstrap.shadow_read', true);
        config()->set('migration.bootstrap.v2_enabled', false);
        config()->set('migration.rbac.dual_resolve', true);

        Log::spy();

        [$user, $store] = $this->createMerchantWithStore([
            'product.view',
            'product.update',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/users/auth/bootstrap');

        $response->assertOk()
            ->assertJsonPath('data.active_store.id', $store->id)
            ->assertJsonPath('data.permissions', ['product.update', 'product.view']);

        Log::shouldHaveReceived('info')->with(
            'bootstrap.parity.checked',
            Mockery::on(fn (array $context): bool => ($context['drift_count'] ?? null) === 0
                && ($context['permission_payload_parity'] ?? false) === true
                && ($context['active_store_parity'] ?? false) === true),
        )->atLeast()->once();
    }

    public function test_bootstrap_contract_snapshot_remains_unchanged(): void
    {
        [$user, $store] = $this->createMerchantWithStore([
            'product.view',
        ], [
            'name' => 'Northwind Store',
            'slug' => 'northwind-store',
            'domain' => 'northwind.test',
            'currency' => 'USD',
            'timezone' => 'UTC',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/users/auth/bootstrap');

        $response->assertOk();

        $this->assertSame([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => null,
                'is_email_verified' => true,
            ],
            'stores' => [[
                'id' => $store->id,
                'name' => 'Northwind Store',
                'slug' => 'northwind-store',
                'domain' => 'northwind.test',
                'currency' => 'USD',
                'role' => StoreRoleEnum::STORE_ADMIN->value,
            ]],
            'active_store' => [
                'id' => $store->id,
                'name' => 'Northwind Store',
                'slug' => 'northwind-store',
                'domain' => 'northwind.test',
                'currency' => 'USD',
                'role' => StoreRoleEnum::STORE_ADMIN->value,
            ],
            'onboarding' => [
                'step' => OnboardingStepEnum::COMPLETED->value,
                'is_completed' => true,
            ],
            'permissions' => ['product.view'],
            'capabilities' => [],
            'config' => [
                'supported_locales' => config('app.supported_locales', ['en']),
                'default_currency' => config('app.default_currency', 'USD'),
                'timezone' => config('app.timezone', 'UTC'),
            ],
            'actor_context' => 'merchant',
        ], $response->json('data'));
    }

    public function test_bootstrap_does_not_leak_inaccessible_active_store(): void
    {
        $user = User::factory()->merchant()->create([
            'last_active_store_id' => null,
        ]);

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

        $response = $this->actingAs($user)->getJson('/api/v1/users/auth/bootstrap');

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
}
