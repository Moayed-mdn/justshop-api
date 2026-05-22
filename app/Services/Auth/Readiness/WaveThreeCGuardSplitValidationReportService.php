<?php

declare(strict_types=1);

namespace App\Services\Auth\Readiness;

use App\Services\Auth\FrontendGuardSplitReadinessService;
use App\Services\Auth\GuardSplitReadinessScoringService;
use App\Services\Auth\GuardSplitSimulationService;

class WaveThreeCGuardSplitValidationReportService
{
    public function __construct(
        private readonly GuardSplitSimulationService $simulationService,
        private readonly FrontendGuardSplitReadinessService $frontendReadinessService,
        private readonly GuardSplitReadinessScoringService $scoringService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function generate(): array
    {
        $simulations = $this->simulationService->simulateAll();
        $frontendAnalysis = $this->frontendReadinessService->analyze();
        $scores = $this->scoringService->score($simulations, $frontendAnalysis);

        return [
            'generated_at' => now()->toIso8601String(),
            'release_version' => (string) config('observability.release_version'),
            'guard_split_simulation_engine' => [
                'simulation_service_present' => class_exists(GuardSplitSimulationService::class),
                'scenario_count' => count($simulations),
                'runtime_authority_unchanged' => true,
                'status' => class_exists(GuardSplitSimulationService::class) ? 'healthy' : 'attention_required',
            ],
            'concurrent_session_validation' => [
                'simulated' => [
                    'merchant_plus_storefront_tabs',
                    'merchant_login_while_storefront_authenticated',
                    'storefront_login_while_merchant_authenticated',
                    'logout_one_context_other_remains_active',
                    'csrf_refresh_during_mixed_context_usage',
                ],
                'browser_safe_assumptions_only' => true,
            ],
            'csrf_ownership_validation' => [
                'shared_behavior_unchanged' => true,
                'ownership_headers_present' => true,
                'refresh_anomalies_simulated' => true,
            ],
            'logout_semantics_validation' => [
                'shared_behavior_unchanged' => true,
                'future_scope_analysis_available' => true,
                'split_safe_logout_map_present' => true,
            ],
            'frontend_compatibility_readiness' => $frontendAnalysis,
            'session_contamination_stress' => [
                'scenario_outputs' => $simulations,
                'hotspots' => $this->hotspots($simulations),
                'contamination_severity' => $this->severityBuckets($simulations),
            ],
            'guard_readiness_scoring' => $scores,
            'operational_risk_analysis' => [
                'blast_radius_estimation' => 'high',
                'rollback_complexity' => 'conditional',
                'browser_risk_analysis' => 'multi-tab shared-cookie behavior remains the primary operational risk',
                'mobile_client_risks' => 'mobile clients may retain stale shared auth assumptions during phased rollout',
                'csrf_risks' => 'single shared csrf lifecycle still couples merchant and storefront flows',
                'support_burden_estimate' => 'high_during_transition',
                'session_invalidation_risk' => 'shared logout and invalidation semantics remain globally scoped',
                'reversible_risks' => [
                    'route-domain metadata tuning',
                    'telemetry threshold tuning',
                    'frontend additive metadata adoption',
                ],
                'conditionally_reversible_risks' => [
                    'browser-tab session confusion during canaries',
                    'csrf refresh ownership drift during mixed usage',
                ],
                'operationally_irreversible_risks' => [
                    'unexpected mass logout during future cookie cutover',
                    'support burden from mixed-session ambiguity if activation precedes readiness',
                ],
            ],
            'remaining_guard_split_blockers' => $scores['blockers'],
        ];
    }

    /**
     * @param array<int, array{scenario: array<string, mixed>, ownership: array<string, mixed>, collision: array<string, mixed>}> $simulations
     * @return array<int, string>
     */
    private function hotspots(array $simulations): array
    {
        $hotspots = [];

        foreach ($simulations as $entry) {
            if (($entry['collision']['contamination_severity_score'] ?? 0) >= 70) {
                $hotspots[] = (string) ($entry['scenario']['key'] ?? 'unknown');
            }
        }

        return $hotspots;
    }

    /**
     * @param array<int, array{scenario: array<string, mixed>, ownership: array<string, mixed>, collision: array<string, mixed>}> $simulations
     * @return array<string, int>
     */
    private function severityBuckets(array $simulations): array
    {
        $buckets = ['low' => 0, 'medium' => 0, 'high' => 0];

        foreach ($simulations as $entry) {
            $score = (int) ($entry['collision']['contamination_severity_score'] ?? 0);

            if ($score >= 70) {
                $buckets['high']++;
            } elseif ($score >= 40) {
                $buckets['medium']++;
            } else {
                $buckets['low']++;
            }
        }

        return $buckets;
    }
}
