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
