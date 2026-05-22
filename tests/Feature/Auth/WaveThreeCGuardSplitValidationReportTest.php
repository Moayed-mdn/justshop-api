<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class WaveThreeCGuardSplitValidationReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_wave_three_c_guard_split_validation_report_is_generated_as_machine_readable_artifact(): void
    {
        $outputPath = storage_path('app/testing/wave3c-guard-split-validation-report.json');
        File::delete($outputPath);

        $this->artisan('architecture:wave3c-guard-split-validation-report', [
            '--output' => $outputPath,
        ])->assertSuccessful();

        $this->assertFileExists($outputPath);

        $report = json_decode((string) File::get($outputPath), true);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('guard_split_simulation_engine', $report);
        $this->assertArrayHasKey('concurrent_session_validation', $report);
        $this->assertArrayHasKey('csrf_ownership_validation', $report);
        $this->assertArrayHasKey('logout_semantics_validation', $report);
        $this->assertArrayHasKey('frontend_compatibility_readiness', $report);
        $this->assertArrayHasKey('session_contamination_stress', $report);
        $this->assertArrayHasKey('guard_readiness_scoring', $report);
        $this->assertArrayHasKey('operational_risk_analysis', $report);
        $this->assertArrayHasKey('remaining_guard_split_blockers', $report);
        $this->assertSame('healthy', $report['guard_split_simulation_engine']['status']);
        $this->assertContains($report['guard_readiness_scoring']['status'], ['READY', 'PARTIALLY_READY', 'BLOCKED']);
        $this->assertContains('shared session cookie remains authoritative', $report['remaining_guard_split_blockers']);
    }
}
