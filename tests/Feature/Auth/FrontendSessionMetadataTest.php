<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendSessionMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_bootstrap_exposes_additive_session_metadata_without_changing_data_contract(): void
    {
        $merchant = User::factory()->merchant()->verified()->create([
            'onboarding_step' => OnboardingStepEnum::CREATE_STORE,
        ]);

        $response = $this->actingAs($merchant)->getJson('/api/v1/users/auth/bootstrap');

        $response->assertOk()
            ->assertJsonPath('data.actor_context', 'merchant')
            ->assertJsonPath('meta.session.auth_domain', 'merchant')
            ->assertJsonPath('meta.session.actor_type', 'merchant')
            ->assertJsonPath('meta.session.route_domain', 'merchant_users')
            ->assertJsonPath('meta.session.onboarding_applicable', true)
            ->assertJsonPath('meta.session.future_guard_hint', 'merchant_guard');
    }

    public function test_storefront_bootstrap_exposes_additive_session_metadata(): void
    {
        $customer = User::factory()->customer()->verified()->create();

        $response = $this->actingAs($customer)->getJson('/api/v1/storefront/account/bootstrap');

        $response->assertOk()
            ->assertJsonPath('data.identity_context.actor_type', 'customer')
            ->assertJsonPath('meta.session.auth_domain', 'customer')
            ->assertJsonPath('meta.session.actor_type', 'customer')
            ->assertJsonPath('meta.session.route_domain', 'customer_account')
            ->assertJsonPath('meta.session.onboarding_applicable', false)
            ->assertJsonPath('meta.session.future_guard_hint', 'customer_guard');
    }
}
