<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\ErrorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_customer_can_logout(): void
    {
        $user = User::factory()->customer()->verified()->create();

        $response = $this->withHeaders(['Referer' => 'http://localhost'])->actingAs($user)->postJson('/api/v1/customer/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_guest_cannot_access_customer_logout(): void
    {
        $response = $this->postJson('/api/v1/customer/auth/logout');

        $response->assertStatus(401)
            ->assertJsonPath('code', ErrorCode::AUTH_002->value);
    }

    public function test_customer_logout_revokes_the_bearer_token_used_to_authenticate(): void
    {
        $user = User::factory()->customer()->verified()->create();
        $issued = $user->createToken('test-token');

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $issued->accessToken->id]);

        $response = $this->withHeaders(['Referer' => 'http://localhost'])
            ->withHeader('Authorization', 'Bearer ' . $issued->plainTextToken)
            ->postJson('/api/v1/customer/auth/logout');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $issued->accessToken->id]);
    }
}
