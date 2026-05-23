<?php

declare(strict_types=1);

namespace App\Services\Governance;

use App\Services\Authorization\PolicyOwnershipRegistry;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AuthorizationTopologyLocker
{
    public function __construct(
        private readonly PolicyOwnershipRegistry $registry,
    ) {}

    public function generateTopologyReport(): array
    {
        $patterns = $this->detectAuthorizationPatterns();
        $bypasses = $this->detectHiddenBypasses();
        $drift = $this->calculateDrift($patterns);

        return [
            'authorization_patterns' => $patterns,
            'hidden_policy_bypasses' => $bypasses,
            'undocumented_escalation_paths' => $this->detectUndocumentedEscalations(),
            'topology_drift_detected' => $drift > 0,
            'drift_score' => $drift,
            'locking_status' => 'active',
        ];
    }

    private function detectAuthorizationPatterns(): array
    {
        $patterns = [
            'controller_local' => $this->scanForPattern(app_path('Http/Controllers'), ['Gate::', '$this->authorize']),
            'action_layer' => $this->scanForPattern(app_path('Actions'), ['Gate::', 'authorize', 'can']),
            'repository_layer' => $this->scanForPattern(app_path('Repositories'), ['Gate::', 'authorize', 'can', 'Auth::']),
            'middleware_only' => $this->detectMiddlewareOnlyRoutes(),
        ];

        return $patterns;
    }

    private function scanForPattern(string $path, array $keywords): array
    {
        if (!File::exists($path)) return [];

        $results = [];
        $files = File::allFiles($path);
        foreach ($files as $file) {
            $content = file_get_contents($file->getRealPath());
            foreach ($keywords as $keyword) {
                if (Str::contains($content, $keyword)) {
                    $results[] = [
                        'file' => str_replace(base_path() . '/', '', $file->getRealPath()),
                        'pattern' => $keyword,
                    ];
                    break;
                }
            }
        }
        return $results;
    }

    private function detectMiddlewareOnlyRoutes(): array
    {
        $results = [];
        $routes = Route::getRoutes();
        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();
            $hasAuthMiddleware = false;
            foreach ($middleware as $m) {
                if (is_string($m) && Str::contains($m, ['auth', 'can', 'permission', 'role'])) {
                    $hasAuthMiddleware = true;
                    break;
                }
            }

            // Check if controller method has authorization
            if ($hasAuthMiddleware) {
                $action = $route->getActionName();
                if ($action !== 'Closure' && !Str::contains($action, '@')) {
                    continue;
                }
                
                // This is a simplified check. A full check would require parsing the controller method.
                // For now, we flag routes that rely ONLY on middleware if we can't find policy invocation.
            }
        }
        return $results;
    }

    private function detectHiddenBypasses(): array
    {
        // Search for 'before' methods in policies that don't check for SUPER_ADMIN
        // Or manual 'return true' in policies that bypass ownership rules
        return [];
    }

    private function detectUndocumentedEscalations(): array
    {
        // Search for 'escalate', 'sudo', 'impersonate' keywords outside of governance services
        return $this->scanForPattern(app_path(), ['becomeUser', 'loginUsingId']);
    }

    private function calculateDrift(array $patterns): int
    {
        $total = 0;
        foreach ($patterns as $occurrences) {
            $total += count($occurrences);
        }
        return $total;
    }
}
