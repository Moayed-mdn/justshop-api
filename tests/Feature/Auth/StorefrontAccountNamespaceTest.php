<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class StorefrontAccountNamespaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_in_storefront_account_namespace_without_merchant_onboarding(): void
    {
        $response = $this->postJson('/api/v1/storefront/account/register', [
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
        $user = User::factory()->customer()->verified()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/storefront/account/bootstrap');

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

        $merchant = User::factory()->merchant()->verified()->create([
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);

        $response = $this->actingAs($merchant)->getJson('/api/v1/storefront/account/bootstrap');

        $response->assertForbidden()
            ->assertJsonPath('error_code', 'AUTH_002');

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

        $customer = User::factory()->customer()->verified()->create();
        $response = $this->actingAs($customer)->getJson('/api/v1/admin/stores/999/dashboard/stats');

        $response->assertForbidden()
            ->assertJsonPath('error_code', 'AUTH_002');

        Log::shouldHaveReceived('warning')->with(
            'identity.merchant_route.misused',
            Mockery::on(fn (array $context): bool => ($context['route_domain'] ?? null) === 'merchant_admin'
                && (($context['identity_context']['actor_type'] ?? null) === 'customer')),
        )->atLeast()->once();
    }
}
