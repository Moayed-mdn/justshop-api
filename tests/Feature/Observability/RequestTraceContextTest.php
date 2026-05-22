<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Models\Store;
use App\Models\User;
use App\Support\Observability\RequestTraceContextManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RequestTraceContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_correlation_id_is_generated_and_returned_in_response_headers(): void
    {
        Route::middleware('api')->get('/api/v1/test-observability/correlation', function (
            RequestTraceContextManager $traceContext
        ) {
            return response()->json($traceContext->current()->toLogContext());
        });

        $response = $this->getJson('/api/v1/test-observability/correlation');

        $correlationId = $response->headers->get('X-Correlation-ID');

        $response->assertOk()
            ->assertHeader('X-Correlation-ID')
            ->assertJsonPath('correlation_id', $correlationId);

        $this->assertMatchesRegularExpression(
            '/^[a-f0-9-]{36}$/',
            (string) $correlationId,
        );
    }

    public function test_incoming_correlation_id_is_preserved(): void
    {
        Route::middleware('api')->get('/api/v1/test-observability/incoming-correlation', function (
            RequestTraceContextManager $traceContext
        ) {
            return response()->json($traceContext->current()->toLogContext());
        });

        $incomingCorrelationId = '4b8fd6c2-4e6b-4d78-9fd8-11d12cdf0d51';

        $response = $this->withHeaders([
            'X-Correlation-ID' => $incomingCorrelationId,
        ])->getJson('/api/v1/test-observability/incoming-correlation');

        $response->assertOk()
            ->assertHeader('X-Correlation-ID', $incomingCorrelationId)
            ->assertJsonPath('correlation_id', $incomingCorrelationId);
    }

    public function test_trace_context_is_enriched_by_store_context_middleware(): void
    {
        /** @var User $user */
        $user = User::factory()->merchant()->create();
        /** @var Store $store */
        $store = Store::factory()->for($user, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => 'store_admin']);

        Route::middleware(['api', 'auth:sanctum', 'store.context'])
            ->get('/api/v1/stores/{store}/test-observability/trace', function (
                RequestTraceContextManager $traceContext
            ) {
                return response()->json($traceContext->current()->toLogContext());
            });

        $response = $this->actingAs($user)->getJson(
            "/api/v1/stores/{$store->id}/test-observability/trace"
        );

        $response->assertOk()
            ->assertJsonPath('actor_id', $user->id)
            ->assertJsonPath('actor_type', 'merchant')
            ->assertJsonPath('store_id', $store->id)
            ->assertJsonPath('api_domain', 'merchant_admin');

        $this->assertNotNull($response->json('membership_id'));
    }

    public function test_identity_route_context_enriches_trace_with_route_and_session_annotations(): void
    {
        $user = User::factory()->customer()->create();

        Route::middleware(['api', 'auth:sanctum', 'identity.route:customer_account,customer,enforce'])
            ->get('/api/v1/storefront/account/test-observability/trace', function (
                RequestTraceContextManager $traceContext
            ) {
                return response()->json($traceContext->current()->toLogContext());
            });

        $response = $this->actingAs($user)->getJson('/api/v1/storefront/account/test-observability/trace');

        $response->assertOk()
            ->assertJsonPath('actor_id', $user->id)
            ->assertJsonPath('actor_type', 'customer')
            ->assertJsonPath('auth_domain', 'customer')
            ->assertJsonPath('route_domain', 'customer_account')
            ->assertJsonPath('route_owner_auth_domain', 'customer')
            ->assertJsonPath('session_auth_domain', 'customer')
            ->assertJsonPath('session_actor_type', 'customer')
            ->assertJsonPath('session_actor_id', $user->id)
            ->assertJsonPath('session_authority_model', 'shared_sanctum_session')
            ->assertJsonPath('session_isolation_state', 'shared_until_guard_split');
    }

    public function test_exception_responses_keep_existing_shape_and_gain_correlation_header(): void
    {
        Route::middleware('api')->get('/api/v1/test-observability/error', function () {
            throw new \Exception('Custom server error message');
        });

        $response = $this->getJson('/api/v1/test-observability/error');

        $response->assertStatus(500)
            ->assertHeader('X-Correlation-ID')
            ->assertJson([
                'status' => false,
                'message' => 'Custom server error message',
            ]);
    }
}
