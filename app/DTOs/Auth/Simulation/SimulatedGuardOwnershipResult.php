<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Simulation;

final readonly class SimulatedGuardOwnershipResult
{
    /**
     * @param string[] $conflicts
     * @param string[] $notes
     */
    public function __construct(
        public string $scenarioKey,
        public string $primaryFutureGuard,
        public ?string $secondaryFutureGuard,
        public bool $ambiguousOwnership,
        public int $contaminationRisk,
        public bool $logoutConflict,
        public bool $csrfConflict,
        public bool $bootstrapOwnershipConflict,
        public bool $crossDomainNavigationRisk,
        public array $conflicts,
        public array $notes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'scenario_key' => $this->scenarioKey,
            'primary_future_guard' => $this->primaryFutureGuard,
            'secondary_future_guard' => $this->secondaryFutureGuard,
            'ambiguous_ownership' => $this->ambiguousOwnership,
            'contamination_risk' => $this->contaminationRisk,
            'logout_conflict' => $this->logoutConflict,
            'csrf_conflict' => $this->csrfConflict,
            'bootstrap_ownership_conflict' => $this->bootstrapOwnershipConflict,
            'cross_domain_navigation_risk' => $this->crossDomainNavigationRisk,
            'conflicts' => $this->conflicts,
            'notes' => $this->notes,
        ];
    }
}
