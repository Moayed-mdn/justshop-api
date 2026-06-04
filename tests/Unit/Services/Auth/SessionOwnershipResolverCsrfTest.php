<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Auth;

use App\Services\Auth\SessionOwnershipResolver;
use App\Support\Observability\RequestTraceContextManager;
use Illuminate\Http\Request;
use Tests\TestCase;

class SessionOwnershipResolverCsrfTest extends TestCase
{
    private SessionOwnershipResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $traceContext = $this->createMock(RequestTraceContextManager::class);
        $this->resolver = new SessionOwnershipResolver($traceContext);
    }

    public function test_customer_referer_path_resolves_to_customer_domain(): void
    {
        $request = new Request();
        $request->headers->set('referer', 'http://storefront.test/storefront/account/profile');

        $context = $this->resolver->resolveForCsrf($request);

        $this->assertSame('customer', $context->authDomain);
        $this->assertSame('customer_account', $context->routeDomain);
        $this->assertSame('customer_guard', $context->intendedGuardFuture);
    }

    public function test_merchant_referer_path_resolves_to_merchant_domain(): void
    {
        $request = new Request();
        $request->headers->set('referer', 'http://admin.test/merchant/dashboard');

        $context = $this->resolver->resolveForCsrf($request);

        $this->assertSame('merchant', $context->authDomain);
        $this->assertSame('merchant_users', $context->routeDomain);
        $this->assertSame('merchant_guard', $context->intendedGuardFuture);
    }

    public function test_no_referer_falls_back_to_merchant_domain(): void
    {
        $request = new Request();

        $context = $this->resolver->resolveForCsrf($request);

        $this->assertSame('merchant', $context->authDomain);
        $this->assertSame('merchant_users', $context->routeDomain);
        $this->assertSame('merchant_guard', $context->intendedGuardFuture);
    }

    public function test_origin_only_fallback_defaults_to_merchant(): void
    {
        $request = new Request();
        $request->headers->set('origin', 'http://storefront.test');

        $context = $this->resolver->resolveForCsrf($request);

        $this->assertSame('merchant', $context->authDomain);
        $this->assertSame('merchant_users', $context->routeDomain);
    }

    public function test_storefront_account_in_origin_resolves_to_customer(): void
    {
        $request = new Request();
        $request->headers->set('origin', 'http://storefront.test/storefront/account/login');

        $context = $this->resolver->resolveForCsrf($request);

        $this->assertSame('customer', $context->authDomain);
        $this->assertSame('customer_account', $context->routeDomain);
    }

    public function test_empty_string_referer_defaults_to_merchant(): void
    {
        $request = new Request();
        $request->headers->set('referer', '');

        $context = $this->resolver->resolveForCsrf($request);

        $this->assertSame('merchant', $context->authDomain);
        $this->assertSame('merchant_users', $context->routeDomain);
    }

    public function test_referer_with_query_string_containing_storefront_account(): void
    {
        $request = new Request();
        $request->headers->set('referer', 'http://storefront.test/storefront/account?redirect=/dashboard');

        $context = $this->resolver->resolveForCsrf($request);

        $this->assertSame('customer', $context->authDomain);
        $this->assertSame('customer_account', $context->routeDomain);
    }

    public function test_session_present_sets_guest_shared_session_origin(): void
    {
        $request = new Request();
        $request->setLaravelSession($this->app->make('session')->driver());

        $context = $this->resolver->resolveForCsrf($request);

        $this->assertSame('guest_shared_session', $context->sessionOrigin);
        $this->assertNotNull($context->sessionId);
    }

    public function test_stateless_request_sets_stateless_origin(): void
    {
        $request = new Request();

        $context = $this->resolver->resolveForCsrf($request);

        $this->assertSame('stateless', $context->sessionOrigin);
        $this->assertNull($context->sessionId);
    }

    public function test_resolveForCsrf_always_sets_actor_fields_null(): void
    {
        $request = new Request();
        $request->headers->set('referer', 'http://storefront.test/storefront/account/profile');

        $context = $this->resolver->resolveForCsrf($request);

        $this->assertNull($context->actorType);
        $this->assertNull($context->actorId);
        $this->assertFalse($context->onboardingApplicable);
    }
}
