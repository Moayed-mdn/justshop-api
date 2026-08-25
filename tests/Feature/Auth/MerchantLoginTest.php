<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\ErrorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->merchant()->verified()->create([
            'email' => 'merchant-login@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/v1/merchant/auth/login', [
            'email' => 'merchant-login@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('meta.session.auth_domain', 'merchant');
    }

    public function test_merchant_login_fails_with_wrong_password(): void
    {
        User::factory()->merchant()->verified()->create([
            'email' => 'merchant-wrong-pass@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/v1/merchant/auth/login', [
            'email' => 'merchant-wrong-pass@example.com',
            'password' => 'not-the-right-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', ErrorCode::AUTH_001->value);

        $this->assertGuest();
    }

    public function test_merchant_login_fails_for_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/merchant/auth/login', [
            'email' => 'no-such-merchant@example.com',
            'password' => 'whatever-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('code', ErrorCode::AUTH_001->value);
    }

    public function test_merchant_login_fails_validation_for_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/merchant/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonPath('code', ErrorCode::VAL_001->value)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_disabled_merchant_account_cannot_login(): void
    {
        User::factory()->merchant()->verified()->create([
            'email' => 'disabled-merchant@example.com',
            'password' => bcrypt('correct-password'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/merchant/auth/login', [
            'email' => 'disabled-merchant@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', ErrorCode::AUTH_002->value);

        $this->assertGuest();
    }

    /**
     * LoginUserAction (used by both the merchant and platform login endpoints)
     * only checks email/password/is_active — unlike LoginCustomerAction, it never
     * checks whether the resolved IdentityContext actor type is actually MERCHANT.
     * A customer-only account (no store membership, onboarding_step === null)
     * can therefore authenticate through the merchant login endpoint and receive
     * a 200 response tagging the session as 'merchant', even though
     * IdentityContextResolver still resolves them as a CUSTOMER actor.
     *
     * This test documents that real, current behavior rather than the behavior
     * one might expect — see the QA report's "doubts / behavior to confirm"
     * section for why this is flagged as worth a human decision.
     */
    public function test_customer_only_account_can_authenticate_through_merchant_login_endpoint(): void
    {
        User::factory()->customer()->verified()->create([
            'email' => 'customer-via-merchant-login@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/v1/merchant/auth/login', [
            'email' => 'customer-via-merchant-login@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.session.auth_domain', 'merchant');
    }
}
