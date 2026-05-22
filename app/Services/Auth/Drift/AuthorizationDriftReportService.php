<?php

declare(strict_types=1);

namespace App\Services\Auth\Drift;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AuthorizationDriftReportService
{
    /**
     * @return array<string, mixed>
     */
    public function generate(?string $allowlistPath = null, ?string $baselinePath = null): array
    {
        $findings = [
            ...$this->detectAuthCallsInActions(),
            ...$this->detectHasPermissionToOutsideAuthorizedBoundaries(),
            ...$this->detectStorePolicyMisuse(),
            ...$this->detectRoutePermissionMiddlewareDrift(),
            ...$this->detectBootstrapCoupling(),
            ...$this->detectRequestSessionCoupling(),
            ...$this->detectRepositoryLeakage(),
        ];

        usort($findings, fn (array $left, array $right): int => [$left['category'], $left['file'], $left['line']] <=> [$right['category'], $right['file'], $right['line']]);

        $allowlist = $this->loadAllowlist($allowlistPath ?? (string) config('migration.drift_detection.allowlist_path'));

        foreach ($findings as &$finding) {
            $finding['allowlisted'] = $this->isAllowlisted($finding, $allowlist);
        }
        unset($finding);

        $activeFindings = array_values(array_filter($findings, fn (array $finding): bool => !$finding['allowlisted']));

        $baseline = $this->loadBaseline($baselinePath ?? (string) config('migration.drift_detection.baseline_path'));
        $trend = $this->buildTrend($activeFindings, $baseline['active_findings'] ?? []);

        foreach ($activeFindings as &$finding) {
            $finding['regression'] = in_array($finding['fingerprint'], $trend['new_fingerprints'], true);
        }
        unset($finding);

        return [
            'generated_at' => now()->toIso8601String(),
            'mode' => 'warning',
            'allowlist_path' => $allowlistPath ?? (string) config('migration.drift_detection.allowlist_path'),
            'baseline_path' => $baselinePath ?? (string) config('migration.drift_detection.baseline_path'),
            'summary' => [
                'total_findings' => count($findings),
                'active_findings' => count($activeFindings),
                'allowlisted_findings' => count($findings) - count($activeFindings),
                'by_category' => $this->countBy($activeFindings, 'category'),
                'by_severity' => $this->countBy($activeFindings, 'severity'),
                'by_type' => $this->countBy($activeFindings, 'type'),
            ],
            'trend' => $trend,
            'findings' => $findings,
            'active_findings' => $activeFindings,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function detectAuthCallsInActions(): array
    {
        return $this->scanFiles(
            basePath: app_path('Actions'),
            matcher: '/\bauth\s*\(|\bAuth::user\s*\(/',
            category: 'hidden_authorization',
            type: 'auth_in_actions',
            severity: 'high',
            message: 'auth() or Auth::user() found inside Action boundary',
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function detectHasPermissionToOutsideAuthorizedBoundaries(): array
    {
        $findings = [];

        foreach ($this->phpFiles(app_path()) as $file) {
            if (str_contains($file, DIRECTORY_SEPARATOR . 'Policies' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            if (str_contains($file, DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            if (str_contains($file, DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $content = File::get($file);
            $matches = [];
            preg_match_all('/hasPermissionTo\s*\(/', $content, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as [$match, $offset]) {
                $findings[] = $this->finding(
                    file: $file,
                    line: $this->lineForOffset($content, $offset),
                    category: 'hidden_authorization',
                    type: 'permission_check_outside_boundary',
                    severity: 'high',
                    message: 'hasPermissionTo() found outside policies/resolvers',
                );
            }
        }

        return $findings;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function detectStorePolicyMisuse(): array
    {
        return $this->scanFiles(
            basePath: app_path('Http/Controllers'),
            matcher: '/authorize\s*\([^\)]*app\(\s*[\'\"]currentStore[\'\"]\s*\)/',
            category: 'policy_ownership_drift',
            type: 'generic_current_store_authorize',
            severity: 'medium',
            message: 'controller authorize() uses generic currentStore path',
            exclude: fn (string $file): bool => str_ends_with($file, 'StoreController.php'),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function detectRoutePermissionMiddlewareDrift(): array
    {
        $findings = [];
        $adminRoutePath = base_path('routes/api/v1/admin');

        foreach ($this->phpFiles($adminRoutePath) as $file) {
            $lines = preg_split('/\R/', File::get($file)) ?: [];
            $statement = '';
            $statementStartLine = 1;

            foreach ($lines as $index => $line) {
                $trimmed = trim($line);

                if ($statement === '' && preg_match('/^Route::(get|post|put|patch|delete|apiResource)\b/', $trimmed) !== 1) {
                    continue;
                }

                if ($statement === '') {
                    $statementStartLine = $index + 1;
                }

                $statement .= ' ' . $trimmed;

                if (!str_contains($trimmed, ';')) {
                    continue;
                }

                $routeStatement = trim($statement);
                $statement = '';

                if (str_contains($routeStatement, 'permission:')) {
                    continue;
                }

                $findings[] = $this->finding(
                    file: $file,
                    line: $statementStartLine,
                    category: 'permission_middleware_drift',
                    type: 'admin_route_missing_permission_middleware',
                    severity: 'high',
                    message: 'admin route is missing explicit permission middleware',
                );
            }
        }

        return $findings;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function detectBootstrapCoupling(): array
    {
        $findings = [];
        $allowedPaths = [
            'app/Actions/Auth/',
            'app/DTOs/Auth/Bootstrap/',
            'app/Http/Controllers/Api/Auth/AuthController.php',
            'app/Http/Resources/Auth/BootstrapResource.php',
            'app/Services/Auth/Bootstrap/',
            'app/Services/Auth/Drift/',
            'app/Services/Auth/Readiness/',
            'tests/Feature/Auth/',
        ];

        foreach ($this->phpFiles(app_path()) as $file) {
            $relativePath = $this->relativePath($file);

            if ($this->startsWithAny($relativePath, $allowedPaths)) {
                continue;
            }

            $content = File::get($file);
            $matches = [];
            preg_match_all('/Bootstrap(Resource|PayloadSerializer|ShadowParityService|Telemetry|CompatibilityAdapter|StoreResolver|PermissionResolver|OnboardingResolver|ConfigResolver)|GetBootstrapResponseDTO/', $content, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as [$match, $offset]) {
                $findings[] = $this->finding(
                    file: $file,
                    line: $this->lineForOffset($content, $offset),
                    category: 'bootstrap_coupling',
                    type: 'bootstrap_boundary_reference_outside_auth',
                    severity: 'medium',
                    message: 'bootstrap internal boundary referenced outside bootstrap/auth surface',
                );
            }
        }

        return $findings;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function detectRequestSessionCoupling(): array
    {
        return [
            ...$this->scanFiles(
                basePath: app_path('Actions'),
                matcher: '/\brequest\s*\(|->session\s*\(|\bsession\s*\(/',
                category: 'request_session_coupling',
                type: 'request_or_session_usage_in_actions',
                severity: 'medium',
                message: 'request/session coupling found inside Action boundary',
            ),
            ...$this->scanFiles(
                basePath: app_path('Services'),
                matcher: '/\brequest\s*\(|->session\s*\(|\bsession\s*\(/',
                category: 'request_session_coupling',
                type: 'request_or_session_usage_in_services',
                severity: 'medium',
                message: 'request/session coupling found inside Service boundary',
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function detectRepositoryLeakage(): array
    {
        $findings = [];
        $models = array_map(fn ($path): string => pathinfo($path, PATHINFO_FILENAME), $this->phpFiles(app_path('Models')));

        if ($models === []) {
            return $findings;
        }

        $modelPattern = implode('|', array_map(fn (string $model): string => preg_quote($model, '/'), $models));
        $matcher = '/\b(' . $modelPattern . ')::(query|where|find|findOrFail|create|first|firstOrCreate|get|updateOrCreate)\s*\(/';

        $targets = [app_path('Http/Controllers'), app_path('Services')];

        foreach ($targets as $basePath) {
            foreach ($this->phpFiles($basePath) as $file) {
                if (str_contains($file, DIRECTORY_SEPARATOR . 'Repositories' . DIRECTORY_SEPARATOR)) {
                    continue;
                }

                $content = File::get($file);
                $matches = [];
                preg_match_all($matcher, $content, $matches, PREG_OFFSET_CAPTURE);

                foreach ($matches[0] as [$match, $offset]) {
                    $findings[] = $this->finding(
                        file: $file,
                        line: $this->lineForOffset($content, $offset),
                        category: 'repository_leakage',
                        type: 'direct_model_query_outside_repository',
                        severity: 'medium',
                        message: 'direct model query found outside repository boundary',
                    );
                }
            }
        }

        return $findings;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function scanFiles(
        string $basePath,
        string $matcher,
        string $category,
        string $type,
        string $severity,
        string $message,
        ?callable $exclude = null,
    ): array {
        $findings = [];

        foreach ($this->phpFiles($basePath) as $file) {
            if ($exclude !== null && $exclude($file) === true) {
                continue;
            }

            $content = File::get($file);
            $matches = [];
            preg_match_all($matcher, $content, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as [$match, $offset]) {
                $findings[] = $this->finding(
                    file: $file,
                    line: $this->lineForOffset($content, $offset),
                    category: $category,
                    type: $type,
                    severity: $severity,
                    message: $message,
                );
            }
        }

        return $findings;
    }

    /**
     * @return array<string, mixed>
     */
    private function finding(
        string $file,
        int $line,
        string $category,
        string $type,
        string $severity,
        string $message,
    ): array {
        $relativePath = $this->relativePath($file);
        $fingerprint = sha1($category . '|' . $type . '|' . $relativePath . '|' . $line . '|' . $message);

        return [
            'fingerprint' => $fingerprint,
            'category' => $category,
            'type' => $type,
            'severity' => $severity,
            'file' => $relativePath,
            'line' => $line,
            'message' => $message,
            'allowlisted' => false,
            'regression' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadAllowlist(string $path): array
    {
        if ($path === '' || !File::exists($path)) {
            return [
                'fingerprints' => [],
                'paths' => [],
                'categories' => [],
            ];
        }

        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : [
            'fingerprints' => [],
            'paths' => [],
            'categories' => [],
        ];
    }

    /**
     * @param array<string, mixed> $finding
     * @param array<string, mixed> $allowlist
     */
    private function isAllowlisted(array $finding, array $allowlist): bool
    {
        $fingerprints = $allowlist['fingerprints'] ?? [];
        $paths = $allowlist['paths'] ?? [];
        $categories = $allowlist['categories'] ?? [];

        if (in_array($finding['fingerprint'], $fingerprints, true)) {
            return true;
        }

        if (in_array($finding['category'], $categories, true)) {
            return true;
        }

        foreach ($paths as $path) {
            if (str_starts_with($finding['file'], (string) $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadBaseline(string $path): array
    {
        if ($path === '' || !File::exists($path)) {
            return [
                'active_findings' => [],
            ];
        }

        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : [
            'active_findings' => [],
        ];
    }

    /**
     * @param list<array<string, mixed>> $currentFindings
     * @param list<array<string, mixed>> $baselineFindings
     * @return array<string, mixed>
     */
    private function buildTrend(array $currentFindings, array $baselineFindings): array
    {
        $currentFingerprints = array_values(array_map(fn (array $finding): string => $finding['fingerprint'], $currentFindings));
        $baselineFingerprints = array_values(array_map(fn (array $finding): string => $finding['fingerprint'], $baselineFindings));

        $newFingerprints = array_values(array_diff($currentFingerprints, $baselineFingerprints));
        $resolvedFingerprints = array_values(array_diff($baselineFingerprints, $currentFingerprints));

        return [
            'baseline_available' => $baselineFindings !== [],
            'new_since_baseline' => count($newFingerprints),
            'resolved_since_baseline' => count($resolvedFingerprints),
            'unchanged_since_baseline' => count($currentFingerprints) - count($newFingerprints),
            'new_fingerprints' => $newFingerprints,
            'resolved_fingerprints' => $resolvedFingerprints,
        ];
    }

    /**
     * @param list<array<string, mixed>> $findings
     * @return array<string, int>
     */
    private function countBy(array $findings, string $key): array
    {
        $counts = [];

        foreach ($findings as $finding) {
            $value = (string) ($finding[$key] ?? 'unknown');
            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        ksort($counts);

        return $counts;
    }

    /**
     * @return string[]
     */
    private function phpFiles(string $basePath): array
    {
        if (!File::exists($basePath)) {
            return [];
        }

        $files = [];

        foreach (File::allFiles($basePath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    private function lineForOffset(string $content, int $offset): int
    {
        return substr_count(substr($content, 0, $offset), "\n") + 1;
    }

    private function relativePath(string $absolutePath): string
    {
        return str_replace(base_path() . DIRECTORY_SEPARATOR, '', $absolutePath);
    }

    /**
     * @param string[] $prefixes
     */
    private function startsWithAny(string $value, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
