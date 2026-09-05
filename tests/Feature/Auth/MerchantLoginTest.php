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

        $response = $this->withHeaders(['Referer' => 'http://localhost'])->postJson('/api/v1/merchant/auth/login', [
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
     * only checks email/password/is_active — unlike LoginCustomerAction, it
     * never checks whether the resolved IdentityContext actor type is
     * actually MERCHANT. A customer-only account (no store membership,
     * onboarding_step === null) can therefore present valid credentials at
     * the merchant login endpoint and receive a 200 response — but the
     * session is tagged, and auth_domain is reported, as CUSTOMER
     * (IdentityContextResolver's actual resolution of who this account is),
     * not 'merchant' merely because of which endpoint accepted the
     * credentials. This is the intended design: identity is determined by
     * the account, never by the endpoint used to authenticate.
     */
    public function test_customer_only_account_can_authenticate_through_merchant_login_endpoint(): void
    {
        $customer = User::factory()->customer()->verified()->create([
            'email' => 'customer-via-merchant-login@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->withHeaders(['Referer' => 'http://localhost'])->postJson('/api/v1/merchant/auth/login', [
            'email' => 'customer-via-merchant-login@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.session.auth_domain', 'customer');

        // The actual security property: even having "logged in" via the
        // merchant endpoint, this customer session must not be able to
        // reach real merchant-domain routes.
        $store = \App\Models\Store::factory()->create(['owner_id' => $customer->id]);
        $this->getJson("/api/v1/merchant/stores/{$store->id}/dashboard/stats")
            ->assertForbidden();
    }
}
