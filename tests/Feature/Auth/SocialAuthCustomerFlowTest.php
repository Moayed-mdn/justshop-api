<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Mockery;
use Tests\TestCase;

class SocialAuthCustomerFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_google_callback_tags_session_as_customer_and_allows_customer_me(): void
    {
        config()->set('app.frontend_url', 'http://demo.justshop.test:3000');

        /** @var User $customer */
        $customer = User::factory()->customer()->verified()->create([
            'name' => 'Storefront Customer',
            'email' => 'storefront-customer@example.test',
        ]);

        $providerUser = Mockery::mock();
        $providerUser->shouldReceive('getId')->atLeast()->once()->andReturn('google-customer-123');
        $providerUser->shouldReceive('getEmail')->atLeast()->once()->andReturn($customer->email);
        $providerUser->shouldReceive('getName')->atLeast()->once()->andReturn($customer->name);
        $providerUser->shouldReceive('getAvatar')->atLeast()->once()->andReturn('https://example.test/avatar.png');

        $googleProvider = Mockery::mock(GoogleProvider::class);
        $googleProvider->shouldReceive('stateless')->once()->andReturnSelf();
        $googleProvider->shouldReceive('user')->once()->andReturn($providerUser);

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($googleProvider);

        $response = $this->get('/api/v1/users/auth/google/callback?code=test-code', [
            'X-Storefront-Version' => 'test-contract',
            'X-Frontend-Url' => 'http://demo.justshop.test:3000',
        ]);

        $response->assertRedirect('http://demo.justshop.test:3000/auth/google/callback?status=success')
            ->assertSessionHas('auth_domain', 'customer')
            ->assertSessionHas('actor_type', 'customer')
            ->assertSessionHas('actor_id', $customer->id);

        $this->getJson('/api/v1/customer/me')
            ->assertOk()
            ->assertJsonPath('data.email', $customer->email)
            ->assertJsonPath('meta.session.auth_domain', 'customer')
            ->assertJsonPath('meta.session.actor_type', 'customer');

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'google_id' => 'google-customer-123',
        ]);
    }
}
