<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\ErrorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    // ---- Merchant: forgot password ----

    public function test_merchant_forgot_password_sends_link_for_known_email(): void
    {
        $user = User::factory()->merchant()->verified()->create();

        $response = $this->postJson('/api/v1/merchant/auth/password/forgot', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Password::sendResetLink()'s return status is never inspected by
     * AuthService::sendResetLink() / SendResetLinkAction, so the endpoint
     * always responds with success regardless of whether the email exists.
     * That's Laravel's standard account-enumeration protection, not a bug —
     * documented here so it isn't "fixed" into a user-enumeration leak later.
     */
    public function test_merchant_forgot_password_also_returns_success_for_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/merchant/auth/password/forgot', [
            'email' => 'no-such-merchant-account@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_merchant_forgot_password_fails_validation_for_invalid_email_format(): void
    {
        $response = $this->postJson('/api/v1/merchant/auth/password/forgot', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', ErrorCode::VAL_001->value)
            ->assertJsonValidationErrors(['email']);
    }

    // ---- Merchant: reset password ----

    public function test_merchant_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->merchant()->verified()->create([
            'password' => bcrypt('old-password-123'),
        ]);
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/merchant/auth/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('new-password-456', $user->fresh()->password));
    }

    public function test_merchant_reset_password_fails_validation_when_password_is_too_short(): void
    {
        $user = User::factory()->merchant()->verified()->create();
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/merchant/auth/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_merchant_reset_password_fails_validation_for_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/merchant/auth/password/reset', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token', 'email', 'password']);
    }

    /**
     * Real, current behavior worth flagging: AuthService::resetPassword() calls
     * Password::reset($data, $closure) but never inspects the returned status
     * constant. When the token is invalid/expired, Laravel's password broker
     * simply skips the closure (the password is never touched) and returns a
     * status string — which this codebase silently discards. The controller
     * then unconditionally responds with the same 200 "success" message it
     * would return for a real reset, and the password is left unchanged.
     *
     * This test documents that actual behavior rather than the behavior one
     * might expect (a 4xx for an invalid token) — see the QA report's
     * "doubts / behavior to confirm" section; this looks like a real bug
     * worth a human decision, not something this suite should paper over.
     */
    public function test_merchant_reset_password_with_invalid_token_reports_success_but_does_not_change_password(): void
    {
        $user = User::factory()->merchant()->verified()->create([
            'password' => bcrypt('old-password-123'),
        ]);

        $response = $this->postJson('/api/v1/merchant/auth/password/reset', [
            'token' => 'this-token-was-never-issued',
            'email' => $user->email,
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));
        $this->assertFalse(Hash::check('new-password-456', $user->fresh()->password));
    }

    // ---- Customer: forgot / reset password (shared SendResetLinkAction / ResetPasswordAction) ----

    public function test_customer_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->customer()->verified()->create([
            'password' => bcrypt('old-password-123'),
        ]);
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/customer/auth/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('new-password-456', $user->fresh()->password));
    }

    public function test_customer_reset_password_with_invalid_token_reports_success_but_does_not_change_password(): void
    {
        $user = User::factory()->customer()->verified()->create([
            'password' => bcrypt('old-password-123'),
        ]);

        $response = $this->postJson('/api/v1/customer/auth/password/reset', [
            'token' => 'this-token-was-never-issued',
            'email' => $user->email,
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));
    }

    public function test_customer_forgot_password_fails_validation_for_invalid_email_format(): void
    {
        $response = $this->postJson('/api/v1/customer/auth/password/forgot', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
