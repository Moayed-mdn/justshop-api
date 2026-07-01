<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\System\FrontendUrlBuilder;
use Illuminate\Http\Request;
use Tests\TestCase;

class FrontendUrlBuilderTest extends TestCase
{
    public function test_build_uses_configured_frontend_url_by_default(): void
    {
        config()->set('app.frontend_url', 'http://localhost:3000');

        $url = FrontendUrlBuilder::build('/auth/google/callback', ['error' => 'google_auth_failed']);

        $this->assertSame(
            'http://localhost:3000/auth/google/callback?error=google_auth_failed',
            $url,
        );
    }

    public function test_build_signed_uses_custom_base_url(): void
    {
        $url = FrontendUrlBuilder::buildSigned(
            '/verify-email/1/abc123',
            'http://backend.test/verify/1?expires=123&signature=xyz',
            baseUrl: 'http://demo.justshop.test:3000',
        );

        $this->assertStringStartsWith('http://demo.justshop.test:3000/verify-email/1/abc123?', $url);
        $this->assertStringContainsString('expires=123', $url);
        $this->assertStringContainsString('signature=xyz', $url);
    }

    public function test_resolve_from_x_frontend_url_header(): void
    {
        config()->set('app.frontend_url', 'http://localhost:3000');

        $request = Request::create('/api/test', 'GET', server: [
            'HTTP_X_FRONTEND_URL' => 'http://demo.justshop.test:3000',
        ]);

        $url = FrontendUrlBuilder::resolveRequestFrontendUrl($request);

        $this->assertSame('http://demo.justshop.test:3000', $url);
    }

    public function test_from_store_domain_uses_config_scheme_and_port(): void
    {
        config()->set('app.frontend_url', 'http://localhost:3000');

        $url = FrontendUrlBuilder::fromStoreDomain('demo.justshop.test');

        $this->assertSame('http://demo.justshop.test:3000', $url);
    }

    public function test_from_store_domain_with_https(): void
    {
        config()->set('app.frontend_url', 'https://localhost');

        $url = FrontendUrlBuilder::fromStoreDomain('demo.justshop.test');

        $this->assertSame('https://demo.justshop.test', $url);
    }

    public function test_social_auth_flow_remembers_frontend_url_from_referer(): void
    {
        config()->set('app.frontend_url', 'http://localhost:3000');

        $session = app('session')->driver();
        $session->start();

        $redirectRequest = Request::create('/api/v1/users/auth/google/redirect', 'GET', server: [
            'HTTP_REFERER' => 'http://demo.justshop.test:3000/api/auth/google/redirect',
        ]);
        $redirectRequest->setLaravelSession($session);

        $rememberedUrl = FrontendUrlBuilder::rememberSocialAuthFrontendUrl($redirectRequest);

        $this->assertSame('http://demo.justshop.test:3000', $rememberedUrl);

        $callbackRequest = Request::create('/api/v1/users/auth/google/callback', 'GET');
        $callbackRequest->setLaravelSession($session);

        $frontendBaseUrl = FrontendUrlBuilder::pullSocialAuthFrontendUrl($callbackRequest);

        $this->assertSame('http://demo.justshop.test:3000', $frontendBaseUrl);
        $this->assertSame(
            'http://demo.justshop.test:3000/auth/google/callback?user_id=42',
            FrontendUrlBuilder::build('/auth/google/callback', ['user_id' => 42], $frontendBaseUrl),
        );
    }
}
