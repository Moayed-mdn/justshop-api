<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Services\Auth\SessionGuardTelemetry;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class CsrfOwnershipPreparationControllerTest extends TestCase
{
    public function test_returns_204_status(): void
    {
        $response = $this->get('/api/sanctum/csrf-cookie');

        $response->assertStatus(204);
    }

    public function test_sets_x_session_auth_domain_header(): void
    {
        $response = $this->get('/api/sanctum/csrf-cookie');

        $response->assertHeader('X-Session-Auth-Domain');
    }

    public function test_customer_referer_sets_customer_headers(): void
    {
        $response = $this->withHeaders([
            'Referer' => 'https://frontend.test/storefront/account/login',
        ])->get('/api/sanctum/csrf-cookie');

        $response->assertStatus(204)
            ->assertHeader('X-Session-Auth-Domain', 'customer')
            ->assertHeader('X-Session-Route-Domain', 'customer_account')
            ->assertHeader('X-Future-Guard-Hint', 'customer_guard');
    }

    public function test_merchant_referer_sets_merchant_headers(): void
    {
        $response = $this->withHeaders([
            'Referer' => 'https://admin.test/merchant/dashboard',
        ])->get('/api/sanctum/csrf-cookie');

        $response->assertStatus(204)
            ->assertHeader('X-Session-Auth-Domain', 'merchant')
            ->assertHeader('X-Session-Route-Domain', 'merchant_users')
            ->assertHeader('X-Future-Guard-Hint', 'merchant_guard');
    }

    public function test_no_referer_defaults_to_merchant_headers(): void
    {
        $response = $this->get('/api/sanctum/csrf-cookie');

        $response->assertStatus(204)
            ->assertHeader('X-Session-Auth-Domain', 'merchant')
            ->assertHeader('X-Session-Route-Domain', 'merchant_users')
            ->assertHeader('X-Future-Guard-Hint', 'merchant_guard');
    }

    public function test_emits_ownership_traced_telemetry(): void
    {
        Log::spy();

        $this->withHeaders([
            'Referer' => 'https://frontend.test/storefront/account/login',
        ])->get('/api/sanctum/csrf-cookie');

        Log::shouldHaveReceived('info')->with(
            'session.csrf.ownership_traced',
            Mockery::on(fn (array $context): bool => (($context['session_ownership']['auth_domain'] ?? null) === 'customer')
                && (($context['guard_shadow']['future_guard_hint'] ?? null) === 'customer_guard')),
        )->atLeast()->once();
    }

    public function test_merchant_referer_emits_merchant_telemetry(): void
    {
        Log::spy();

        $this->withHeaders([
            'Referer' => 'https://admin.test/merchant/dashboard',
        ])->get('/api/sanctum/csrf-cookie');

        Log::shouldHaveReceived('info')->with(
            'session.csrf.ownership_traced',
            Mockery::on(fn (array $context): bool => (($context['session_ownership']['auth_domain'] ?? null) === 'merchant')
                && (($context['session_ownership']['route_domain'] ?? null) === 'merchant_users')),
        )->atLeast()->once();
    }

    public function test_sets_xsrf_token_cookie_via_delegation(): void
    {
        $response = $this->get('/api/sanctum/csrf-cookie');

        $response->assertStatus(204);
        $response->assertCookie('XSRF-TOKEN');
    }

    public function test_customer_referer_emits_customer_ownership_metric(): void
    {
        Log::spy();

        $this->withHeaders([
            'Referer' => 'https://frontend.test/storefront/account/login',
        ])->get('/api/sanctum/csrf-cookie');

        Log::shouldHaveReceived('info')->with(
            SessionGuardTelemetry::METRIC_CSRF_OWNERSHIP_CUSTOMER,
            Mockery::any(),
        )->atLeast()->once();
    }

    public function test_merchant_referer_emits_merchant_ownership_metric(): void
    {
        Log::spy();

        $this->withHeaders([
            'Referer' => 'https://admin.test/merchant/dashboard',
        ])->get('/api/sanctum/csrf-cookie');

        Log::shouldHaveReceived('info')->with(
            SessionGuardTelemetry::METRIC_CSRF_OWNERSHIP_MERCHANT,
            Mockery::any(),
        )->atLeast()->once();
    }

    public function test_no_referer_emits_referer_missing_metric(): void
    {
        Log::spy();

        $this->get('/api/sanctum/csrf-cookie');

        Log::shouldHaveReceived('info')->with(
            SessionGuardTelemetry::METRIC_CSRF_OWNERSHIP_REFERER_MISSING,
            Mockery::any(),
        )->atLeast()->once();
    }
}
