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

class PlatformLogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_authenticated_super_admin_can_logout_through_platform_endpoint(): void
    {
        Role::findOrCreate(RoleEnum::SUPER_ADMIN->value, 'web');

        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUPER_ADMIN->value);

        $response = $this->actingAs($user)->postJson('/api/v1/platform/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_guest_cannot_access_platform_logout(): void
    {
        $response = $this->postJson('/api/v1/platform/auth/logout');

        $response->assertStatus(401)
            ->assertJsonPath('code', ErrorCode::AUTH_002->value);
    }

    public function test_platform_logout_revokes_the_bearer_token_used_to_authenticate(): void
    {
        Role::findOrCreate(RoleEnum::SUPER_ADMIN->value, 'web');

        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUPER_ADMIN->value);
        $issued = $user->createToken('test-token');

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $issued->accessToken->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $issued->plainTextToken)
            ->postJson('/api/v1/platform/auth/logout');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $issued->accessToken->id]);
    }
}
