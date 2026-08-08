<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Store\StoreRoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Services\Auth\Membership\MembershipResolver;
use App\Services\Auth\PermissionResolver;
use App\Support\FeatureFlags\FeatureFlag;
use App\Support\Observability\RequestTraceContextManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MembershipAndPermissionResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_store_context_enrichment_uses_membership_resolver_consistently(): void
    {
        [$user, $store, $membershipId] = $this->createMerchantMembership();

        Route::middleware(['api', 'auth:sanctum', 'store.context'])
            ->get('/api/v1/test-wave2/membership/{store}', function (
                RequestTraceContextManager $traceContext,
                MembershipResolver $membershipResolver
            ) use ($user) {
                return response()->json([
                    'trace' => $traceContext->current()->toLogContext(),
                    'resolver_membership_id' => $membershipResolver->resolveForStore($user, app('currentStore'))?->membershipId,
                ]);
            });

        $response = $this->actingAs($user)->getJson("/api/v1/test-wave2/membership/{$store->id}");

        $response->assertOk()
            ->assertJsonPath('trace.membership_id', $membershipId)
            ->assertJsonPath('resolver_membership_id', $membershipId)
            ->assertJsonPath('trace.store_id', $store->id);
    }

    public function test_permission_resolution_preserves_current_outcome_while_dual_resolving(): void
    {
        $this->setFlag('rbac.dual_resolve', true);
        $this->setFlag('rbac.resolver.v2', false);

        Log::spy();

        [$user, $store, $membershipId] = $this->createMerchantMembership([
            'product.view',
            'product.update',
        ]);

        $result = app(PermissionResolver::class)->resolveResult($user, $store);

        $this->assertSame(['product.update', 'product.view'], $result->permissions());
        $this->assertSame($store->id, $result->storeId);
        $this->assertSame($membershipId, $result->membershipId);
        $this->assertSame(StoreRoleEnum::STORE_ADMIN->value, $result->membershipRole);
        $this->assertTrue($result->storeScoped);
        $this->assertFalse($result->superAdminBypass);

        Log::shouldHaveReceived('info')->with(
            'authorization.permission.parity_checked',
            Mockery::on(fn (array $context): bool => ($context['drift_count'] ?? null) === 0),
        )->atLeast()->once();
    }

    /**
     * @param string[] $permissions
     * @return array{0: User, 1: Store, 2: int}
     */
    private function createMerchantMembership(array $permissions = ['product.view']): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate(StoreRoleEnum::STORE_ADMIN->value, 'web');
        $role->syncPermissions($permissions);

        $user = User::factory()->merchant()->create();
        $store = Store::factory()->for($user, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        $membershipId = (int) DB::table('store_user')
            ->where('user_id', $user->id)
            ->where('store_id', $store->id)
            ->value('id');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return [$user->fresh(), $store->fresh(), $membershipId];
    }

    private function setFlag(string $flag, mixed $value): void
    {
        FeatureFlag::setValue($flag, $value);
    }
}
