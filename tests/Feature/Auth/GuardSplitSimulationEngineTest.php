<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Services\Auth\GuardSplitSimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardSplitSimulationEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_dual_session_coexistence_simulation_detects_ambiguity_and_collision_risk(): void
    {
        $service = app(GuardSplitSimulationService::class);
        $scenario = collect($service->scenarios())->firstWhere('key', 'dual_session_coexistence');

        $ownership = $service->simulateScenario($scenario);
        $collision = $service->analyzeCollision($scenario);

        $this->assertSame('merchant_guard', $ownership->primaryFutureGuard);
        $this->assertSame('customer_guard', $ownership->secondaryFutureGuard);
        $this->assertTrue($ownership->ambiguousOwnership);
        $this->assertTrue($ownership->logoutConflict);
        $this->assertTrue($ownership->csrfConflict);
        $this->assertTrue($collision->collisionDetected);
        $this->assertGreaterThanOrEqual(70, $collision->contaminationSeverityScore);
    }

    public function test_concurrent_session_simulation_captures_browser_and_logout_risks(): void
    {
        $service = app(GuardSplitSimulationService::class);
        $scenario = collect($service->scenarios())->firstWhere('key', 'logout_one_context_other_remains_active');

        $collision = $service->analyzeCollision($scenario);

        $this->assertTrue($collision->collisionDetected);
        $this->assertContains('browser_multi_tab_shared_cookie', $collision->collisionVectors);
        $this->assertContains('logout_propagation_collision', $collision->collisionVectors);
        $this->assertSame('invalidate_merchant_scope_only', $collision->splitSafeLogoutMap['merchant_guard']);
    }

    public function test_csrf_mixed_context_simulation_flags_refresh_collision(): void
    {
        $service = app(GuardSplitSimulationService::class);
        $scenario = collect($service->scenarios())->firstWhere('key', 'csrf_refresh_during_mixed_context_usage');

        $ownership = $service->simulateScenario($scenario);
        $collision = $service->analyzeCollision($scenario);

        $this->assertTrue($ownership->csrfConflict);
        $this->assertGreaterThanOrEqual(70, $collision->csrfRefreshRisk);
    }
}
