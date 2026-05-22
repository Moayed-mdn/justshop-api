<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Non-destructive migration: normalize the seo JSON column in marketing_pages.
 *
 * The seo column already exists as JSON.
 * This migration backfills any existing rows with the full
 * normalized SEO schema structure so the application layer
 * can safely access all expected keys.
 *
 * New schema enforced by application layer (SeoMetaDTO):
 * {
 *   "meta_title":       {"en": null, "ar": null},
 *   "meta_description": {"en": null, "ar": null},
 *   "canonical_url":    null,
 *   "og_image":         null,
 *   "og_title":         {"en": null, "ar": null},
 *   "og_description":   {"en": null, "ar": null},
 *   "robots":           "index,follow",
 *   "twitter_card":     "summary_large_image",
 *   "structured_data":  null
 * }
 *
 * NO columns are added or removed.
 * NO data is destroyed.
 * Existing values are preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pages = DB::table('marketing_pages')
            ->whereNull('deleted_at')
            ->get(['id', 'seo']);

        $defaults = [
            'meta_title'       => ['en' => null, 'ar' => null],
            'meta_description' => ['en' => null, 'ar' => null],
            'canonical_url'    => null,
            'og_image'         => null,
            'og_title'         => ['en' => null, 'ar' => null],
            'og_description'   => ['en' => null, 'ar' => null],
            'robots'           => 'index,follow',
            'twitter_card'     => 'summary_large_image',
            'structured_data'  => null,
        ];

        foreach ($pages as $page) {
            $existing = json_decode((string) $page->seo, true) ?? [];

            // Merge: existing values win, defaults fill missing keys
            $normalized = array_merge($defaults, array_filter(
                $existing,
                fn ($value) => $value !== null && $value !== '' && $value !== [],
            ));

            // Ensure nested locale maps are preserved
            foreach (['meta_title', 'meta_description', 'og_title', 'og_description'] as $field) {
                if (isset($existing[$field])) {
                    // Preserve existing locale maps
                    $normalized[$field] = is_array($existing[$field])
                        ? array_merge($defaults[$field], $existing[$field])
                        : $existing[$field];
                }
            }

            DB::table('marketing_pages')
                ->where('id', $page->id)
                ->update(['seo' => json_encode($normalized)]);
        }
    }

    public function down(): void
    {
        // Non-destructive — no rollback needed
        // Original data is preserved within the normalized structure
    }
};
