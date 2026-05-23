<?php

declare(strict_types=1);

namespace App\Console\Commands\Architecture;

use App\Services\Governance\AuthorizationTopologyLocker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuthorizationTopologyReportCommand extends Command
{
    protected $signature = 'architecture:authorization-topology-report {--output=authorization-topology-report.json} {--fail-on-drift}';
    protected $description = 'Generate authorization topology report and enforce architectural locking';

    public function handle(AuthorizationTopologyLocker $locker): int
    {
        $this->info('Generating Authorization Topology Report...');

        $report = $locker->generateTopologyReport();

        $outputFile = $this->option('output');
        File::put(base_path($outputFile), json_encode($report, JSON_PRETTY_PRINT));

        $this->info("Report generated: {$outputFile}");

        if ($report['topology_drift_detected']) {
            $this->warn("WARNING: Authorization topology drift detected! Score: {$report['drift_score']}");
            
            if ($this->option('fail-on-drift')) {
                $this->error('CRITICAL: Architecture lock violation detected in CI mode!');
                return 1;
            }
        }

        return 0;
    }
}
