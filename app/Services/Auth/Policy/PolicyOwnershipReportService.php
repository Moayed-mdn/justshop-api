<?php

declare(strict_types=1);

namespace App\Services\Auth\Policy;

use App\Policies\AddressPolicy;
use App\Policies\BlogPostPolicy;
use App\Policies\BrandPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\DashboardPolicy;
use App\Policies\MembershipPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentMethodPolicy;
use App\Policies\ProductPolicy;
use App\Policies\StorePolicy;
use App\Policies\TagPolicy;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use ReflectionMethod;
use ReflectionNamedType;

class PolicyOwnershipReportService
{
    public function __construct(
        private readonly Router $router,
        private readonly Gate $gate,
        private readonly PolicyCapabilityCatalog $capabilityCatalog,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function generate(): array
    {
        $entries = [];

        foreach ($this->router->getRoutes() as $route) {
            $actionName = $route->getActionName();

            if (!is_string($actionName) || !str_contains($actionName, '@')) {
                continue;
            }

            if (!str_starts_with($actionName, 'App\\Http\\Controllers\\')) {
                continue;
            }

            [$controllerClass, $method] = explode('@', $actionName, 2);

            if (!class_exists($controllerClass) || !method_exists($controllerClass, $method)) {
                continue;
            }

            $reflectionMethod = new ReflectionMethod($controllerClass, $method);
            $methodSource = $this->extractMethodSource($reflectionMethod);
            $importMap = $this->buildImportMap($reflectionMethod->getFileName() ?: '');
            $authorizationCalls = $this->parseAuthorizationCalls($reflectionMethod, $methodSource, $importMap);
            $primaryAuthorization = $authorizationCalls[0] ?? null;
            $middleware = $route->gatherMiddleware();
            $requestAuthorizeStrategies = $this->requestAuthorizeStrategies($reflectionMethod);
            $permissionCapability = $this->capabilityCatalog->resolveFromMiddleware($middleware);
            $usesGenericCurrentStore = str_contains($methodSource, "app('currentStore')") || str_contains($methodSource, 'app("currentStore")');
            $expectedPolicyOwner = $this->resolveExpectedPolicyOwner($controllerClass);
            $requestAuthorizeIsAuthBearing = $this->hasAuthBearingRequestStrategy($requestAuthorizeStrategies);
            $fallbackPathUsed = $primaryAuthorization === null && ($permissionCapability !== null || $requestAuthorizeIsAuthBearing);
            $middlewareOnlyAuthorization = $primaryAuthorization === null && $permissionCapability !== null && !$requestAuthorizeIsAuthBearing;
            $dualAuthorizationPath = $primaryAuthorization !== null && ($permissionCapability !== null || $requestAuthorizeIsAuthBearing);
            $authorizationSource = match (true) {
                $dualAuthorizationPath => 'dual_authorization',
                $primaryAuthorization !== null => 'explicit_policy',
                $middlewareOnlyAuthorization => 'middleware_only',
                $fallbackPathUsed => 'fallback_only',
                default => 'none',
            };
            $policyUsed = $primaryAuthorization['policy'] ?? null;
            $ownershipMatchesExpected = $expectedPolicyOwner === null ? null : $policyUsed === $expectedPolicyOwner;
            $domain = $this->resolveDomain($controllerClass);

            $entries[] = [
                'route_uri' => '/' . ltrim($route->uri(), '/'),
                'route_name' => $route->getName(),
                'methods' => array_values(array_filter($route->methods(), fn (string $value): bool => $value !== 'HEAD')),
                'controller' => $controllerClass,
                'controller_method' => $method,
                'domain' => $domain,
                'policy_used' => $policyUsed,
                'policy_invoked' => $policyUsed !== null,
                'expected_policy_owner' => $expectedPolicyOwner,
                'ownership_matches_expected' => $ownershipMatchesExpected,
                'capability_used' => $permissionCapability
                    ?? $this->capabilityCatalog->resolve($policyUsed, $primaryAuthorization['ability'] ?? null, $middleware),
                'store_aware' => str_contains($route->uri(), '{store}')
                    || in_array('store.context', $middleware, true)
                    || (bool) ($primaryAuthorization['generic_current_store'] ?? false)
                    || $usesGenericCurrentStore,
                'generic_currentStore' => (bool) ($primaryAuthorization['generic_current_store'] ?? false) || $usesGenericCurrentStore,
                'hidden_fallback' => $fallbackPathUsed,
                'fallback_path_used' => $fallbackPathUsed,
                'middleware_only_authorization' => $middlewareOnlyAuthorization,
                'dual_authorization_path' => $dualAuthorizationPath,
                'authorization_source' => $authorizationSource,
                'authorization_calls' => $authorizationCalls,
                'request_authorize_strategies' => $requestAuthorizeStrategies,
                'middleware' => $middleware,
            ];
        }

        usort($entries, fn (array $left, array $right): int => [$left['route_uri'], $left['controller_method']] <=> [$right['route_uri'], $right['controller_method']]);

        $normalizedDomains = $this->buildNormalizedDomainMetrics($entries);
        $normalizedDomainScores = array_column($normalizedDomains, 'health_score');
        $domainOwnershipHealthScore = $normalizedDomainScores === []
            ? 0
            : (int) round(array_sum($normalizedDomainScores) / count($normalizedDomainScores));

        $report = [
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'total_routes' => count($entries),
                'routes_with_explicit_policy' => count(array_filter($entries, fn (array $entry): bool => $entry['policy_used'] !== null)),
                'routes_with_hidden_fallback' => count(array_filter($entries, fn (array $entry): bool => $entry['hidden_fallback'] === true)),
                'routes_using_generic_current_store' => count(array_filter($entries, fn (array $entry): bool => $entry['generic_currentStore'] === true)),
                'store_aware_routes' => count(array_filter($entries, fn (array $entry): bool => $entry['store_aware'] === true)),
                'middleware_only_authorization_routes' => count(array_filter($entries, fn (array $entry): bool => $entry['middleware_only_authorization'] === true)),
                'dual_authorization_routes' => count(array_filter($entries, fn (array $entry): bool => $entry['dual_authorization_path'] === true)),
                'expected_owner_matches' => count(array_filter($entries, fn (array $entry): bool => $entry['ownership_matches_expected'] === true)),
                'expected_owner_mismatches' => count(array_filter($entries, fn (array $entry): bool => $entry['ownership_matches_expected'] === false)),
                'domain_ownership_health_score' => $domainOwnershipHealthScore,
            ],
            'normalized_domain_metrics' => $normalizedDomains,
            'entries' => $entries,
        ];

        Log::info('authorization.policy_ownership.report.generated', $report['summary']);

        return $report;
    }

