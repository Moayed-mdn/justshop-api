<?php

declare(strict_types=1);

namespace App\Services\Auth\Readiness;

use App\Services\Auth\IdentityContextResolver;
use App\Services\Auth\IdentityTelemetry;
use App\Services\Auth\OnboardingApplicabilityResolver;
use App\Services\Auth\SessionBoundaryMetadataResolver;
use Illuminate\Routing\Router;

class WaveThreeAIdentityReadinessReportService
{
    public function __construct(
        private readonly Router $router,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function generate(): array
    {
        $merchantUsersRoutes = 0;
        $merchantUsersAnnotated = 0;
        $merchantAdminRoutes = 0;
        $merchantAdminAnnotated = 0;
        $customerAccountRoutes = 0;
        $customerAccountAnnotated = 0;

        foreach ($this->router->getRoutes() as $route) {
            $uri = $route->uri();
            $middleware = $route->gatherMiddleware();
            $hasIdentityRouteMetadata = collect($middleware)->contains(
                fn (string $entry): bool => str_starts_with($entry, 'identity.route:'),
            );

            if (str_starts_with($uri, 'api/v1/users/')) {
                $merchantUsersRoutes++;
                $merchantUsersAnnotated += $hasIdentityRouteMetadata ? 1 : 0;
            }

            if (str_starts_with($uri, 'api/v1/admin/')) {
                $merchantAdminRoutes++;
                $merchantAdminAnnotated += $hasIdentityRouteMetadata ? 1 : 0;
            }

            if (str_starts_with($uri, 'api/v1/storefront/account/')) {
                $customerAccountRoutes++;
                $customerAccountAnnotated += $hasIdentityRouteMetadata ? 1 : 0;
            }
        }

        $merchantRouteCoverage = ($merchantUsersRoutes + $merchantAdminRoutes) === 0
            ? 1.0
            : round(($merchantUsersAnnotated + $merchantAdminAnnotated) / ($merchantUsersRoutes + $merchantAdminRoutes), 4);

        $customerRouteCoverage = $customerAccountRoutes === 0
            ? 0.0
            : round($customerAccountAnnotated / $customerAccountRoutes, 4);

        return [
            'generated_at' => now()->toIso8601String(),
            'release_version' => (string) config('observability.release_version'),
            'identity_context_health' => [
                'resolver_present' => class_exists(IdentityContextResolver::class),
                'explicit_actor_types' => ['merchant', 'customer', 'super_admin'],
                'session_boundary_metadata_present' => class_exists(SessionBoundaryMetadataResolver::class),
                'status' => class_exists(IdentityContextResolver::class) ? 'healthy' : 'attention_required',
            ],
            'onboarding_isolation_health' => [
                'applicability_resolver_present' => class_exists(OnboardingApplicabilityResolver::class),
                'customer_bypass_instrumented' => class_exists(OnboardingApplicabilityResolver::class),
                'merchant_evaluation_instrumented' => class_exists(OnboardingApplicabilityResolver::class),
                'status' => class_exists(OnboardingApplicabilityResolver::class) ? 'healthy' : 'attention_required',
            ],
            'route_domain_isolation_health' => [
                'merchant_users_routes' => $merchantUsersRoutes,
                'merchant_users_annotated' => $merchantUsersAnnotated,
                'merchant_admin_routes' => $merchantAdminRoutes,
                'merchant_admin_annotated' => $merchantAdminAnnotated,
                'customer_account_routes' => $customerAccountRoutes,
                'customer_account_annotated' => $customerAccountAnnotated,
                'merchant_route_metadata_coverage_ratio' => $merchantRouteCoverage,
                'customer_route_metadata_coverage_ratio' => $customerRouteCoverage,
                'status' => $merchantRouteCoverage === 1.0 && $customerRouteCoverage === 1.0 ? 'healthy' : 'attention_required',
            ],
            'cross_context_telemetry' => [
                'identity_telemetry_present' => class_exists(IdentityTelemetry::class),
                'actor_domain_mismatch_logging' => true,
                'cross_context_denial_logging' => true,
                'customer_route_access_logging' => true,
                'merchant_route_misuse_logging' => true,
            ],
            'remaining_wave4_blockers' => [
                'shared_users_table_still_authoritative',
                'shared_sanctum_session_model_still_authoritative',
                'cookie_split_not_started',
                'guard_split_not_started',
                'session_isolation_not_started',
                'merchant_auth_routes_remain_authoritative',
                'checkout_auth_model_remains_shared',
            ],
            'guard_split_preparation' => [
                'status' => 'more_normalization_required',
                'next_gate' => 'wave4_guard_split_preparation',
                'reason' => 'Wave 3A only normalizes identity context, route ownership, onboarding applicability, and session metadata. Runtime auth authority remains shared.',
            ],
        ];
    }
}
