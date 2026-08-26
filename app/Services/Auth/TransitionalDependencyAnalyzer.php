<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Illuminate\Support\Facades\Route;

/**
 * Transitional Dependency Analyzer
 * 
 * Wave 6: Measure transitional dependency.
 * 
 * Identify:
 * - Remaining fallback authority usage
 * - Remaining shared transitional routes
 * - Shadow-only execution paths
 * - Legacy compatibility dependencies
 */
class TransitionalDependencyAnalyzer
{
    public function analyze(): array
    {
        return [
            'fallback_authority_usage' => $this->analyzeFallbackAuthority(),
            'shared_transitional_routes' => $this->analyzeSharedTransitionalRoutes(),
            'shadow_only_paths' => $this->analyzeShadowOnlyPaths(),
            'legacy_compatibility_dependencies' => $this->analyzeLegacyDependencies(),
            'normalization_candidates' => $this->identifyNormalizationCandidates(),
        ];
    }

    private function analyzeFallbackAuthority(): array
    {
        return [
            'web_guard_fallback_enabled' => !config('features.auth.guard_split.enforce.default'),
            'shared_session_fallback_enabled' => true,
            'implicit_auth_helper_usage' => 'unknown', // Requires static analysis
        ];
    }

    private function analyzeSharedTransitionalRoutes(): array
    {
        $routes = collect(Route::getRoutes())->filter(function ($route) {
            $middleware = $route->middleware();
            foreach ($middleware as $m) {
                if (str_contains($m, 'identity.route:shared_transitional')) {
                    return true;
                }
            }
            return false;
        });

        return [
            'count' => $routes->count(),
            'routes' => $routes->map(fn ($route) => [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'name' => $route->getName(),
            ])->values()->toArray(),
        ];
    }

    private function analyzeShadowOnlyPaths(): array
    {
        return [
            'guard_shadow_enabled' => config('features.auth.guard_split.shadow.default'),
            'guard_split_enabled' => config('features.auth.guard_split.enabled.default'),
            'guard_split_enforced' => config('features.auth.guard_split.enforce.default'),
            'shadow_only_mode' => config('features.auth.guard_split.shadow.default') 
                && !config('features.auth.guard_split.enabled.default'),
        ];
    }

    private function analyzeLegacyDependencies(): array
    {
        return [
            'shared_web_guard' => true, // Still using 'web' guard as default
            'shared_user_provider' => true, // All guards use 'users' provider
            'shared_session_table' => true, // Single sessions table
            'csrf_ownership_preparation' => true, // Still in preparation mode
        ];
    }

    private function identifyNormalizationCandidates(): array
    {
        $candidates = [];

        // If guard split is enabled and stable, it's a normalization candidate
        if (config('features.auth.guard_split.enabled.default')) {
            $candidates[] = [
                'domain' => 'guard_split',
                'status' => 'enabled',
                'ready_for_normalization' => config('features.auth.guard_split.enforce.default'),
            ];
        }

        // Check route domains
        $routes = collect(Route::getRoutes());
        $enforcedRoutes = $routes->filter(function ($route) {
            $middleware = $route->middleware();
            foreach ($middleware as $m) {
                if (str_contains($m, 'identity.route:') && str_contains($m, ',enforce')) {
                    return true;
                }
            }
            return false;
        });

        $candidates[] = [
            'domain' => 'route_enforcement',
            'enforced_routes_count' => $enforcedRoutes->count(),
            'total_routes_count' => $routes->count(),
            'enforcement_percentage' => $routes->count() > 0 
                ? round(($enforcedRoutes->count() / $routes->count()) * 100, 2) 
                : 0,
        ];

        return $candidates;
    }

    public function getTransitionalDebtScore(): int
    {
        $analysis = $this->analyze();
        $score = 0;

        // Fallback authority usage (0-30 points)
        if ($analysis['fallback_authority_usage']['web_guard_fallback_enabled']) {
            $score += 30;
        }

        // Shared transitional routes (0-25 points)
        $transitionalRouteCount = $analysis['shared_transitional_routes']['count'];
        $score += min(25, $transitionalRouteCount * 5);

        // Shadow-only mode (0-20 points)
        if ($analysis['shadow_only_paths']['shadow_only_mode']) {
            $score += 20;
        }

        // Legacy dependencies (0-25 points)
        $legacyCount = count(array_filter($analysis['legacy_compatibility_dependencies']));
        $score += min(25, $legacyCount * 6);

        return min(100, $score);
    }
}
