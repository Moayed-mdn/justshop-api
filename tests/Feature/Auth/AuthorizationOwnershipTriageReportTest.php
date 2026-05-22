<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AuthorizationOwnershipTriageReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_wave_2_5_authorization_triage_report_is_generated_as_machine_readable_artifact(): void
    {
        $outputPath = storage_path('app/testing/authorization-triage-report.json');
        File::delete($outputPath);

        $this->artisan('architecture:wave2-authorization-triage', [
            '--output' => $outputPath,
        ])->assertSuccessful();

        $this->assertFileExists($outputPath);

        $report = json_decode((string) File::get($outputPath), true);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('migration_priority_order', $report);
        $this->assertArrayHasKey('generic_current_store_triage', $report);
        $this->assertArrayHasKey('compatibility_bridges', $report);
        $this->assertSame(17, $report['summary']['generic_current_store_findings']);
        $this->assertSame(1, $report['migration_priority_order'][0]['priority']);
        $this->assertSame('brand', $report['migration_priority_order'][0]['domain']);
        $this->assertSame('safe_to_normalize_now', $report['migration_priority_order'][0]['status']);

        $classifications = $report['summary']['classifications'];
        $this->assertArrayNotHasKey('safe_to_normalize_now', $classifications);
        $this->assertSame(6, $classifications['requires_rbac_normalization_later']);
        $this->assertSame(6, $classifications['requires_membership_evolution_later']);
        $this->assertSame(5, $classifications['requires_wave_3_context']);
    }
}
