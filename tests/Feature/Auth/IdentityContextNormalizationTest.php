<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Auth\ActorContextEnum;
use App\Enums\Auth\AuthDomainEnum;
use App\Enums\Auth\OperationalContextEnum;
use App\Enums\Auth\OnboardingStepEnum;
use App\Enums\RoleEnum;
use App\Services\Auth\IdentityContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IdentityContextNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_identity_context_is_explicit_and_customer_safe(): void
    {
        $user = \App\Models\User::factory()->customer()->verified()->create();

        $context = app(IdentityContextResolver::class)->resolve($user);

        $this->assertSame(ActorContextEnum::CUSTOMER, $context->actorType);
        $this->assertSame(AuthDomainEnum::CUSTOMER, $context->authDomain);
        $this->assertFalse($context->onboardingRequired);
        $this->assertSame(OperationalContextEnum::CUSTOMER_ACCOUNT, $context->operationalContext);
    }

    public function test_merchant_identity_context_is_explicit_and_marks_onboarding_as_required_context(): void
    {
        $user = \App\Models\User::factory()->merchant()->verified()->create([
            'onboarding_step' => OnboardingStepEnum::CREATE_STORE,
        ]);

        $context = app(IdentityContextResolver::class)->resolve($user);

        $this->assertSame(ActorContextEnum::MERCHANT, $context->actorType);
        $this->assertSame(AuthDomainEnum::MERCHANT, $context->authDomain);
        $this->assertTrue($context->onboardingRequired);
        $this->assertSame(OperationalContextEnum::MERCHANT_ONBOARDING, $context->operationalContext);
    }

    public function test_super_admin_identity_context_is_explicit_and_platform_scoped(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate(RoleEnum::SUPER_ADMIN->value, 'web');

        $user = \App\Models\User::factory()->superAdmin()->create();

        $context = app(IdentityContextResolver::class)->resolve($user);

        $this->assertSame(ActorContextEnum::SUPER_ADMIN, $context->actorType);
        $this->assertSame(AuthDomainEnum::PLATFORM, $context->authDomain);
        $this->assertFalse($context->onboardingRequired);
        $this->assertSame(OperationalContextEnum::PLATFORM_ADMIN, $context->operationalContext);
    }
}
