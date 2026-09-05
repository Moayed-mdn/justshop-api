<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\ErrorCode;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlatformLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_super_admin_can_login_through_platform_endpoint(): void
    {
        Role::findOrCreate(RoleEnum::SUPER_ADMIN->value, 'web');

        $user = User::factory()->create([
            'email' => 'super-admin-login@example.com',
            'password' => bcrypt('correct-password'),
        ]);
        $user->assignRole(RoleEnum::SUPER_ADMIN->value);

        $response = $this->withHeaders(['Referer' => 'http://localhost'])->postJson('/api/v1/platform/auth/login', [
            'email' => 'super-admin-login@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.actor_context', 'super_admin')
            ->assertJsonPath('data.auth_domain', 'platform')
            ->assertJsonPath('data.session.auth_domain', 'platform');
    }

    public function test_platform_login_fails_with_wrong_password(): void
    {
        Role::findOrCreate(RoleEnum::SUPER_ADMIN->value, 'web');

        $user = User::factory()->create([
            'email' => 'platform-wrong-pass@example.com',
            'password' => bcrypt('correct-password'),
        ]);
        $user->assignRole(RoleEnum::SUPER_ADMIN->value);

        $response = $this->postJson('/api/v1/platform/auth/login', [
            'email' => 'platform-wrong-pass@example.com',
            'password' => 'not-the-right-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('code', ErrorCode::AUTH_001->value);

        $this->assertGuest();
    }

    public function test_platform_login_fails_validation_for_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/platform/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonPath('code', ErrorCode::VAL_001->value)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * PlatformAuthController::login() delegates to the same LoginUserAction as
     * the merchant endpoint: it checks email/password/is_active only, with no
     * check that the account actually holds SUPER_ADMIN or SUPPORT_AGENT.
     * A plain merchant account can authenticate through the platform login
     * endpoint and receive a 200 tagging the session 'platform', while
     * IdentityContextResolver still (correctly) resolves them as a MERCHANT
     * actor — the response body reflects that mismatch in 'actor_context' /
     * 'auth_domain' rather than rejecting the login outright.
     *
     * This documents real, current behavior — see the QA report's "doubts /
     * behavior to confirm" section; a merchant reaching this endpoint with
     * valid credentials is a real gap worth a human decision, not something
     * this suite should silently assume away.
     */
    public function test_merchant_account_can_authenticate_through_platform_login_endpoint(): void
    {
        User::factory()->merchant()->verified()->create([
            'email' => 'merchant-via-platform-login@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->withHeaders(['Referer' => 'http://localhost'])->postJson('/api/v1/platform/auth/login', [
            'email' => 'merchant-via-platform-login@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.actor_context', 'merchant')
            ->assertJsonPath('data.auth_domain', 'merchant');
    }
}
