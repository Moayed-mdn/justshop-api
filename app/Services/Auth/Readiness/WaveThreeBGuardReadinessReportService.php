<?php

declare(strict_types=1);

namespace App\Services\Auth\Readiness;

use App\Services\Auth\CustomerGuardShadowResolver;
use App\Services\Auth\FrontendSessionMetadataResolver;
use App\Services\Auth\GuardShadowAnalyzer;
use App\Services\Auth\MerchantGuardShadowResolver;
use App\Services\Auth\SessionGuardTelemetry;
use App\Services\Auth\SessionOwnershipResolver;
use Illuminate\Routing\Router;

class WaveThreeBGuardReadinessReportService
{
    public function __construct(
        private readonly Router $router,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function generate(): array
    {
        $merchantAnnotatedRoutes = 0;
        $customerAnnotatedRoutes = 0;
        $logoutRoutes = 0;
        $csrfRoutes = 0;

        foreach ($this->router->getRoutes() as $route) {
            $uri = $route->uri();
            $middleware = $route->gatherMiddleware();

            if (collect($middleware)->contains(fn (string $entry): bool => str_starts_with($entry, 'identity.route:merchant_'))) {
                $merchantAnnotatedRoutes++;
            }

            if (collect($middleware)->contains(fn (string $entry): bool => str_starts_with($entry, 'identity.route:customer_account'))) {
                $customerAnnotatedRoutes++;
            }

            if (str_ends_with($uri, '/logout')) {
                $logoutRoutes++;
            }

            if ($uri === 'api/sanctum/csrf-cookie' || $uri === 'sanctum/csrf-cookie') {
                $csrfRoutes++;
            }
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'release_version' => (string) config('observability.release_version'),
            'session_ownership_model' => [
                'resolver_present' => class_exists(SessionOwnershipResolver::class),
                'request_scoped_only' => true,
                'runtime_authority_unchanged' => true,
                'status' => class_exists(SessionOwnershipResolver::class) ? 'healthy' : 'attention_required',
            ],
            'guard_shadow_infrastructure' => [
                'merchant_guard_shadow_present' => class_exists(MerchantGuardShadowResolver::class),
                'customer_guard_shadow_present' => class_exists(CustomerGuardShadowResolver::class),
                'guard_shadow_analyzer_present' => class_exists(GuardShadowAnalyzer::class),
                'observe_only' => true,
                'status' => class_exists(MerchantGuardShadowResolver::class) && class_exists(CustomerGuardShadowResolver::class) ? 'healthy' : 'attention_required',
            ],
            'session_contamination_readiness' => [
                'telemetry_present' => class_exists(SessionGuardTelemetry::class),
                'merchant_annotated_routes' => $merchantAnnotatedRoutes,
                'customer_annotated_routes' => $customerAnnotatedRoutes,
                'cross_domain_detection_enabled' => true,
                'bootstrap_misuse_detection_enabled' => true,
                'logout_ambiguity_detection_enabled' => true,
            ],
            'logout_and_csrf_preparation' => [
                'logout_routes_detected' => $logoutRoutes,
                'csrf_routes_detected' => $csrfRoutes,
                'logout_tracing_enabled' => class_exists(SessionGuardTelemetry::class),
                'csrf_tracing_enabled' => class_exists(SessionGuardTelemetry::class),
            ],
            'frontend_session_metadata' => [
                'resolver_present' => class_exists(FrontendSessionMetadataResolver::class),
                'fields' => ['auth_domain', 'actor_type', 'route_domain', 'onboarding_applicable', 'future_guard_hint'],
                'backward_compatible' => true,
            ],
            'remaining_guard_split_blockers' => [
                'shared_users_table_still_authoritative',
                'shared_sanctum_session_still_authoritative',
                'shared_session_cookie_still_authoritative',
                'guard_split_not_activated',
                'cookie_split_not_started',
                'separate_auth_providers_not_started',
                'session_isolation_not_enforced',
                'checkout_auth_rewrite_not_started',
            ],
            'guard_split_gate' => [
                'status' => 'preparation_only',
                'reason' => 'Wave 3B adds session ownership metadata, guard shadowing, contamination telemetry, logout tracing, CSRF preparation, and frontend session metadata without changing runtime auth authority.',
            ],
        ];
    }
}
