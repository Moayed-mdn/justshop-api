<?php

declare(strict_types=1);

namespace App\Console\Commands\Architecture;

use App\Services\Platform\Impersonation\ImpersonationGovernanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImpersonationAuditReportCommand extends Command
{
    protected $signature = 'architecture:impersonation-audit-report {--output=impersonation-audit-report.json}';
    protected $description = 'Generate impersonation governance and audit report';

    public function handle(ImpersonationGovernanceService $service): int
    {
        $this->info('Generating Impersonation Audit Report...');

        $report = $service->generateAuditReport();

        $outputFile = $this->option('output');
        File::put(base_path($outputFile), json_encode($report, JSON_PRETTY_PRINT));

        $this->info("Report generated: {$outputFile}");

        if ($report['security_violations_detected'] > 0) {
            $this->error("CRITICAL: {$report['security_violations_detected']} security violations detected in impersonation logs!");
        }

        return 0;
    }
}
