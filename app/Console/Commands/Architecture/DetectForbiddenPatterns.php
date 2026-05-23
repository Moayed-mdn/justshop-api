<?php

declare(strict_types=1);

namespace App\Console\Commands\Architecture;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DetectForbiddenPatterns extends Command
{
    protected $signature = 'architecture:detect-forbidden-patterns
                            {--json : Output results as JSON}
                            {--fail-on-violations : Exit with non-zero code if violations found}';

    protected $description = 'Detect forbidden patterns that violate Wave 1 governance';

    private array $violations = [];

    public function handle(): int
    {
        $this->info('Wave 1 Governance — Forbidden Pattern Detection');
        $this->newLine();

        $this->detectEnvUsageViolations();
        $this->detectSensitiveLoggingPatterns();
        $this->detectGraphQLDebugExposure();
        $this->detectUnsafeDebugConfiguration();

        return $this->outputResults();
    }

    private function detectEnvUsageViolations(): void
    {
        $this->info('Scanning for direct env() usage outside config layer...');

        $appFiles = $this->getPhpFiles(app_path());
        
        foreach ($appFiles as $file) {
            $filePath = $file->getPathname();
            $content = File::get($filePath);
            $lines = explode("\n", $content);

            foreach ($lines as $lineNumber => $line) {
                // Skip comments
                if (preg_match('/^\s*\/\//', $line) || preg_match('/^\s*\*/', $line)) {
                    continue;
                }

                // Detect env() calls
                if (preg_match('/\benv\s*\(/', $line)) {
                    $this->violations[] = [
                        'category' => 'forbidden_env_usage',
                        'severity' => 'high',
                        'file' => $this->relativePath($filePath),
                        'line' => $lineNumber + 1,
                        'message' => 'Direct env() usage outside config layer',
                        'snippet' => trim($line),
                        'fingerprint' => $this->fingerprint($filePath, $lineNumber, 'env_usage'),
                    ];
                }
            }
        }
    }

    private function detectSensitiveLoggingPatterns(): void
    {
        $this->info('Scanning for sensitive data logging patterns...');

        $appFiles = $this->getPhpFiles(app_path());
        
        $sensitivePatterns = [
            'signature' => '/Log::(info|debug|warning|error|critical).*signature/i',
            'token' => '/Log::(info|debug|warning|error|critical).*\btoken\b/i',
            'authorization' => '/Log::(info|debug|warning|error|critical).*authorization/i',
            'password' => '/Log::(info|debug|warning|error|critical).*password/i',
            'cookie' => '/Log::(info|debug|warning|error|critical).*cookie/i',
            'session_id' => '/Log::(info|debug|warning|error|critical).*session.*id/i',
        ];

        foreach ($appFiles as $file) {
            $filePath = $file->getPathname();
            $content = File::get($filePath);
            $lines = explode("\n", $content);

            foreach ($lines as $lineNumber => $line) {
                foreach ($sensitivePatterns as $type => $pattern) {
                    if (preg_match($pattern, $line)) {
                        // Check if it's using request() or similar unsafe patterns
                        if (preg_match('/request\(\)|->all\(\)|->input\(|->query\(|->header\(/', $line)) {
                            $this->violations[] = [
                                'category' => 'sensitive_logging',
                                'severity' => 'critical',
                                'type' => $type,
                                'file' => $this->relativePath($filePath),
                                'line' => $lineNumber + 1,
                                'message' => "Potential sensitive data logging: {$type}",
                                'snippet' => trim($line),
                                'fingerprint' => $this->fingerprint($filePath, $lineNumber, "sensitive_log_{$type}"),
                            ];
                        }
                    }
                }
            }
        }
    }

