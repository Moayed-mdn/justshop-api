<?php

declare(strict_types=1);

namespace App\Console\Commands\Architecture;

use App\Support\FeatureFlags\FeatureFlag;
use Illuminate\Console\Command;

class ValidateFeatureFlags extends Command
{
    protected $signature = 'architecture:validate-feature-flags
                            {--json : Output results as JSON}
                            {--fail-on-violations : Exit with non-zero code if violations found}';

    protected $description = 'Validate feature flag governance compliance';

    public function handle(): int
    {
        $this->info('Wave 1 Governance — Feature Flag Validation');
        $this->newLine();

        $errors = FeatureFlag::validateAll();

        if ($this->option('json')) {
            $this->outputJson($errors);
        } else {
            $this->outputHuman($errors);
        }

        if ($this->option('fail-on-violations') && !empty($errors)) {
            return 1;
        }

        return empty($errors) ? 0 : 1;
    }

    private function outputJson(array $errors): void
    {
        $flags = FeatureFlag::all();
        
        $this->line(json_encode([
            'total_flags' => count($flags),
            'valid_flags' => count($flags) - count($errors),
            'invalid_flags' => count($errors),
            'errors' => $errors,
            'by_category' => $this->groupByCategory($flags),
            'by_wave' => $this->groupByWave($flags),
            'kill_switches' => array_keys(FeatureFlag::killSwitches()),
        ], JSON_PRETTY_PRINT));
    }

    private function outputHuman(array $errors): void
    {
        $flags = FeatureFlag::all();
        $totalFlags = count($flags);
        $invalidFlags = count($errors);
        $validFlags = $totalFlags - $invalidFlags;

        if (empty($errors)) {
            $this->info("✓ All {$totalFlags} feature flags are valid");
            $this->newLine();
            $this->displaySummary($flags);
            return;
        }

        $this->error("✗ Found {$invalidFlags} invalid feature flags out of {$totalFlags}");
        $this->newLine();

        foreach ($errors as $flag => $flagErrors) {
            $this->warn("Flag: {$flag}");
            foreach ($flagErrors as $error) {
                $this->line("  - {$error}");
            }
            $this->newLine();
        }

        $this->displaySummary($flags);
    }

    private function displaySummary(array $flags): void
    {
        $this->info('Feature Flag Summary:');
        $this->newLine();

        $byCategory = $this->groupByCategory($flags);
        $this->table(
            ['Category', 'Count'],
            array_map(fn($cat, $count) => [$cat, $count], array_keys($byCategory), $byCategory)
        );

        $this->newLine();
        $byWave = $this->groupByWave($flags);
        $this->table(
            ['Wave', 'Count'],
            array_map(fn($wave, $count) => [$wave, $count], array_keys($byWave), $byWave)
        );

        $killSwitches = FeatureFlag::killSwitches();
        $this->newLine();
        $this->info('Kill Switches: ' . count($killSwitches));
        foreach (array_keys($killSwitches) as $flag) {
            $this->line("  - {$flag}");
        }
    }

    private function groupByCategory(array $flags): array
    {
        $grouped = [];
        foreach ($flags as $config) {
            if (is_array($config)) {
                $category = $config['category'] ?? 'uncategorized';
                $grouped[$category] = ($grouped[$category] ?? 0) + 1;
            }
        }
        ksort($grouped);
        return $grouped;
    }

    private function groupByWave(array $flags): array
    {
        $grouped = [];
        foreach ($flags as $config) {
            if (is_array($config)) {
                $wave = $config['introduced_wave'] ?? 'unknown';
                $grouped[$wave] = ($grouped[$wave] ?? 0) + 1;
            }
        }
        ksort($grouped);
        return $grouped;
    }
}
