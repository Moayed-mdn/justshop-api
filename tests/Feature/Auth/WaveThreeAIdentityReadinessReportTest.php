<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class WaveThreeAIdentityReadinessReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_wave_three_a_readiness_report_is_generated_as_machine_readable_artifact(): void
    {
        $outputPath = storage_path('app/testing/wave3a-readiness-report.json');
        File::delete($outputPath);

        $this->artisan('architecture:wave3a-readiness-report', [
            '--output' => $outputPath,
        ])->assertSuccessful();

        $this->assertFileExists($outputPath);

        $report = json_decode((string) File::get($outputPath), true);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('identity_context_health', $report);
        $this->assertArrayHasKey('onboarding_isolation_health', $report);
        $this->assertArrayHasKey('route_domain_isolation_health', $report);
        $this->assertArrayHasKey('cross_context_telemetry', $report);
        $this->assertArrayHasKey('remaining_wave4_blockers', $report);
        $this->assertArrayHasKey('guard_split_preparation', $report);
        $this->assertSame('healthy', $report['identity_context_health']['status']);
        $this->assertSame('more_normalization_required', $report['guard_split_preparation']['status']);
    }
}
