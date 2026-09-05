<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\ErrorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * ApplyIdentityRouteContext applies the same actor-type mismatch check
 * (matchesOwnership) regardless of a route's declared enforcement mode, and
 * always logs 'identity.actor_domain.mismatch' when it fails. The only
 * difference is what happens next: 'enforce' additionally logs
 * 'identity.cross_context.denied' and throws InvalidIdentityDomainAccessException
 * (403); 'observe' logs the mismatch and lets the request through.
 *
 * Both routes below use a fresh, untagged session so the separate session-
 * contamination check (enforceSessionOwnership) — which fires independently
 * of enforcement mode — never enters the picture; what differs between the
 * two assertions is exclusively the enforcement mode.
 */
class IdentityRouteEnforcementModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_observe_mode_logs_actor_mismatch_but_allows_the_request_through(): void
    {
        Log::spy();

        // routes/api.php: Route::prefix('/v1/users')->middleware([
        //     'identity.route:merchant_users,merchant,observe', ...
        // ]) — a customer actor is not an allowed actor type for the
        // merchant domain, but the mode is 'observe'.
        $customer = User::factory()->customer()->verified()->create();

        $response = $this->actingAs($customer)->getJson('/api/v1/users/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $customer->id);

        Log::shouldHaveReceived('warning')->with(
            'identity.actor_domain.mismatch',
            Mockery::on(fn (array $context): bool => ($context['actual_auth_domain'] ?? null) === 'customer'
                && ($context['expected_auth_domain'] ?? null) === 'merchant'),
        )->once();

        Log::shouldNotHaveReceived('warning', ['identity.cross_context.denied', Mockery::any()]);
    }

    public function test_enforce_mode_logs_actor_mismatch_and_blocks_the_request(): void
    {
        Log::spy();

        // routes/api.php: Route::prefix('/v1/merchant')->middleware([
        //     'identity.route:merchant_users,merchant,enforce', ...
        // ]) — same actor/domain mismatch as above, only the mode differs.
        $customer = User::factory()->customer()->verified()->create();

        $response = $this->actingAs($customer)->getJson('/api/v1/merchant/me');

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', ErrorCode::IDENTITY_DOMAIN_MISMATCH->value);

        Log::shouldHaveReceived('warning')->with(
            'identity.actor_domain.mismatch',
            Mockery::on(fn (array $context): bool => ($context['actual_auth_domain'] ?? null) === 'customer'
                && ($context['expected_auth_domain'] ?? null) === 'merchant'),
        )->once();

        Log::shouldHaveReceived('warning')->with(
            'identity.cross_context.denied',
            Mockery::any(),
        )->once();
    }
}
