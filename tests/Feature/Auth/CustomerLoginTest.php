<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\Enums\ErrorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->customer()->verified()->create([
            'email' => 'customer-login@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->withHeaders(['Referer' => 'http://localhost'])->postJson('/api/v1/customer/auth/login', [
            'email' => 'customer-login@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('meta.session.auth_domain', 'customer');
    }

    public function test_customer_login_fails_with_wrong_password(): void
    {
        User::factory()->customer()->verified()->create([
            'email' => 'customer-wrong-pass@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/v1/customer/auth/login', [
            'email' => 'customer-wrong-pass@example.com',
            'password' => 'not-the-right-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('code', ErrorCode::AUTH_001->value);

        $this->assertGuest();
    }

    public function test_customer_login_fails_validation_for_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/customer/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonPath('code', ErrorCode::VAL_001->value)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_disabled_customer_account_cannot_login(): void
    {
        User::factory()->customer()->verified()->create([
            'email' => 'disabled-customer@example.com',
            'password' => bcrypt('correct-password'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/customer/auth/login', [
            'email' => 'disabled-customer@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('code', ErrorCode::AUTH_002->value);

        $this->assertGuest();
    }

    /**
     * LoginCustomerAction explicitly resolves the IdentityContext and rejects
     * any credentials that don't resolve to a CUSTOMER actor — this is the
     * gate that MerchantLoginTest documents as absent on the merchant/platform
     * login endpoints. A merchant account (onboarding_step not null) must be
     * refused here even with correct credentials.
     */
    public function test_merchant_account_cannot_authenticate_through_customer_login_endpoint(): void
    {
        User::factory()->merchant()->verified()->create([
            'email' => 'merchant-via-customer-login@example.com',
            'password' => bcrypt('correct-password'),
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);

        $response = $this->postJson('/api/v1/customer/auth/login', [
            'email' => 'merchant-via-customer-login@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', ErrorCode::IDENTITY_DOMAIN_MISMATCH->value);

        $this->assertGuest();
    }
}
