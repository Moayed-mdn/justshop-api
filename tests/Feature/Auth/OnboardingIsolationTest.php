<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Auth\OnboardingStepEnum;
use App\Models\User;
use App\Support\Security\SecurityEventLoggerInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class OnboardingIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_actor_bypasses_onboarding_and_bypass_is_telemetried(): void
    {
        Log::spy();

        $customer = User::factory()->customer()->verified()->create();

        Route::middleware(['api', 'auth:sanctum', 'onboarding.completed'])
            ->get('/api/v1/test-auth/onboarding-bypass', fn () => response()->json(['ok' => true]));

        $response = $this->actingAs($customer)->getJson('/api/v1/test-auth/onboarding-bypass');

        $response->assertOk()->assertJson(['ok' => true]);

        Log::shouldHaveReceived('info')->with(
            'identity.onboarding.bypassed',
            Mockery::on(fn (array $context): bool => ($context['onboarding_reason'] ?? null) === 'customer_actor_bypass'
                && (($context['identity_context']['actor_type'] ?? null) === 'customer')),
        )->atLeast()->once();
    }

    public function test_merchant_actor_is_evaluated_and_denied_when_onboarding_is_incomplete(): void
    {
        Log::spy();
        app()->bind(SecurityEventLoggerInterface::class, fn () => new class implements SecurityEventLoggerInterface {
            public function record(\App\Support\Security\SecurityEventType|string $event, array $metadata = [], string $level = 'warning'): void {}
        });

        $merchant = User::factory()->merchant()->verified()->create([
            'onboarding_step' => OnboardingStepEnum::CREATE_STORE,
        ]);

        Route::middleware(['api', 'auth:sanctum', 'onboarding.completed'])
            ->get('/api/v1/test-auth/onboarding-enforced', fn () => response()->json(['ok' => true]));

        $response = $this->actingAs($merchant)->getJson('/api/v1/test-auth/onboarding-enforced');

        $response->assertForbidden()
            ->assertJsonPath('code', 'AUTH_002');

        Log::shouldHaveReceived('info')->with(
            'identity.onboarding.evaluated',
            Mockery::on(fn (array $context): bool => ($context['onboarding_applies'] ?? null) === true
                && ($context['current_onboarding_step'] ?? null) === OnboardingStepEnum::CREATE_STORE->value
                && (($context['identity_context']['actor_type'] ?? null) === 'merchant')),
        )->atLeast()->once();
    }
}
