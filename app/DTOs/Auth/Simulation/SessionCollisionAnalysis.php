<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Simulation;

final readonly class SessionCollisionAnalysis
{
    /**
     * @param string[] $collisionVectors
     * @param array<string, string> $splitSafeLogoutMap
     */
    public function __construct(
        public string $scenarioKey,
        public bool $collisionDetected,
        public int $contaminationSeverityScore,
        public int $browserMultiTabRisk,
        public int $mobileClientRisk,
        public int $logoutPropagationRisk,
        public int $csrfRefreshRisk,
        public array $collisionVectors,
        public array $splitSafeLogoutMap,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'scenario_key' => $this->scenarioKey,
            'collision_detected' => $this->collisionDetected,
            'contamination_severity_score' => $this->contaminationSeverityScore,
            'browser_multi_tab_risk' => $this->browserMultiTabRisk,
            'mobile_client_risk' => $this->mobileClientRisk,
            'logout_propagation_risk' => $this->logoutPropagationRisk,
            'csrf_refresh_risk' => $this->csrfRefreshRisk,
            'collision_vectors' => $this->collisionVectors,
            'split_safe_logout_map' => $this->splitSafeLogoutMap,
        ];
    }
}
