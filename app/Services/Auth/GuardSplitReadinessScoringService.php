<?php

declare(strict_types=1);

namespace App\Services\Auth;

class GuardSplitReadinessScoringService
{
    /**
     * @param array<int, array{scenario: array<string, mixed>, ownership: array<string, mixed>, collision: array<string, mixed>}> $simulations
     * @return array<string, mixed>
     */
    public function score(array $simulations, array $frontendAnalysis): array
    {
        $contaminationScores = array_map(
            fn (array $entry): int => (int) ($entry['collision']['contamination_severity_score'] ?? 0),
            $simulations,
        );
        $csrfScores = array_map(
            fn (array $entry): int => (int) ($entry['collision']['csrf_refresh_risk'] ?? 0),
            $simulations,
        );
        $logoutScores = array_map(
            fn (array $entry): int => (int) ($entry['collision']['logout_propagation_risk'] ?? 0),
            $simulations,
        );
        $browserScores = array_map(
            fn (array $entry): int => (int) ($entry['collision']['browser_multi_tab_risk'] ?? 0),
            $simulations,
        );

        $sessionContaminationScore = $this->inverseAverage($contaminationScores);
        $csrfIsolationReadiness = $this->inverseAverage($csrfScores);
        $logoutIsolationReadiness = $this->inverseAverage($logoutScores);
        $frontendReadiness = max(0, 100 - (int) ($frontendAnalysis['migration_risk_score'] ?? 100));
        $authDomainStabilityScore = $this->inverseAverage($browserScores);
        $guardSplitReadinessScore = (int) round(array_sum([
            $sessionContaminationScore,
            $csrfIsolationReadiness,
            $logoutIsolationReadiness,
            $frontendReadiness,
            $authDomainStabilityScore,
        ]) / 5);

        $status = match (true) {
            $guardSplitReadinessScore >= 80 => 'READY',
            $guardSplitReadinessScore >= 50 => 'PARTIALLY_READY',
            default => 'BLOCKED',
        };

        $blockers = array_values(array_filter([
            $sessionContaminationScore < 60 ? 'session contamination remains too high under simulated split scenarios' : null,
            $csrfIsolationReadiness < 60 ? 'csrf ownership remains too coupled to shared lifecycle assumptions' : null,
            $logoutIsolationReadiness < 60 ? 'logout invalidation scope remains globally shared' : null,
            $frontendReadiness < 60 ? 'frontend-observable assumptions still depend on shared auth lifecycle' : null,
            'shared users table remains authoritative',
            'shared sanctum session remains authoritative',
            'shared session cookie remains authoritative',
        ]));

        return [
            'guard_split_readiness_score' => $guardSplitReadinessScore,
            'csrf_isolation_readiness' => $csrfIsolationReadiness,
            'logout_isolation_readiness' => $logoutIsolationReadiness,
            'frontend_readiness' => $frontendReadiness,
            'session_contamination_score' => $sessionContaminationScore,
            'auth_domain_stability_score' => $authDomainStabilityScore,
            'status' => $status,
            'blockers' => $blockers,
        ];
    }

    /**
     * @param int[] $scores
     */
    private function inverseAverage(array $scores): int
    {
        if ($scores === []) {
            return 100;
        }

        return max(0, 100 - (int) round(array_sum($scores) / count($scores)));
    }
}
