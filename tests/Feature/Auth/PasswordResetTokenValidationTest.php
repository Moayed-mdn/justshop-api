<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTokenValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_reset_token_returns_success_for_valid_token(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var \Illuminate\Auth\Passwords\PasswordBroker $broker */
        $broker = app(PasswordBrokerManager::class)->broker();
        $token = $broker->createToken($user);

        $response = $this->postJson('/api/v1/users/auth/password/validate-token', [
            'email' => $user->email,
            'token' => $token,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => __('passwords.token_valid'),
            ]);
    }

    public function test_validate_reset_token_returns_validation_error_for_unknown_user(): void
    {
        $response = $this->postJson('/api/v1/users/auth/password/validate-token', [
            'email' => 'missing@example.com',
            'token' => 'invalid-token',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => __('passwords.token_invalid'),
            ]);
    }
}
