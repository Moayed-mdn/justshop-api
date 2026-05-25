<?php

declare(strict_types=1);

namespace App\Console\Commands\Cms;

use App\Models\Cms\Marketing\Platform\PlatformMarketingPage;
use App\Models\Cms\MarketingPage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigratePlatformMarketingCommand extends Command
{
    protected $signature = 'cms:migrate-platform-marketing {--dry-run : Run without making changes}';

    protected $description = 'Migrate legacy platform marketing content to new platform_marketing_pages table';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Starting platform marketing migration...");

        // In the current legacy system, all marketing pages are in marketing_pages.
        // We only want to migrate "platform" pages (non-tenant).
        // Based on the docs, legacy MarketingPage might have a type or just be global.
        // Let's assume all existing ones are platform pages if they don't have a store_id.
        // Wait, legacy MarketingPage table doesn't have store_id.
        
        $legacyPages = MarketingPage::all();

        if ($legacyPages->isEmpty()) {
            $this->warn("No legacy marketing pages found.");
            return 0;
        }

        $this->info("Found {$legacyPages->count()} pages to migrate.");

        $migratedCount = 0;
        $skippedCount = 0;

        foreach ($legacyPages as $legacyPage) {
            // Check if already migrated by slug (simplified check)
            $slugs = $legacyPage->slug;
            $exists = false;
            
            if (is_array($slugs)) {
                foreach ($slugs as $locale => $slug) {
                    if (PlatformMarketingPage::query()->where("slug->{$locale}", $slug)->exists()) {
                        $exists = true;
                        break;
                    }
                }
            }

            if ($exists) {
                $this->line("Skipping page '{$legacyPage->id}' - slug already exists in new table.");
                $skippedCount++;
                continue;
            }

            if (!$dryRun) {
                DB::transaction(function () use ($legacyPage) {
                    $newPage = PlatformMarketingPage::create([
                        'title' => $legacyPage->title,
                        'slug' => $legacyPage->slug,
                        'excerpt' => $legacyPage->excerpt,
                        'content' => $legacyPage->sections, // In legacy, sections is the content
                        'status' => $legacyPage->status->value,
                        'published_at' => $legacyPage->published_at,
                        'seo' => $legacyPage->seo,
                        'template' => 'generic', // Default to generic
                        'sort_order' => 0,
                        'created_by' => $legacyPage->created_by,
                        'updated_by' => $legacyPage->updated_by,
                        'created_at' => $legacyPage->created_at,
                        'updated_at' => $legacyPage->updated_at,
                    ]);
                });
            }

            $this->line("Migrated page '{$legacyPage->id}' -> new platform page.");
            $migratedCount++;
        }

        $this->info("Migration completed.");
        $this->info("Migrated: {$migratedCount}");
        $this->info("Skipped: {$skippedCount}");

        if ($dryRun) {
            $this->info("DRY RUN: No changes were made to the database.");
        }

        return 0;
    }
}
