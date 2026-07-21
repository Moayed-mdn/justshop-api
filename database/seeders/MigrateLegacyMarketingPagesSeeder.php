<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Migrate Legacy marketing_pages → platform_marketing_pages
 * 
 * This migrates the 10 legacy marketing pages that laratenant-commerce
 * expects to find at /api/v1/public/cms/pages/{slug}
 */
class MigrateLegacyMarketingPagesSeeder extends Seeder
{
    public function run(): void
    {
        $creatorId = DB::table('users')->where('email', 'super@test.com')->value('id');

        // Clear existing platform_marketing_pages (disable FK checks)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('platform_marketing_sections')->truncate();
        DB::table('platform_marketing_pages')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Get all legacy pages
        $legacyPages = DB::table('marketing_pages')->get();

        $migrated = 0;
        foreach ($legacyPages as $legacy) {
            // Extract basic content from sections
            $sections = json_decode($legacy->sections ?? '{}', true);
            $content = $this->extractPlainTextContent($sections);
            $excerpt = substr(strip_tags($content), 0, 200);

            DB::table('platform_marketing_pages')->insert([
                'title' => $legacy->title,
                'slug' => $legacy->slug,
                'excerpt' => json_encode(['en' => $excerpt, 'ar' => '']),
                'content' => json_encode(['en' => $content, 'ar' => '']),
                'status' => $legacy->status,
                'published_at' => $legacy->published_at,
                'seo' => $legacy->seo,
                'template' => $legacy->type ?? 'default',
                'sort_order' => 0,
                'created_by' => $legacy->created_by ?? $creatorId,
                'updated_by' => $legacy->updated_by ?? $creatorId,
                'created_at' => $legacy->created_at,
                'updated_at' => $legacy->updated_at,
                'deleted_at' => $legacy->deleted_at,
            ]);

            $migrated++;
        }

        $this->command->info("✓ Migrated {$migrated} pages from marketing_pages → platform_marketing_pages");
        $this->command->warn("⚠ Legacy marketing_pages table can now be renamed/archived");
    }

    private function extractPlainTextContent(array $sections): string
    {
        $content = '';

        // Extract hero section
        if (isset($sections['hero'])) {
            $hero = $sections['hero'];
            $content .= '<h1>' . ($hero['title']['en'] ?? '') . '</h1>' . PHP_EOL;
            $content .= '<p>' . ($hero['subtitle']['en'] ?? '') . '</p>' . PHP_EOL . PHP_EOL;
        }

        // Extract features
        if (isset($sections['features']) && is_array($sections['features'])) {
            $content .= '<h2>Features</h2>' . PHP_EOL;
            foreach ($sections['features'] as $feature) {
                if (is_array($feature)) {
                    $content .= '<h3>' . ($feature['title']['en'] ?? '') . '</h3>' . PHP_EOL;
                    $content .= '<p>' . ($feature['description']['en'] ?? '') . '</p>' . PHP_EOL;
                }
            }
            $content .= PHP_EOL;
        }

        // Extract stats
        if (isset($sections['stats']) && is_array($sections['stats'])) {
            foreach ($sections['stats'] as $stat) {
                if (is_array($stat)) {
                    $content .= '**' . ($stat['value'] ?? '') . '** ' . ($stat['label']['en'] ?? '') . PHP_EOL;
                }
            }
            $content .= PHP_EOL;
        }

        // Extract CTA
        if (isset($sections['cta'])) {
            $cta = $sections['cta'];
            $content .= '<h2>' . ($cta['title']['en'] ?? '') . '</h2>' . PHP_EOL;
            $content .= '<p>' . ($cta['subtitle']['en'] ?? '') . '</p>' . PHP_EOL;
        }

        return trim($content) ?: 'Content migrated from legacy system. Please edit to add proper content.';
    }
}
