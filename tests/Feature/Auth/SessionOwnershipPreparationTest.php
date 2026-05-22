<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\DTOs\Auth\Identity\RouteDomainContext;
use App\Enums\Auth\AuthDomainEnum;
use App\Enums\Auth\RouteDomainEnforcementModeEnum;
use App\Enums\Auth\RouteDomainEnum;
use App\Models\User;
use App\Services\Auth\GuardShadowAnalyzer;
use App\Services\Auth\IdentityContextResolver;
use App\Services\Auth\SessionOwnershipResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionOwnershipPreparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_ownership_resolution_tracks_explicit_auth_route_context(): void
    {
        $user = User::factory()->customer()->verified()->create();
        $identity = app(IdentityContextResolver::class)->resolve($user);
        $routeContext = new RouteDomainContext(
            routeDomain: RouteDomainEnum::CUSTOMER_ACCOUNT,
            ownerAuthDomain: AuthDomainEnum::CUSTOMER,
            enforcementMode: RouteDomainEnforcementModeEnum::ENFORCE,
            allowedActorTypes: ['customer'],
        );

        $this->withSession(['wave' => '3b'])->actingAs($user);
        $context = app(SessionOwnershipResolver::class)->resolve(request(), $identity, $routeContext);

        $this->assertSame('customer', $context->authDomain);
        $this->assertSame('customer', $context->actorType);
        $this->assertSame('customer_account', $context->routeDomain);
        $this->assertSame('authenticated_shared_session', $context->sessionOrigin);
        $this->assertSame('customer_guard', $context->intendedGuardFuture);
        $this->assertFalse($context->onboardingApplicable);
    }

    public function test_guard_shadow_resolution_is_observe_only_and_detects_ambiguous_cross_domain_paths(): void
    {
        $user = User::factory()->merchant()->verified()->create();
        $identity = app(IdentityContextResolver::class)->resolve($user);
        $routeContext = new RouteDomainContext(
            routeDomain: RouteDomainEnum::CUSTOMER_ACCOUNT,
            ownerAuthDomain: AuthDomainEnum::CUSTOMER,
            enforcementMode: RouteDomainEnforcementModeEnum::ENFORCE,
            allowedActorTypes: ['customer'],
        );

        $this->actingAs($user);
        $ownership = app(SessionOwnershipResolver::class)->resolve(request(), $identity, $routeContext);
        $summary = app(GuardShadowAnalyzer::class)->analyze($ownership);

        $this->assertTrue($summary->merchant->wouldResolve);
        $this->assertTrue($summary->customer->wouldResolve);
        $this->assertSame('ambiguous_guard', $summary->futureGuardHint);
        $this->assertTrue($summary->ambiguousOwnershipPath);
        $this->assertTrue($summary->guardMismatchAnomaly);
    }
}
