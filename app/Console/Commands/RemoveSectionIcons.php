<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveSectionIcons extends Command
{
    protected $signature = 'cms:remove-icons 
                          {--dry-run : Show what would be deleted without actually deleting}
                          {--type=* : Specific section types to target (features, action_cards, industry_use_cases)}';

    protected $description = 'Remove icon references from CMS page sections';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $types = $this->option('type');

        $this->info('🔍 Scanning for sections with icons...');

        // Check store marketing page sections
        $this->processTable('store_marketing_page_sections', $types, $dryRun);

        // Check platform marketing page sections
        if (DB::getSchemaBuilder()->hasTable('platform_marketing_sections')) {
            $this->processTable('platform_marketing_sections', $types, $dryRun);
        }

        if ($dryRun) {
            $this->info("\n✅ Dry run complete. No changes were made.");
            $this->info("Run without --dry-run to actually remove icons.");
        } else {
            $this->info("\n✅ Icons removed successfully!");
        }

        return 0;
    }

    private function processTable(string $table, array $types, bool $dryRun): void
    {
        $this->line("\n📋 Checking table: {$table}");

        $query = DB::table($table)
            ->whereNotNull('content')
            ->where(function ($q) {
                $q->whereRaw("JSON_CONTAINS_PATH(content, 'one', '$.items[*].icon')")
                  ->orWhereRaw("JSON_EXTRACT(content, '$.items') IS NOT NULL");
            });

        if (!empty($types)) {
            $query->whereIn('type', $types);
        }

        $sections = $query->get(['id', 'type', 'content']);

        if ($sections->isEmpty()) {
            $this->comment("  No sections with icons found.");
            return;
        }

        $this->info("  Found {$sections->count()} section(s) with icons:");

        foreach ($sections as $section) {
            $content = json_decode($section->content, true);
            
            if (!isset($content['items'])) {
                continue;
            }

            $iconValues = [];
            foreach ($content['items'] as $item) {
                if (isset($item['icon'])) {
                    $iconValues[] = $item['icon'];
                }
            }

            if (empty($iconValues)) {
                continue;
            }

            $this->line("    ID: {$section->id}, Type: {$section->type}");
            $this->line("    Icons: " . implode(', ', array_unique($iconValues)));

            if (!$dryRun) {
                // Remove icons from items
                foreach ($content['items'] as &$item) {
                    unset($item['icon']);
                }

                DB::table($table)
                    ->where('id', $section->id)
                    ->update(['content' => json_encode($content)]);

                $this->comment("    ✓ Removed");
            }
        }
    }
}
