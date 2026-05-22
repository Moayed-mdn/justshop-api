<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class SessionGuardTelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_cross_domain_session_usage_and_bootstrap_misuse_are_telemetried(): void
    {
        Log::spy();

        $merchant = User::factory()->merchant()->verified()->create([
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);

        $response = $this->actingAs($merchant)->getJson('/api/v1/storefront/account/bootstrap');

        $response->assertForbidden();

        Log::shouldHaveReceived('warning')->with(
            'session.contamination.cross_domain_detected',
            Mockery::on(fn (array $context): bool => (($context['session_ownership']['auth_domain'] ?? null) === 'merchant')
                && (($context['session_ownership']['route_domain'] ?? null) === 'customer_account')),
        )->atLeast()->once();

        Log::shouldHaveReceived('warning')->with(
            'session.contamination.bootstrap_misuse_detected',
            Mockery::on(fn (array $context): bool => (($context['session_ownership']['route_domain'] ?? null) === 'customer_account')),
        )->atLeast()->once();

        Log::shouldHaveReceived('warning')->with(
            'session.contamination.actor_context_detected',
            Mockery::on(fn (array $context): bool => (($context['session_ownership']['actor_type'] ?? null) === 'merchant')
                && (($context['guard_shadow']['future_guard_hint'] ?? null) === 'ambiguous_guard')),
        )->atLeast()->once();

        Log::shouldHaveReceived('warning')->with(
            'session.contamination.future_guard_ambiguity_detected',
            Mockery::on(fn (array $context): bool => (($context['guard_shadow']['future_guard_hint'] ?? null) === 'ambiguous_guard')),
        )->atLeast()->once();

        Log::shouldHaveReceived('info')->with(
            'session.contamination.severity_assessed',
            Mockery::on(fn (array $context): bool => (($context['severity_score'] ?? 0) >= 70)),
        )->atLeast()->once();
    }

    public function test_logout_ownership_tracing_is_emitted_without_behavior_change(): void
    {
        Log::spy();

        $customer = User::factory()->customer()->verified()->create();

        $response = $this->actingAs($customer)->postJson('/api/v1/storefront/account/logout');

        $response->assertOk()
            ->assertJsonPath('meta.session.auth_domain', 'customer')
            ->assertJsonPath('meta.session.future_guard_hint', 'customer_guard');

        Log::shouldHaveReceived('info')->with(
            'session.logout.ownership_traced',
            Mockery::on(fn (array $context): bool => (($context['session_ownership']['auth_domain'] ?? null) === 'customer')
                && (($context['guard_shadow']['future_guard_hint'] ?? null) === 'customer_guard')),
        )->atLeast()->once();
    }

    public function test_csrf_preparation_emits_headers_and_telemetry_without_changing_csrf_behavior(): void
    {
        Log::spy();

        $response = $this->withHeaders([
            'Referer' => 'https://frontend.test/storefront/account/login',
        ])->get('/api/sanctum/csrf-cookie');

        $response->assertStatus(204)
            ->assertHeader('X-Session-Auth-Domain', 'customer')
            ->assertHeader('X-Session-Route-Domain', 'customer_account')
            ->assertHeader('X-Future-Guard-Hint', 'customer_guard');

        Log::shouldHaveReceived('info')->with(
            'session.csrf.ownership_traced',
            Mockery::on(fn (array $context): bool => (($context['session_ownership']['route_domain'] ?? null) === 'customer_account')
                && (($context['guard_shadow']['future_guard_hint'] ?? null) === 'customer_guard')),
        )->atLeast()->once();
    }
}
