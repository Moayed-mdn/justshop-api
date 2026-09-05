<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Enums\RoleEnum;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * HTTP-level coverage for /api/v1/platform/billing/plans (PlatformPlanController).
 *
 * GAP THIS FILE CLOSES: the pre-existing PlanManagementTest.php only calls
 * CreatePlanAction/UpdatePlanAction/DeletePlanAction directly — it never hits the actual
 * route, so the platform.authority:platform_admin middleware, StorePlanRequest validation,
 * and the controller's HTTP status mapping had no coverage at all.
 *
 * All authorization assertions here were verified by reading the real middleware chain
 * (not guessed): routes/api.php wraps this group in
 * ['auth:sanctum', 'identity.route:platform,platform,enforce', 'platform.context',
 * 'platform.authority:platform_admin'].
 */
class PlanManagementHttpTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/platform/billing/plans';

    private User $platformAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->platformAdmin = User::factory()->create();
        $this->platformAdmin->assignRole(RoleEnum::SUPER_ADMIN->value);

        $this->mock(\App\Contracts\Billing\BillingProviderInterface::class, function ($mock) {
            $mock->shouldReceive('createPrice')
                ->andReturnUsing(fn () => [
                    'provider_product_id' => 'prod_' . \Illuminate\Support\Str::random(14),
                    'provider_price_id' => 'price_' . \Illuminate\Support\Str::random(14),
                ]);
            $mock->shouldReceive('archivePrice')->andReturnNull();
        });
    }

    private function validPlanPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'http-test-plan-' . uniqid(),
            'name' => ['en' => 'HTTP Test Plan'],
            'description' => ['en' => 'Created via HTTP test'],
            'tier' => 'starter',
            'tier_rank' => 1,
            'is_public' => true,
            'is_active' => true,
            'trial_days' => 14,
            'sort_order' => 10,
            'features' => [
                ['feature_key' => 'products.max', 'value_type' => 'limit', 'limit_value' => 100, 'boolean_value' => null],
            ],
            'prices' => [
                ['billing_cycle' => 'monthly', 'currency' => 'USD', 'amount_cents' => 1900, 'provider' => 'stripe'],
            ],
        ], $overrides);
    }

    /** @test */
    public function test_platform_admin_can_create_a_plan_via_http(): void
    {
        $response = $this->actingAs($this->platformAdmin, 'sanctum')
            ->postJson(self::ENDPOINT, $this->validPlanPayload(['code' => 'http-happy-path']));

        $response->assertStatus(201);
        $response->assertJsonPath('data.code', 'http-happy-path');

        $this->assertDatabaseHas('plans', [
            'code' => 'http-happy-path',
            'tier' => 'starter',
        ]);
    }

    /** @test */
    public function test_guest_cannot_list_plans(): void
    {
        // No actingAs() at all: auth:sanctum runs before any of the platform-domain
        // middleware, so this is a plain Laravel AuthenticationException -> 401.
        $response = $this->getJson(self::ENDPOINT);

        $response->assertStatus(401);
    }

    /** @test */
    public function test_merchant_actor_cannot_access_plan_management(): void
    {
        // A merchant (store owner/staff, no platform role at all) is a domain mismatch:
        // identity.route's ownership check throws InvalidIdentityDomainAccessException,
        // which IS correctly mapped by ExceptionRegistrar to 403.
        $merchant = User::factory()->create();
        $store = Store::factory()->create(['owner_id' => $merchant->id]);
        $merchant->stores()->attach($store, ['role' => 'store_admin']);

        $response = $this->actingAs($merchant, 'sanctum')
            ->getJson(self::ENDPOINT);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_creating_plan_without_required_fields_returns_422(): void
    {
        $response = $this->actingAs($this->platformAdmin, 'sanctum')
            ->postJson(self::ENDPOINT, [
                // 'code' omitted entirely, 'features' and 'prices' omitted -> StorePlanRequest
                // requires all three ('required' rules on code, features (min:1), prices (min:1)).
                'name' => ['en' => 'Incomplete Plan'],
                'tier' => 'starter',
                'tier_rank' => 1,
                'is_public' => true,
                'is_active' => true,
                'trial_days' => 14,
                'sort_order' => 10,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code', 'features', 'prices']);
        $this->assertDatabaseCount('plans', 0);
    }

    /** @test */
    public function test_creating_plan_with_duplicate_code_returns_422_with_domain_error(): void
    {
        $this->actingAs($this->platformAdmin, 'sanctum')
            ->postJson(self::ENDPOINT, $this->validPlanPayload(['code' => 'dup-http-test']))
            ->assertStatus(201);

        // Edge case: the SAME code submitted again must be rejected as a DomainException
        // (BIL_014, per CreatePlanAction / PlanManagementTest), mapped to 422 by the
        // controller's catch(\DomainException) block — not a 500, not a silent duplicate row.
        $response = $this->actingAs($this->platformAdmin, 'sanctum')
            ->postJson(self::ENDPOINT, $this->validPlanPayload(['code' => 'dup-http-test']));

        $response->assertStatus(422);
        $this->assertDatabaseCount('plans', 1);
    }

    /**
     * REGRESSION / DEFECT-DOCUMENTING TEST — currently fails against real app code.
     *
     * App\Exceptions\Auth\UnauthorizedPlatformAccessException (thrown by
     * EnforcePlatformAuthority when an authenticated platform actor lacks the specific
     * required authority — here: a 'support' role user hitting a platform_admin-only
     * route) is a plain `Exception`. It does not extend DomainException, BaseApiException,
     * AuthenticationException, AuthorizationException, or implement HttpExceptionInterface.
     * App\Exceptions\ExceptionRegistrar has no branch for it, so it falls through to the
     * final catch-all and is returned as HTTP 500 (ErrorCode::SYS_001) instead of 403.
     *
     * Verified statically end-to-end (no execution needed, since this environment cannot
     * run phpunit — see final report):
     *  - IdentityContextResolver maps a 'support' role to actorType=SUPPORT_AGENT,
     *    authDomain=PLATFORM (Rule 2).
     *  - ApplyIdentityRouteContext::allowedActorTypes() allows SUPPORT_AGENT on PLATFORM
     *    routes, so a support user passes identity.route cleanly (no domain mismatch).
     *  - PlatformAuthorityResolver maps SUPPORT_AGENT -> PlatformAuthorityDomainEnum::SUPPORT_AGENT.
     *  - EnforcePlatformAuthority requires exactly 'platform_admin' for this route group
     *    and throws UnauthorizedPlatformAccessException for anything else.
     *  - This exception class is absent from every instanceof check in ExceptionRegistrar.
     *
     * This is not specific to plan management — it affects every platform.authority-protected
     * route in the app. This test encodes the correct/intended contract (403 Forbidden for
     * "authenticated platform actor, wrong specific authority"), so it will start passing
     * once ExceptionRegistrar gains a branch for UnauthorizedPlatformAccessException.
     */
    public function test_support_role_user_without_platform_admin_authority_cannot_manage_plans(): void
    {
        // PermissionSeeder (seeded in setUp) only creates super_admin/store_admin/staff/
        // customer roles, not 'support' — create it here so assignRole() doesn't throw
        // Spatie\Permission\Exceptions\RoleDoesNotExist.
        Role::firstOrCreate(['name' => RoleEnum::SUPPORT->value]);

        $supportUser = User::factory()->create();
        $supportUser->assignRole(RoleEnum::SUPPORT->value);

        $response = $this->actingAs($supportUser, 'sanctum')
            ->postJson(self::ENDPOINT, $this->validPlanPayload());

        $response->assertStatus(403);
        $this->assertDatabaseCount('plans', 0);
    }
}