    private function extractMethodSource(ReflectionMethod $method): string
    {
        $fileName = $method->getFileName();

        if ($fileName === false) {
            return '';
        }

        $lines = file($fileName);

        if ($lines === false) {
            return '';
        }

        return implode('', array_slice(
            $lines,
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1,
        ));
    }

    /**
     * @return array<string, string>
     */
    private function buildImportMap(string $fileName): array
    {
        if ($fileName === '' || !is_file($fileName)) {
            return [];
        }

        $contents = file_get_contents($fileName);

        if ($contents === false) {
            return [];
        }

        $map = [];
        preg_match_all('/^use\s+([^;]+);$/m', $contents, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $fqcn = trim($match[1]);
            $alias = class_basename(str_replace('\\\\', '\\', $fqcn));
            $map[$alias] = str_replace('\\\\', '\\', $fqcn);
        }

        return $map;
    }

    /**
     * @param array<string, string> $importMap
     * @return list<array<string, mixed>>
     */
    private function parseAuthorizationCalls(ReflectionMethod $method, string $source, array $importMap): array
    {
        $calls = [];

        if ($source === '') {
            return $calls;
        }

        preg_match_all('/authorize\(\s*[\'\"]([^\'\"]+)[\'\"]\s*,\s*([^\)]*)\)/', $source, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $ability = $match[1];
            $subjectExpression = trim($match[2]);
            $policy = null;
            $subjectModel = null;
            $genericCurrentStore = str_contains($subjectExpression, 'app(\'currentStore\')') || str_contains($subjectExpression, 'app("currentStore")');

            if (preg_match('/\[\s*([A-Za-z0-9_\\\\]+)::class\s*,/', $subjectExpression, $policyMatch) === 1) {
                $subjectModel = $this->resolveImportedClass($policyMatch[1], $importMap, $method->getDeclaringClass()->getNamespaceName());
                $resolvedPolicy = $subjectModel ? $this->gate->getPolicyFor($subjectModel) : null;
                $policy = $resolvedPolicy ? $resolvedPolicy::class : null;

                if ($policy === null && is_string($subjectModel) && str_ends_with($subjectModel, 'Policy')) {
                    $policy = $subjectModel;
                }
            } elseif ($genericCurrentStore) {
                $subjectModel = 'App\\Models\\Store';
                $resolvedPolicy = $this->gate->getPolicyFor($subjectModel);
                $policy = $resolvedPolicy ? $resolvedPolicy::class : null;
            } elseif (preg_match('/\$([A-Za-z_][A-Za-z0-9_]*)/', $subjectExpression, $variableMatch) === 1) {
                [$subjectModel, $policy] = $this->resolveModelVariableAuthorization($method, $variableMatch[1]);
            } elseif (preg_match('/([A-Za-z0-9_\\\\]+)::class/', $subjectExpression, $classMatch) === 1) {
                $subjectModel = $this->resolveImportedClass($classMatch[1], $importMap, $method->getDeclaringClass()->getNamespaceName());
                $resolvedPolicy = $subjectModel ? $this->gate->getPolicyFor($subjectModel) : null;
                $policy = $resolvedPolicy ? $resolvedPolicy::class : null;
            }

            $calls[] = [
                'ability' => $ability,
                'subject_expression' => $subjectExpression,
                'policy' => $policy,
                'subject_model' => $subjectModel,
                'generic_current_store' => $genericCurrentStore,
            ];
        }

        return $calls;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveModelVariableAuthorization(ReflectionMethod $method, string $variableName): array
    {
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getName() !== $variableName) {
                continue;
            }

            $type = $parameter->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                return [null, null];
            }