    private function detectGraphQLDebugExposure(): void
    {
        $this->info('Checking GraphQL debug configuration...');

        $lighthouseConfig = config_path('lighthouse.php');
        
        if (File::exists($lighthouseConfig)) {
            $content = File::get($lighthouseConfig);
            
            // Check for debug mode in production-like settings
            if (preg_match("/'debug'\s*=>\s*(env\([^)]*\)|true)/", $content, $matches)) {
                $this->violations[] = [
                    'category' => 'graphql_debug_exposure',
                    'severity' => 'high',
                    'file' => 'config/lighthouse.php',
                    'line' => 0,
                    'message' => 'GraphQL debug mode may expose sensitive information',
                    'snippet' => trim($matches[0] ?? ''),
                    'fingerprint' => $this->fingerprint($lighthouseConfig, 0, 'graphql_debug'),
                ];
            }
        }
    }

    private function detectUnsafeDebugConfiguration(): void
    {
        $this->info('Checking for unsafe debug configuration...');

        $appConfig = config_path('app.php');
        
        if (File::exists($appConfig)) {
            $content = File::get($appConfig);
            $lines = explode("\n", $content);

            foreach ($lines as $lineNumber => $line) {
                // Check for hardcoded debug = true
                if (preg_match("/'debug'\s*=>\s*true/", $line)) {
                    $this->violations[] = [
                        'category' => 'unsafe_debug_config',
                        'severity' => 'high',
                        'file' => 'config/app.php',
                        'line' => $lineNumber + 1,
                        'message' => 'Hardcoded debug mode enabled',
                        'snippet' => trim($line),
                        'fingerprint' => $this->fingerprint($appConfig, $lineNumber, 'debug_hardcoded'),
                    ];
                }
            }
        }
    }

    private function getPhpFiles(string $directory): array
    {
        if (!File::isDirectory($directory)) {
            return [];
        }

        return File::allFiles($directory);
    }

    private function relativePath(string $path): string
    {
        return str_replace(base_path() . '/', '', $path);
    }

    private function fingerprint(string $file, int $line, string $type): string
    {
        return sha1($this->relativePath($file) . ':' . $line . ':' . $type);
    }

    private function outputResults(): int
    {
        $totalViolations = count($this->violations);

        if ($this->option('json')) {
            $this->line(json_encode([
                'total_violations' => $totalViolations,
                'violations' => $this->violations,
                'by_category' => $this->groupByCategory(),
                'by_severity' => $this->groupBySeverity(),
            ], JSON_PRETTY_PRINT));
        } else {
            $this->displayHumanReadable();
        }

        if ($this->option('fail-on-violations') && $totalViolations > 0) {
            return 1;
        }

        return 0;
    }

    private function displayHumanReadable(): void
    {
        $this->newLine();

        if (empty($this->violations)) {
            $this->info('✓ No forbidden pattern violations detected');
            return;
        }

        $this->error("✗ Found {$this->countViolations()} forbidden pattern violations");
        $this->newLine();

        $byCategory = $this->groupByCategory();
        
        foreach ($byCategory as $category => $violations) {
            $this->warn(strtoupper(str_replace('_', ' ', $category)) . " ({$violations})");
        }

        $this->newLine();
        $this->table(
            ['Severity', 'Category', 'File', 'Line', 'Message'],
            array_map(fn($v) => [
                $v['severity'],
                $v['category'],
                $v['file'],
                $v['line'],
                $v['message'],
            ], $this->violations)
        );

        $this->newLine();
        $this->warn('Run with --json for machine-readable output');
    }

    private function countViolations(): int
    {
        return count($this->violations);
    }

    private function groupByCategory(): array
    {
        $grouped = [];
        foreach ($this->violations as $violation) {
            $category = $violation['category'];
            $grouped[$category] = ($grouped[$category] ?? 0) + 1;
        }
        return $grouped;
    }

    private function groupBySeverity(): array
    {
        $grouped = [];
        foreach ($this->violations as $violation) {
            $severity = $violation['severity'];
            $grouped[$severity] = ($grouped[$severity] ?? 0) + 1;
        }
        return $grouped;
    }
}
