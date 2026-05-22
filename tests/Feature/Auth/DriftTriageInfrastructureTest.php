<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DriftTriageInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_drift_command_writes_machine_readable_report_with_regression_visibility_for_wave_2_5(): void
    {
        $outputPath = storage_path('app/testing/drift-report.json');
        File::delete($outputPath);

        $this->artisan('architecture:detect-authorization-drift', [
            '--output' => $outputPath,
            '--format' => 'json',
        ])->assertSuccessful();

        $this->assertFileExists($outputPath);

        $report = json_decode((string) File::get($outputPath), true);

        $this->assertIsArray($report);
        $this->assertSame('warning', $report['mode']);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('trend', $report);
        $this->assertArrayHasKey('active_findings', $report);
        $this->assertNotEmpty($report['active_findings']);
        $this->assertArrayHasKey('severity', $report['active_findings'][0]);
        $this->assertArrayHasKey('fingerprint', $report['active_findings'][0]);
        $this->assertSame(0, (int) ($report['summary']['by_category']['hidden_authorization'] ?? 0));
        $this->assertLessThanOrEqual(17, (int) ($report['summary']['by_type']['generic_current_store_authorize'] ?? 0));

        $filesWithActiveFindings = array_column($report['active_findings'], 'file');

        $this->assertNotContains('app/Http/Requests/Admin/Tag/ListTagsRequest.php', $filesWithActiveFindings);
        $this->assertNotContains('app/Http/Requests/Admin/Tag/UpdateTagRequest.php', $filesWithActiveFindings);

        $normalizedControllerLeakage = collect($report['active_findings'])
            ->filter(fn (array $finding): bool => ($finding['type'] ?? null) === 'generic_current_store_authorize')
            ->pluck('file')
            ->intersect([
                'app/Http/Controllers/Api/Admin/Brand/AdminBrandController.php',
                'app/Http/Controllers/Api/Admin/Category/AdminCategoryController.php',
                'app/Http/Controllers/Api/Admin/Dashboard/AdminDashboardController.php',
                'app/Http/Controllers/Api/Admin/Tag/AdminTagController.php',
            ]);

        $this->assertCount(0, $normalizedControllerLeakage);
    }

    public function test_drift_command_supports_allowlist_and_baseline_snapshots(): void
    {
        $baseDirectory = storage_path('app/testing/wave2');
        File::ensureDirectoryExists($baseDirectory);

        $firstReportPath = $baseDirectory . '/drift-first.json';
        $baselinePath = $baseDirectory . '/drift-baseline.json';
        $allowlistPath = $baseDirectory . '/drift-allowlist.json';
        $secondReportPath = $baseDirectory . '/drift-second.json';

        $this->artisan('architecture:detect-authorization-drift', [
            '--output' => $firstReportPath,
            '--format' => 'json',
        ])->assertSuccessful();

        $firstReport = json_decode((string) File::get($firstReportPath), true);
        $this->assertIsArray($firstReport);
        $firstFingerprint = $firstReport['active_findings'][0]['fingerprint'];

        File::put($allowlistPath, json_encode([
            'fingerprints' => [$firstFingerprint],
            'paths' => [],
            'categories' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->artisan('architecture:detect-authorization-drift', [
            '--output' => $baselinePath,
            '--format' => 'json',
            '--allowlist' => $allowlistPath,
            '--write-baseline' => true,
        ])->assertSuccessful();

        $this->assertFileExists(config('migration.drift_detection.baseline_path'));

        $this->artisan('architecture:detect-authorization-drift', [
            '--output' => $secondReportPath,
            '--format' => 'json',
            '--allowlist' => $allowlistPath,
            '--baseline' => config('migration.drift_detection.baseline_path'),
        ])->assertSuccessful();

        $secondReport = json_decode((string) File::get($secondReportPath), true);
        $this->assertTrue($secondReport['trend']['baseline_available']);
        $this->assertSame(0, $secondReport['trend']['new_since_baseline']);
        $this->assertSame(0, $secondReport['trend']['resolved_since_baseline']);
        $this->assertNotContains($firstFingerprint, array_column($secondReport['active_findings'], 'fingerprint'));
    }
}
