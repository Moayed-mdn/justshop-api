<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\Enums\ErrorCode;
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

    public function test_registration_fails_validation_for_missing_required_fields(): void
    {
        $response = $this->postJson('/api/v1/users/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonPath('code', ErrorCode::VAL_001->value)
            ->assertJsonValidationErrors(['name', 'email', 'password']);

        $this->assertGuest();
    }

    public function test_registration_fails_validation_for_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/v1/users/auth/register', [
            'name' => 'John Merchant',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertSame(1, User::where('email', 'taken@example.com')->count());
    }

    public function test_registration_fails_validation_when_password_confirmation_does_not_match(): void
    {
        $response = $this->postJson('/api/v1/users/auth/register', [
            'name' => 'John Merchant',
            'email' => 'mismatch@example.com',
            'password' => 'password123',
            'password_confirmation' => 'something-else',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => 'mismatch@example.com']);
    }
}
