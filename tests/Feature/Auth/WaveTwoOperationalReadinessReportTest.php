<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class WaveTwoOperationalReadinessReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_wave_two_readiness_report_is_generated_as_machine_readable_artifact(): void
    {
        $outputPath = storage_path('app/testing/wave2-readiness-report.json');
        File::delete($outputPath);

        $this->artisan('architecture:wave2-readiness-report', [
            '--output' => $outputPath,
        ])->assertSuccessful();

        $this->assertFileExists($outputPath);

        $report = json_decode((string) File::get($outputPath), true);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('bootstrap_parity_health', $report);
        $this->assertArrayHasKey('drift_counts', $report);
        $this->assertArrayHasKey('authorization_triage', $report);
        $this->assertArrayHasKey('normalization_progress', $report);
        $this->assertArrayHasKey('tenant_isolation_status', $report);
        $this->assertArrayHasKey('policy_instrumentation_coverage', $report);
        $this->assertArrayHasKey('observability_health', $report);
        $this->assertArrayHasKey('wave3_gate', $report);
        $this->assertTrue($report['bootstrap_parity_health']['shadow_parity_instrumented']);
        $this->assertSame(0, $report['normalization_progress']['hidden_authorization_remaining']);
        $this->assertSame('blocked', $report['wave3_gate']['status']);
    }
}
