<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class WaveThreeBGuardReadinessReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_wave_three_b_guard_readiness_report_is_generated_as_machine_readable_artifact(): void
    {
        $outputPath = storage_path('app/testing/wave3b-guard-readiness-report.json');
        File::delete($outputPath);

        $this->artisan('architecture:wave3b-guard-readiness-report', [
            '--output' => $outputPath,
        ])->assertSuccessful();

        $this->assertFileExists($outputPath);

        $report = json_decode((string) File::get($outputPath), true);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('session_ownership_model', $report);
        $this->assertArrayHasKey('guard_shadow_infrastructure', $report);
        $this->assertArrayHasKey('session_contamination_readiness', $report);
        $this->assertArrayHasKey('logout_and_csrf_preparation', $report);
        $this->assertArrayHasKey('frontend_session_metadata', $report);
        $this->assertArrayHasKey('remaining_guard_split_blockers', $report);
        $this->assertArrayHasKey('guard_split_gate', $report);
        $this->assertSame('healthy', $report['session_ownership_model']['status']);
        $this->assertSame('preparation_only', $report['guard_split_gate']['status']);
    }
}