            $modelClass = $type->getName();
            $resolvedPolicy = $this->gate->getPolicyFor($modelClass);

            return [$modelClass, $resolvedPolicy ? $resolvedPolicy::class : null];
        }

        return [null, null];
    }

    private function resolveImportedClass(string $classReference, array $importMap, string $namespace): ?string
    {
        if (str_contains($classReference, '\\')) {
            return ltrim($classReference, '\\');
        }

        if (isset($importMap[$classReference])) {
            return $importMap[$classReference];
        }

        return $namespace !== '' ? $namespace . '\\' . $classReference : $classReference;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function requestAuthorizeStrategies(ReflectionMethod $method): array
    {
        $strategies = [];

        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();
            if (!is_subclass_of($typeName, FormRequest::class) || !method_exists($typeName, 'authorize')) {
                continue;
            }

            $authorizeMethod = new ReflectionMethod($typeName, 'authorize');
            $source = $this->extractMethodSource($authorizeMethod);
            $strategy = 'custom';

            if (str_contains($source, 'hasPermissionTo(')) {
                $strategy = 'hasPermissionTo';
            } elseif (str_contains($source, '->can(') || str_contains($source, '::authorize(')) {
                $strategy = 'policy_can';
            } elseif (str_contains($source, 'hasRole(')) {
                $strategy = 'hasRole';
            } elseif (preg_match('/return\s+true\s*;/', $source) === 1) {
                $strategy = 'returns_true';
            } elseif (preg_match('/return\s*\(bool\)\s*\$this->user\(\)/', $source) === 1) {
                $strategy = 'user_presence';
            }

            $strategies[] = [
                'request' => $typeName,
                'strategy' => $strategy,
            ];
        }

        return $strategies;
    }

    /**
     * @param list<array<string, mixed>> $strategies
     */
    private function hasAuthBearingRequestStrategy(array $strategies): bool
    {
        foreach ($strategies as $strategy) {
            if (($strategy['strategy'] ?? null) !== 'returns_true') {
                return true;
            }
        }

        return false;
    }

    private function resolveExpectedPolicyOwner(string $controllerClass): ?string
    {
        return match (true) {
            str_contains($controllerClass, '\\Admin\\Brand\\') => BrandPolicy::class,
            str_contains($controllerClass, '\\Admin\\Category\\') => CategoryPolicy::class,
            str_contains($controllerClass, '\\Admin\\Dashboard\\') => DashboardPolicy::class,
            str_contains($controllerClass, '\\Admin\\Order\\') => OrderPolicy::class,
            str_contains($controllerClass, '\\Admin\\Product\\') => ProductPolicy::class,
            str_contains($controllerClass, '\\Admin\\Tag\\') => TagPolicy::class,
            str_contains($controllerClass, '\\Admin\\User\\') => MembershipPolicy::class,
            str_contains($controllerClass, '\\Api\\Address\\') => AddressPolicy::class,
            str_contains($controllerClass, '\\Cms\\Blog\\AdminBlogController') => BlogPostPolicy::class,
            str_contains($controllerClass, '\\PaymentMethod\\') => PaymentMethodPolicy::class,
            str_contains($controllerClass, '\\Store\\StoreController') => StorePolicy::class,
            default => null,
        };
    }

    private function resolveDomain(string $controllerClass): ?string
    {
        return match (true) {
            str_contains($controllerClass, '\\Admin\\Brand\\') => 'brand',
            str_contains($controllerClass, '\\Admin\\Category\\') => 'category',
            str_contains($controllerClass, '\\Admin\\Dashboard\\') => 'dashboard',
            str_contains($controllerClass, '\\Admin\\Order\\') => 'order',
            str_contains($controllerClass, '\\Admin\\Product\\') => 'product',
            str_contains($controllerClass, '\\Admin\\Tag\\') => 'tag',
            str_contains($controllerClass, '\\Admin\\User\\') => 'membership_admin',
            str_contains($controllerClass, '\\Cms\\Blog\\AdminBlogController') => 'cms_blog',
            str_contains($controllerClass, '\\Store\\StoreController') => 'store',
            default => null,
        };
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @return array<string, array<string, int|float|string>>
     */
    private function buildNormalizedDomainMetrics(array $entries): array
    {
        $domains = ['brand', 'tag', 'category', 'cms_blog', 'dashboard'];
        $metrics = [];

        foreach ($domains as $domain) {
            $domainEntries = array_values(array_filter($entries, fn (array $entry): bool => ($entry['domain'] ?? null) === $domain));

            if ($domainEntries === []) {
                $metrics[$domain] = [
                    'total_routes' => 0,
                    'explicit_policy_routes' => 0,
                    'generic_current_store_routes' => 0,
                    'hidden_fallback_routes' => 0,
                    'middleware_only_routes' => 0,
                    'dual_authorization_routes' => 0,
                    'ownership_mismatches' => 0,
                    'health_score' => 0,
                    'status' => 'not_mapped',
                ];

                continue;
            }

            $healthyRoutes = count(array_filter($domainEntries, fn (array $entry): bool => ($entry['policy_invoked'] ?? false) === true
                && ($entry['generic_currentStore'] ?? false) === false
                && ($entry['fallback_path_used'] ?? false) === false
                && ($entry['ownership_matches_expected'] ?? true) !== false));

            $healthScore = (int) round(($healthyRoutes / count($domainEntries)) * 100);

            $metrics[$domain] = [
                'total_routes' => count($domainEntries),
                'explicit_policy_routes' => count(array_filter($domainEntries, fn (array $entry): bool => ($entry['policy_invoked'] ?? false) === true)),
                'generic_current_store_routes' => count(array_filter($domainEntries, fn (array $entry): bool => ($entry['generic_currentStore'] ?? false) === true)),
                'hidden_fallback_routes' => count(array_filter($domainEntries, fn (array $entry): bool => ($entry['fallback_path_used'] ?? false) === true)),
                'middleware_only_routes' => count(array_filter($domainEntries, fn (array $entry): bool => ($entry['middleware_only_authorization'] ?? false) === true)),
                'dual_authorization_routes' => count(array_filter($domainEntries, fn (array $entry): bool => ($entry['dual_authorization_path'] ?? false) === true)),
                'ownership_mismatches' => count(array_filter($domainEntries, fn (array $entry): bool => ($entry['ownership_matches_expected'] ?? null) === false)),
                'health_score' => $healthScore,
                'status' => $healthScore === 100 ? 'normalized' : 'needs_attention',
            ];
        }

        return $metrics;
    }
}
