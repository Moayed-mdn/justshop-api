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
        // Wave 2.5 normalization is complete: all generic currentStore authorization
        // findings have been resolved. The count is now 0.
        $this->assertSame(0, $report['summary']['generic_current_store_findings']);
        $this->assertSame(1, $report['migration_priority_order'][0]['priority']);
        $this->assertSame('brand', $report['migration_priority_order'][0]['domain']);
        $this->assertSame('safe_to_normalize_now', $report['migration_priority_order'][0]['status']);

        // Wave 2.5 complete: no remaining findings to classify.
        $classifications = $report['summary']['classifications'];
        $this->assertEmpty($classifications);
    }
}
