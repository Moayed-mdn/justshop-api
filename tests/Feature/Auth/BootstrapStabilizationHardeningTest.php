<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Enums\RoleEnum;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BootstrapStabilizationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_bootstrap_parity_matches_legacy_and_records_timing_and_parity_counters(): void
    {
        config()->set('migration.bootstrap.shadow_read', true);
        config()->set('migration.bootstrap.v2_enabled', false);

        Log::spy();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('platform.stores.view', 'web');
        Permission::findOrCreate('platform.settings.manage', 'web');

        $superAdminRole = Role::findOrCreate(RoleEnum::SUPER_ADMIN->value, 'web');
        $superAdminRole->syncPermissions(['platform.stores.view', 'platform.settings.manage']);

        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Wave 2 Super Admin',
            'email' => 'super-admin@example.test',
            'last_active_store_id' => null,
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);

        $firstStore = Store::factory()->create(['name' => 'First Active Store']);
        $secondStore = Store::factory()->create(['name' => 'Second Active Store']);

        $response = $this->actingAs($superAdmin)->getJson('/api/v1/users/auth/bootstrap');

        $response->assertOk()
            ->assertJsonPath('data.actor_context', 'super_admin')
            ->assertJsonPath('data.onboarding.step', OnboardingStepEnum::COMPLETED->value)
            ->assertJsonPath('data.onboarding.is_completed', true)
            ->assertJsonPath('data.active_store.id', $firstStore->id)
            ->assertJsonPath('data.permissions', ['platform.settings.manage', 'platform.stores.view']);

        $this->assertSame([$firstStore->id, $secondStore->id], array_column($response->json('data.stores'), 'id'));

        Log::shouldHaveReceived('info')->with(
            'bootstrap.parity.checked',
            Mockery::on(fn (array $context): bool => ($context['drift_count'] ?? null) === 0
                && ($context['stores_parity'] ?? false) === true
                && ($context['onboarding_parity'] ?? false) === true
                && ($context['actor_context_parity'] ?? false) === true
                && ($context['permission_payload_parity'] ?? false) === true),
        )->atLeast()->once();

        Log::shouldHaveReceived('info')->with(
            'bootstrap.parity.counter',
            Mockery::on(fn (array $context): bool => ($context['parity_counters']['matched_sections'] ?? null) === 6
                && ($context['parity_counters']['total_sections'] ?? null) === 6),
        )->atLeast()->once();

        Log::shouldHaveReceived('info')->with(
            'bootstrap.dependencies.profiled',
            Mockery::on(fn (array $context): bool => ($context['store_count'] ?? null) === 2
                && ($context['permission_count'] ?? null) === 2
                && isset($context['bootstrap_payload_size_growth']['payload_size_bytes'])),
        )->atLeast()->once();

        Log::shouldHaveReceived('info')->with(
            'bootstrap.resolver.timed',
            Mockery::on(fn (array $context): bool => ($context['resolver'] ?? null) === 'BootstrapStoreResolver'
                && isset($context['elapsed_ms'])
                && isset($context['elapsed_bucket'])),
        )->atLeast()->once();
    }

    public function test_verified_merchant_with_zero_stores_has_bootstrap_parity(): void
    {
        $user = User::factory()->merchant()->verified()->create([
            'onboarding_step' => OnboardingStepEnum::CREATE_STORE,
            'last_active_store_id' => null,
        ]);

        $this->assertBootstrapParity($user, function (array $data): void {
            $this->assertSame('merchant', $data['actor_context']);
            $this->assertSame([], $data['stores']);
            $this->assertNull($data['active_store']);
            $this->assertSame([], $data['permissions']);
            $this->assertSame(OnboardingStepEnum::CREATE_STORE->value, $data['onboarding']['step']);
            $this->assertFalse($data['onboarding']['is_completed']);
        });
    }

    public function test_customer_actor_with_zero_stores_has_bootstrap_parity(): void
    {
        $user = User::factory()->customer()->verified()->create([
            'last_active_store_id' => null,
        ]);

        $this->assertBootstrapParity($user, function (array $data): void {
            $this->assertSame('customer', $data['actor_context']);
            $this->assertSame([], $data['stores']);
            $this->assertNull($data['active_store']);
            $this->assertSame([], $data['permissions']);
            $this->assertSame(OnboardingStepEnum::COMPLETED->value, $data['onboarding']['step']);
            $this->assertTrue($data['onboarding']['is_completed']);
        });
    }

    public function test_onboarding_incomplete_and_completed_merchants_preserve_parity(): void
    {
        $incomplete = User::factory()->merchant()->verified()->create([
            'onboarding_step' => OnboardingStepEnum::CREATE_STORE,
            'last_active_store_id' => null,
        ]);

        $completed = User::factory()->merchant()->verified()->create([
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
            'last_active_store_id' => null,
        ]);

        $this->assertBootstrapParity($incomplete, function (array $data): void {
            $this->assertSame('merchant', $data['actor_context']);
            $this->assertSame(OnboardingStepEnum::CREATE_STORE->value, $data['onboarding']['step']);
            $this->assertFalse($data['onboarding']['is_completed']);
        });

        $this->assertBootstrapParity($completed, function (array $data): void {
            $this->assertSame('merchant', $data['actor_context']);
            $this->assertSame(OnboardingStepEnum::COMPLETED->value, $data['onboarding']['step']);
            $this->assertTrue($data['onboarding']['is_completed']);
        });
    }

    public function test_invalid_and_missing_active_store_references_preserve_bootstrap_parity(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('product.view', 'web');
        $role = Role::findOrCreate(StoreRoleEnum::STORE_ADMIN->value, 'web');
        $role->syncPermissions(['product.view']);

        $user = User::factory()->merchant()->verified()->create();
        $activeStore = Store::factory()->for($user, 'owner')->create(['name' => 'Accessible Active Store']);
        $inactiveStore = Store::factory()->for($user, 'owner')->inactive()->create(['name' => 'Inactive Store']);

        $user->stores()->attach($activeStore->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $user->stores()->attach($inactiveStore->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        $deletedStore = Store::factory()->for($user, 'owner')->create(['name' => 'Deleted Store']);
        $user->update(['last_active_store_id' => $deletedStore->id]);
        $deletedStore->delete();

        $this->assertBootstrapParity($user->fresh(), function (array $data) use ($activeStore): void {
            $this->assertSame($activeStore->id, $data['active_store']['id']);
        });

        $user->update(['last_active_store_id' => null]);
        $this->assertBootstrapParity($user->fresh(), function (array $data) use ($activeStore): void {
            $this->assertSame($activeStore->id, $data['active_store']['id']);
        });

        $user->update(['last_active_store_id' => $inactiveStore->id]);
        $this->assertBootstrapParity($user->fresh(), function (array $data) use ($inactiveStore): void {
            $this->assertSame($inactiveStore->id, $data['active_store']['id']);
            $this->assertContains($inactiveStore->id, array_column($data['stores'], 'id'));
        });
    }

    private function assertBootstrapParity(User $user, callable $assertions): void
    {
        $legacy = $this->bootstrapPayload($user, false);
        $decomposed = $this->bootstrapPayload($user, true);

        $this->assertSame($legacy, $decomposed);
        $assertions($legacy);
    }

    /**
     * @return array<string, mixed>
     */
    private function bootstrapPayload(User $user, bool $decomposed): array
    {
        config()->set('migration.bootstrap.v2_enabled', $decomposed);
        config()->set('migration.bootstrap.shadow_read', false);

        $response = $this->actingAs($user)->getJson('/api/v1/users/auth/bootstrap');
        $response->assertOk();

        return $response->json('data');
    }
}
