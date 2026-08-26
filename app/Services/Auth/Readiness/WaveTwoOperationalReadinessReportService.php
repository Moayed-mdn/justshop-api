<?php

declare(strict_types=1);

namespace App\Services\Auth\Readiness;

use App\Services\Auth\Bootstrap\BootstrapDependencyProfiler;
use App\Services\Auth\Bootstrap\BootstrapShadowParityService;
use App\Services\Auth\Bootstrap\BootstrapTelemetry;
use App\Services\Auth\Drift\AuthorizationDriftReportService;
use App\Services\Auth\Drift\AuthorizationOwnershipTriageService;
use App\Services\Auth\Membership\MembershipResolver;
use App\Services\Auth\Policy\PolicyOwnershipReportService;
use Illuminate\Routing\Router;

class WaveTwoOperationalReadinessReportService
{
    public function __construct(
        private readonly AuthorizationDriftReportService $driftReportService,
        private readonly AuthorizationOwnershipTriageService $triageService,
        private readonly PolicyOwnershipReportService $policyOwnershipReportService,
        private readonly Router $router,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function generate(?string $allowlistPath = null, ?string $baselinePath = null): array
    {
        $driftReport = $this->driftReportService->generate($allowlistPath, $baselinePath);
        $triageReport = $this->triageService->generate($allowlistPath, $baselinePath);
        $policyOwnershipReport = $this->policyOwnershipReportService->generate();

        $storeScopedRoutes = 0;
        $storeScopedRoutesWithStoreContext = 0;

        foreach ($this->router->getRoutes() as $route) {
            if (!str_contains($route->uri(), '{store}')) {
                continue;
            }

            $storeScopedRoutes++;

            if (in_array('store.context', $route->gatherMiddleware(), true)) {
                $storeScopedRoutesWithStoreContext++;
            }
        }

        $bootstrapTests = [
            'tests/Feature/Auth/BootstrapBoundaryNormalizationTest.php',
            'tests/Feature/Auth/BootstrapStabilizationHardeningTest.php',
        ];

        $hiddenAuthorizationCount = (int) (($driftReport['summary']['by_category']['hidden_authorization'] ?? 0));
        $genericCurrentStoreCount = (int) (($driftReport['summary']['by_type']['generic_current_store_authorize'] ?? 0));
        $policySummary = $policyOwnershipReport['summary'];

        return [
            'generated_at' => now()->toIso8601String(),
            'release_version' => (string) config('observability.release_version'),
            'bootstrap_parity_health' => [
                'shadow_parity_instrumented' => class_exists(BootstrapShadowParityService::class),
                'resolver_timing_instrumented' => method_exists(BootstrapTelemetry::class, 'measure'),
                'dependency_observability_instrumented' => class_exists(BootstrapDependencyProfiler::class),
                'bootstrap_test_files_present' => array_values(array_filter($bootstrapTests, fn (string $path): bool => file_exists(base_path($path)))),
            ],
            'resolver_stability' => [
                'resolver_count' => 5,
                'timing_distribution_logging' => true,
                'timing_validation_mode' => 'observability_only',
            ],
            'drift_counts' => $driftReport['summary'],
            'drift_trend' => $driftReport['trend'],
            'authorization_triage' => $triageReport['summary'],
            'normalization_progress' => [
                'normalized_domains' => $policyOwnershipReport['normalized_domain_metrics'],
                'generic_current_store_remaining' => $genericCurrentStoreCount,
                'hidden_authorization_remaining' => $hiddenAuthorizationCount,
                'domain_ownership_health_score' => $policySummary['domain_ownership_health_score'],
            ],
            'tenant_isolation_status' => [
                'store_scoped_routes' => $storeScopedRoutes,
                'store_scoped_routes_with_store_context' => $storeScopedRoutesWithStoreContext,
                'store_context_coverage_ratio' => $storeScopedRoutes === 0 ? 1.0 : round($storeScopedRoutesWithStoreContext / $storeScopedRoutes, 4),
                'status' => $storeScopedRoutes === $storeScopedRoutesWithStoreContext ? 'healthy' : 'attention_required',
            ],
            'policy_instrumentation_coverage' => [
                'total_routes_mapped' => $policySummary['total_routes'],
                'routes_with_explicit_policy' => $policySummary['routes_with_explicit_policy'],
                'routes_with_hidden_fallback' => $policySummary['routes_with_hidden_fallback'],
                'routes_using_generic_current_store' => $policySummary['routes_using_generic_current_store'],
                'middleware_only_authorization_routes' => $policySummary['middleware_only_authorization_routes'],
                'dual_authorization_routes' => $policySummary['dual_authorization_routes'],
            ],
            'observability_health' => [
                'correlation_header' => (string) config('observability.correlation_header'),
                'security_log_channel' => (string) config('observability.security_log_channel'),
                'membership_resolver_bound' => app()->bound(MembershipResolver::class),
            ],
            'wave3_gate' => [
                'status' => 'blocked',
                'blocked_by' => array_values(array_filter([
                    $genericCurrentStoreCount > 0 ? 'generic currentStore ownership drift remains outside normalized domains' : null,
                    $hiddenAuthorizationCount > 0 ? 'hidden authorization findings remain' : null,
                    (($driftReport['summary']['by_category']['permission_middleware_drift'] ?? 0) > 0) ? 'permission middleware drift remains outside normalized domains' : null,
                    ($policySummary['routes_with_hidden_fallback'] ?? 0) > 0 ? 'hidden fallback authorization paths remain in non-normalized domains' : null,
                    'production-like parity telemetry review still required',
                ])),
            ],
        ];
    }
}
