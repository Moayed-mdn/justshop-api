<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\DTOs\Auth\Session\SessionOwnershipContext;
use App\Enums\Auth\RouteDomainEnum;
use App\Services\Auth\TransitionalGuardResolver;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class TransitionalGuardResolverTest extends TestCase
{
    private TransitionalGuardResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(TransitionalGuardResolver::class);
    }

    public function test_customer_domain_resolves_to_customer_guard(): void
    {
        $context = new SessionOwnershipContext(
            authDomain: 'customer',
            actorType: null,
            routeDomain: 'customer_account',
            sessionOrigin: 'guest_shared_session',
            intendedGuardFuture: 'customer_guard',
            onboardingApplicable: false,
        );

        $result = $this->resolver->resolve($context);

        $this->assertSame('customer', $result->guard);
        $this->assertFalse($result->isFallback);
    }

    public function test_merchant_domain_resolves_to_merchant_guard(): void
    {
        $context = new SessionOwnershipContext(
            authDomain: 'merchant',
            actorType: null,
            routeDomain: 'merchant_users',
            sessionOrigin: 'guest_shared_session',
            intendedGuardFuture: 'merchant_guard',
            onboardingApplicable: false,
        );

        $result = $this->resolver->resolve($context);

        $this->assertSame('merchant', $result->guard);
        $this->assertFalse($result->isFallback);
    }

    public function test_platform_domain_resolves_to_merchant_guard(): void
    {
        $context = new SessionOwnershipContext(
            authDomain: 'platform',
            actorType: null,
            routeDomain: 'platform_admin',
            sessionOrigin: 'guest_shared_session',
            intendedGuardFuture: 'merchant_guard',
            onboardingApplicable: false,
        );

        $result = $this->resolver->resolve($context);

        $this->assertSame('merchant', $result->guard);
        $this->assertFalse($result->isFallback);
    }

    public function test_unknown_domain_resolves_to_web_guard(): void
    {
        $context = new SessionOwnershipContext(
            authDomain: null,
            actorType: null,
            routeDomain: 'shared_transitional',
            sessionOrigin: 'stateless',
            intendedGuardFuture: 'shared_guard',
            onboardingApplicable: false,
        );

        $result = $this->resolver->resolve($context);

        $this->assertSame('web', $result->guard);
    }

    public function test_web_guard_with_shared_transitional_is_not_fallback(): void
    {
        $context = new SessionOwnershipContext(
            authDomain: null,
            actorType: null,
            routeDomain: RouteDomainEnum::SHARED_TRANSITIONAL->value,
            sessionOrigin: 'stateless',
            intendedGuardFuture: 'shared_guard',
            onboardingApplicable: false,
        );

        $result = $this->resolver->resolve($context);

        $this->assertSame('web', $result->guard);
        $this->assertFalse($result->isFallback);
    }

    public function test_web_guard_with_non_transitional_route_is_fallback(): void
    {
        Log::spy();

        $context = new SessionOwnershipContext(
            authDomain: null,
            actorType: null,
            routeDomain: 'merchant_users',
            sessionOrigin: 'stateless',
            intendedGuardFuture: 'shared_guard',
            onboardingApplicable: false,
        );

        $result = $this->resolver->resolve($context);

        $this->assertSame('web', $result->guard);
        $this->assertTrue($result->isFallback);

        Log::shouldHaveReceived('error')->with(
            'auth.guard.illegal_fallback_detected',
            Mockery::on(fn (array $context): bool => ($context['route_domain'] ?? null) === 'merchant_users'),
        )->atLeast()->once();
    }

    public function test_resolution_emits_transitional_resolution_log(): void
    {
        Log::spy();

        $context = new SessionOwnershipContext(
            authDomain: 'customer',
            actorType: 'customer',
            routeDomain: 'customer_account',
            sessionOrigin: 'authenticated_shared_session',
            intendedGuardFuture: 'customer_guard',
            onboardingApplicable: false,
            actorId: 1,
        );

        $this->resolver->resolve($context);

        Log::shouldHaveReceived('info')->with(
            'auth.guard.transitional_resolution',
            Mockery::on(fn (array $context): bool => ($context['resolved_guard'] ?? null) === 'customer'
                && ($context['auth_domain'] ?? null) === 'customer'),
        )->atLeast()->once();
    }
}
