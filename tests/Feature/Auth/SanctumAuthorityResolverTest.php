<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\Models\User;
use App\Services\Auth\Sanctum\SanctumAuthorityResolver;
use App\Services\Auth\SessionOwnershipManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mockery;
use Tests\TestCase;

class SanctumAuthorityResolverTest extends TestCase
{
    use RefreshDatabase;

    private SanctumAuthorityResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(SanctumAuthorityResolver::class);
    }

    public function test_merchant_session_resolves_to_merchant_authority(): void
    {
        $merchant = User::factory()->merchant()->verified()->create([
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);

        $this->actingAs($merchant);
        Session::put('auth_domain', 'merchant');
        Session::put('actor_type', 'merchant');
        Session::put('actor_id', (int) $merchant->id);
        $context = $this->resolver->resolve(request());

        $this->assertSame('merchant', $context['auth_domain']);
        $this->assertSame('merchant', $context['actor_type']);
        $this->assertSame($merchant->id, $context['actor_id']);
        $this->assertSame('merchant', $context['resolved_guard']);
        $this->assertTrue($context['is_authenticated']);
    }

    public function test_customer_session_resolves_to_customer_authority(): void
    {
        $customer = User::factory()->customer()->verified()->create();

        $this->actingAs($customer);
        Session::put('auth_domain', 'customer');
        Session::put('actor_type', 'customer');
        Session::put('actor_id', (int) $customer->id);
        $context = $this->resolver->resolve(request());

        $this->assertSame('customer', $context['auth_domain']);
        $this->assertSame('customer', $context['actor_type']);
        $this->assertSame($customer->id, $context['actor_id']);
        $this->assertSame('customer', $context['resolved_guard']);
        $this->assertTrue($context['is_authenticated']);
    }

    public function test_unauthenticated_session_resolves_to_web_guard(): void
    {
        $context = $this->resolver->resolve(request());

        $this->assertNull($context['auth_domain']);
        $this->assertNull($context['actor_type']);
        $this->assertNull($context['actor_id']);
        $this->assertSame('web', $context['resolved_guard']);
        $this->assertFalse($context['is_authenticated']);
    }

    public function test_resolution_emits_authority_log(): void
    {
        Log::spy();

        $merchant = User::factory()->merchant()->verified()->create([
            'onboarding_step' => OnboardingStepEnum::COMPLETED,
        ]);

        $this->actingAs($merchant);
        Session::put('auth_domain', 'merchant');
        Session::put('actor_type', 'merchant');
        Session::put('actor_id', (int) $merchant->id);
        $this->resolver->resolve(request());

        Log::shouldHaveReceived('info')->with(
            'sanctum.authority.resolved',
            Mockery::on(fn (array $context): bool => ($context['auth_domain'] ?? null) === 'merchant'
                && ($context['resolved_guard'] ?? null) === 'merchant'),
        )->atLeast()->once();
    }
}
