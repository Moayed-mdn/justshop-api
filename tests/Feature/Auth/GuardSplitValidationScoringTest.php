<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Services\Auth\FrontendGuardSplitReadinessService;
use App\Services\Auth\GuardSplitReadinessScoringService;
use App\Services\Auth\GuardSplitSimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardSplitValidationScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_split_readiness_scoring_classifies_current_state_as_blocked_or_partially_ready_with_explicit_blockers(): void
    {
        $simulations = app(GuardSplitSimulationService::class)->simulateAll();
        $frontend = app(FrontendGuardSplitReadinessService::class)->analyze();
        $scores = app(GuardSplitReadinessScoringService::class)->score($simulations, $frontend);

        $this->assertContains($scores['status'], ['BLOCKED', 'PARTIALLY_READY']);
        $this->assertArrayHasKey('guard_split_readiness_score', $scores);
        $this->assertArrayHasKey('csrf_isolation_readiness', $scores);
        $this->assertArrayHasKey('logout_isolation_readiness', $scores);
        $this->assertContains('shared session cookie remains authoritative', $scores['blockers']);
    }

    public function test_frontend_readiness_analysis_lists_unsupported_split_assumptions(): void
    {
        $analysis = app(FrontendGuardSplitReadinessService::class)->analyze();

        $this->assertSame('attention_required', $analysis['status']);
        $this->assertContains('shared_session_cookie_still_authoritative', $analysis['unsupported_assumptions']);
        $this->assertContains('single_csrf_cookie_endpoint_still_shared', $analysis['unsupported_assumptions']);
        $this->assertTrue($analysis['bootstrap_dependencies']['merchant_bootstrap_contract_preserved']);
    }
}
