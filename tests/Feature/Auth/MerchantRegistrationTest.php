<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_can_register_and_starts_onboarding(): void
    {
        $payload = [
            'name' => 'John Merchant',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/users/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.onboarding_step', OnboardingStepEnum::PENDING_VERIFICATION->value);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'onboarding_step' => OnboardingStepEnum::PENDING_VERIFICATION->value,
        ]);

        $this->assertAuthenticated();
    }

    public function test_unverified_merchant_is_restricted_in_bootstrap(): void
    {
        $user = User::factory()->pendingVerification()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/v1/users/bootstrap');

        $response->assertStatus(200)
            ->assertJsonPath('data.onboarding.step', OnboardingStepEnum::PENDING_VERIFICATION->value)
            ->assertJsonPath('data.email_verified', false);
    }
}
