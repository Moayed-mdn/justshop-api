<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\ErrorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_merchant_can_logout(): void
    {
        $user = User::factory()->merchant()->verified()->create();

        $response = $this->withHeaders(['Referer' => 'http://localhost'])->actingAs($user)->postJson('/api/v1/merchant/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_guest_cannot_access_merchant_logout(): void
    {
        $response = $this->postJson('/api/v1/merchant/auth/logout');

        $response->assertStatus(401)
            ->assertJsonPath('code', ErrorCode::AUTH_002->value);
    }

    public function test_merchant_logout_revokes_the_bearer_token_used_to_authenticate(): void
    {
        // $user->createToken() genuinely persists a personal_access_tokens row
        // (unlike Sanctum::actingAs(), which attaches an in-memory, unsaved
        // token model and would make a DB assertion here meaningless).
        $user = User::factory()->merchant()->verified()->create();
        $issued = $user->createToken('test-token');

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $issued->accessToken->id]);

        $response = $this->withHeaders(['Referer' => 'http://localhost'])
            ->withHeader('Authorization', 'Bearer ' . $issued->plainTextToken)
            ->postJson('/api/v1/merchant/auth/logout');

        $response->assertStatus(200);

        // LogoutUserAction::execute() explicitly deletes $user->currentAccessToken()
        // when one is present, on top of invalidating the shared session.
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $issued->accessToken->id]);
    }
}
