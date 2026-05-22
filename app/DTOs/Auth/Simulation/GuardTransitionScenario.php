<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Simulation;

final readonly class GuardTransitionScenario
{
    public function __construct(
        public string $key,
        public string $description,
        public string $primaryAuthDomain,
        public string $primaryRouteDomain,
        public ?string $secondaryAuthDomain = null,
        public ?string $secondaryRouteDomain = null,
        public bool $primaryOnboardingApplicable = false,
        public bool $secondaryOnboardingApplicable = false,
        public bool $crossDomainNavigation = false,
        public bool $multiTab = false,
        public bool $includesBootstrap = false,
        public bool $includesLogout = false,
        public bool $includesCsrfRefresh = false,
        public bool $includesSessionMigration = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'description' => $this->description,
            'primary_auth_domain' => $this->primaryAuthDomain,
            'primary_route_domain' => $this->primaryRouteDomain,
            'secondary_auth_domain' => $this->secondaryAuthDomain,
            'secondary_route_domain' => $this->secondaryRouteDomain,
            'primary_onboarding_applicable' => $this->primaryOnboardingApplicable,
            'secondary_onboarding_applicable' => $this->secondaryOnboardingApplicable,
            'cross_domain_navigation' => $this->crossDomainNavigation,
            'multi_tab' => $this->multiTab,
            'includes_bootstrap' => $this->includesBootstrap,
            'includes_logout' => $this->includesLogout,
            'includes_csrf_refresh' => $this->includesCsrfRefresh,
            'includes_session_migration' => $this->includesSessionMigration,
        ];
    }
}
