<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Illuminate\Support\Facades\Route;

class TransitionalDebtMeasurer
{
    /**
     * @return array<string, mixed>
     */
    public function measure(): array
    {
        $totalRoutes = 0;
        $transitionalRoutes = 0;
        $unclassifiedRoutes = 0;

        foreach (Route::getRoutes() as $route) {
            $middleware = $route->gatherMiddleware();
            $hasIdentityMetadata = collect($middleware)->contains(
                fn ($m) => str_starts_with((string) $m, 'identity.route:')
            );

            if (!$hasIdentityMetadata) {
                $unclassifiedRoutes++;
                continue;
            }

            $isTransitional = collect($middleware)->contains(
                fn ($m) => str_contains((string) $m, 'shared_transitional')
            );

            if ($isTransitional) {
                $transitionalRoutes++;
            }

            $totalRoutes++;
        }

        return [
            'total_classified_routes' => $totalRoutes,
            'transitional_routes_count' => $transitionalRoutes,
            'unclassified_routes_count' => $unclassifiedRoutes,
            'transitional_ratio' => $totalRoutes > 0 ? round($transitionalRoutes / $totalRoutes, 4) : 0,
            'debt_severity' => $this->calculateSeverity($transitionalRoutes, $unclassifiedRoutes),
        ];
    }

    private function calculateSeverity(int $transitional, int $unclassified): string
    {
        if ($unclassified > 0) return 'CRITICAL';
        if ($transitional > 10) return 'HIGH';
        if ($transitional > 5) return 'MEDIUM';
        return 'LOW';
    }
}
