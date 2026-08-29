<?php

declare(strict_types=1);

namespace Database\Seeders\Theme;

use App\Enums\Theme\AssetTypeEnum;
use App\Models\Store;
use App\Models\Asset\StoreAsset;
use Database\Seeders\Concerns\GeneratesBrandAssets;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Store Assets Seeder - Creates On-Brand Assets for Stores
 *
 * This used to pick a "logo" at random from a handful of unrelated Unsplash
 * lifestyle photos, and one of the "Product Showcase" URLs was built from a
 * fabricated numeric id with no photo behind it (a guaranteed broken image).
 *
 * It now generates every asset as an SVG derived from the store's brand
 * palette (see GeneratesBrandAssets):
 * - Logo: an icon + wordmark lockup, not a photo of a stranger or a room.
 * - Favicon: the same mark, icon-only.
 * - Banners: abstract on-brand backdrops in 3 compositions, all built from
 *   the same palette as the logo and the active theme's buttons.
 * - Product showcase placeholders: deterministic picsum.photos URLs (the
 *   same convention DemoStorePresentationSeeder already uses for product
 *   images), so nothing can 404.
 *
 * This runs BEFORE RichThemeSeeder (see DatabaseSeeder's ordering comment
 * "Must run before themes"), so it can't read a Theme's colors — it owns
 * the canonical brand palette instead, and RichThemeSeeder's "Aurora"
 * variation reuses the exact same values to stay in sync.
 */
class StoreAssetsSeeder extends Seeder
{
    use GeneratesBrandAssets;

    public function run(): void
    {
        DB::transaction(function (): void {
            $stores = Store::all();

            foreach ($stores as $store) {
                $this->command->info("Creating brand assets for store: {$store->name}");
                $this->seedAssetsForStore($store);
            }
        });

        $this->command->info('✅ Store assets seeded successfully for all stores');
    }

    private function seedAssetsForStore(Store $store): void
    {
        $palette = $this->brandPalette();

        // ── Logo ─────────────────────────────────────────────────────
        $logoSvg = $this->logoSvg($store->name, $palette['primary'], $palette['secondary'], $palette['text']);
        $logoPath = $this->writeBrandAsset("assets/logos/logo-{$store->id}.svg", $logoSvg);

        $logo = StoreAsset::create([
            'store_id' => $store->id,
            'type' => AssetTypeEnum::LOGO,
            'name' => 'Store Logo',
            'file_path' => $logoPath,
            'file_url' => $logoPath,
            'alt_text' => $store->name . ' logo',
            'mime_type' => 'image/svg+xml',
            'file_size' => strlen($logoSvg),
            'width' => 320,
            'height' => 84,
        ]);

        $store->update(['logo_url' => $logo->file_url]);

        // ── Favicon ──────────────────────────────────────────────────
        $iconSvg = $this->iconSvg($store->name, $palette['primary'], $palette['secondary']);
        $iconPath = $this->writeBrandAsset("assets/favicons/favicon-{$store->id}.svg", $iconSvg);

        $favicon = StoreAsset::create([
            'store_id' => $store->id,
            'type' => AssetTypeEnum::FAVICON,
            'name' => 'Store Favicon',
            'file_path' => $iconPath,
            'file_url' => $iconPath,
            'alt_text' => $store->name . ' favicon',
            'mime_type' => 'image/svg+xml',
            'file_size' => strlen($iconSvg),
            'width' => 64,
            'height' => 64,
        ]);

        $store->update(['favicon_url' => $favicon->file_url]);

        // ── Hero / Banner Backdrops ──────────────────────────────────
        // Same palette, varied compositions — every banner still reads as
        // one coherent brand instead of unrelated stock photography.
        $bannerStyles = ['modern', 'modern', 'dark', 'minimal', 'modern'];

        foreach ($bannerStyles as $index => $style) {
            $bannerSvg = $this->heroBannerSvg(
                $palette['primary'],
                $palette['secondary'],
                $palette['accent'],
                $style === 'dark' ? '#111827' : $palette['background'],
                $style,
            );
            $bannerPath = $this->writeBrandAsset(
                'assets/banners/banner-' . $store->id . '-' . ($index + 1) . '.svg',
                $bannerSvg,
            );

            StoreAsset::create([
                'store_id' => $store->id,
                'type' => AssetTypeEnum::BANNER,
                'name' => 'Hero Banner ' . ($index + 1),
                'file_path' => $bannerPath,
                'file_url' => $bannerPath,
                'alt_text' => 'On-brand hero banner for ' . $store->name,
                'mime_type' => 'image/svg+xml',
                'file_size' => strlen($bannerSvg),
                'width' => 1600,
                'height' => 900,
            ]);
        }

        // ── Product Showcase Placeholders ───────────────────────────
        // Deterministic, always-resolvable photography (same convention as
        // DemoStorePresentationSeeder) instead of a fabricated Unsplash id.
        for ($i = 1; $i <= 3; $i++) {
            StoreAsset::create([
                'store_id' => $store->id,
                'type' => AssetTypeEnum::OTHER,
                'name' => 'Product Showcase ' . $i,
                'file_path' => 'assets/products/showcase-' . $store->id . '-' . $i . '.jpg',
                'file_url' => "https://picsum.photos/seed/justshop-showcase-{$store->id}-{$i}/800/600",
                'alt_text' => 'Product showcase image ' . $i,
                'mime_type' => 'image/jpeg',
                'file_size' => 256000,
                'width' => 800,
                'height' => 600,
            ]);
        }

        $this->command->info('  ✓ Created ' . (2 + count($bannerStyles) + 3) . ' assets');
    }
}
