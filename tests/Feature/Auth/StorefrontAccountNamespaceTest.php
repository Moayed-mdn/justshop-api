<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\Enums\ErrorCode;
use App\Enums\PermissionEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StorefrontAccountNamespaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_in_customer_namespace_without_merchant_onboarding(): void
    {
        $response = $this->withHeaders(['Referer' => 'http://localhost'])->postJson('/api/v1/customer/auth/register', [
            'name' => 'Storefront Customer',
            'email' => 'storefront-customer@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'storefront-customer@example.test');

        $this->assertDatabaseHas('users', [
            'email' => 'storefront-customer@example.test',
            'onboarding_step' => null,
        ]);
    }

    public function test_customer_bootstrap_is_isolated_from_merchant_bootstrap_and_contains_identity_and_session_metadata(): void
    {
        /** @var User $user */
        $user = User::factory()->customer()->verified()->create();

        $response = $this->actingAs($user, 'customer')->getJson('/api/v1/customer/bootstrap');

        $response->assertOk()
            ->assertJsonMissingPath('data.stores')
            ->assertJsonMissingPath('data.onboarding')
            ->assertJsonPath('data.identity_context.actor_type', 'customer')
            ->assertJsonPath('data.identity_context.auth_domain', 'customer')
            ->assertJsonPath('data.identity_context.onboarding_required', false)
            ->assertJsonPath('data.session.auth_domain', 'customer')
            ->assertJsonPath('data.session.actor_type', 'customer')
            ->assertJsonPath('data.session.authority_model', 'shared_sanctum_session')
            ->assertJsonPath('data.session.isolation_state', 'shared_until_guard_split');
    }

    public function test_authenticated_merchant_is_denied_from_customer_account_namespace_and_denial_is_telemetried(): void
    {
        Log::spy();

        /** @var User $merchant */
        $merchant = User::factory()->merchant()->verified()->create([
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);

        $response = $this->actingAs($merchant, 'merchant')->getJson('/api/v1/customer/bootstrap');

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'code' => ErrorCode::IDENTITY_DOMAIN_MISMATCH->value,
            ]);

        Log::shouldHaveReceived('warning')->with(
            'identity.cross_context.denied',
            Mockery::on(fn (array $context): bool => ($context['route_domain'] ?? null) === 'customer_account'
                && ($context['route_owner_auth_domain'] ?? null) === 'customer'
                && (($context['identity_context']['actor_type'] ?? null) === 'merchant')),
        )->atLeast()->once();
    }

    public function test_customer_is_denied_from_admin_route_and_mismatch_is_telemetried(): void
    {
        Log::spy();

        /** @var User $customer */
        $customer = User::factory()->customer()->verified()->create();
        $store = Store::factory()->create();
        $response = $this->actingAs($customer, 'customer')->getJson("/api/v1/merchant/stores/{$store->id}/dashboard/stats");

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'code' => ErrorCode::IDENTITY_DOMAIN_MISMATCH->value,
            ]);

        Log::shouldHaveReceived('warning')->with(
            'identity.merchant_route.misused',
            Mockery::on(fn (array $context): bool => ($context['route_domain'] ?? null) === 'merchant_admin'
                && (($context['identity_context']['actor_type'] ?? null) === 'customer')),
        )->atLeast()->once();
    }

    public function test_legacy_storefront_account_is_deprecated(): void
    {
        /** @var User $user */
        $user = User::factory()->customer()->verified()->create();

        $response = $this->actingAs($user, 'customer')->getJson('/api/v1/storefront/account/bootstrap');

        $response->assertOk()
            ->assertHeader('X-API-Deprecated', 'true')
            ->assertHeader('X-API-Suggested-New-Route', '/v1/customer/bootstrap');
    }

    public function test_store_owner_can_access_store_admin_dashboard_via_pivot_role_permissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate(PermissionEnum::DASHBOARD_VIEW, 'web');
        $storeAdminRole = Role::findOrCreate(StoreRoleEnum::STORE_ADMIN->value, 'web');
        $storeAdminRole->syncPermissions([PermissionEnum::DASHBOARD_VIEW]);

        /** @var User $merchant */
        $merchant = User::factory()->merchant()->verified()->create([
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);

        $store = Store::factory()->for($merchant, 'owner')->create();
        $merchant->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        $response = $this->actingAs($merchant, 'merchant')->getJson("/api/v1/admin/stores/{$store->id}/dashboard/stats");

        $response->assertOk()
            ->assertJsonPath('success', true);
    }
}
