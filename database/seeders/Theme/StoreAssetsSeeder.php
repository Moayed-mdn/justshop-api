<?php

declare(strict_types=1);

namespace Database\Seeders\Theme;

use App\Enums\Theme\AssetTypeEnum;
use App\Models\Store;
use App\Models\Asset\StoreAsset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Store Assets Seeder - Creates Sample Assets for Stores
 * 
 * This seeder creates:
 * - Logo images (using Unsplash placeholder)
 * - Favicon images
 * - Banner images for hero sections
 * - Product showcase images
 */
class StoreAssetsSeeder extends Seeder
{
    private array $sampleAssets = [
        'logos' => [
            'https://images.unsplash.com/photo-1599305445671-ac291c95aaa9',
            'https://images.unsplash.com/photo-1611162616475-46b635cb6868',
            'https://images.unsplash.com/photo-1614624532983-4ce03382d63d',
        ],
        'banners' => [
            'https://images.unsplash.com/photo-1441986300917-64674bd600d8',
            'https://images.unsplash.com/photo-1483985988355-763728e1935b',
            'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da',
            'https://images.unsplash.com/photo-1542744094-3a31f272c490',
            'https://images.unsplash.com/photo-1556742111-a301076d9d18',
        ],
        'favicons' => [
            'https://images.unsplash.com/photo-1626785774573-4b799315345d',
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $stores = Store::all();

            foreach ($stores as $store) {
                $this->command->info("Creating assets for store: {$store->name}");
                $this->seedAssetsForStore($store);
            }
        });

        $this->command->info('✅ Store assets seeded successfully for all stores');
    }

    private function seedAssetsForStore(Store $store): void
    {
        $logoUrl = $this->sampleAssets['logos'][array_rand($this->sampleAssets['logos'])];
        
        // Create Logo
        $logo = StoreAsset::create([
            'store_id' => $store->id,
            'type' => AssetTypeEnum::LOGO,
            'name' => 'Store Logo',
            'file_path' => 'assets/logos/logo-' . $store->id . '.jpg',
            'file_url' => $logoUrl,
            'alt_text' => $store->name . ' Logo',
            'mime_type' => 'image/jpeg',
            'file_size' => 102400, // 100KB fake size
            'width' => 400,
            'height' => 150,
        ]);

        // Update store with logo URL
        $store->update([
            'logo_url' => $logo->file_url,
        ]);

        $faviconUrl = $this->sampleAssets['favicons'][0];
        
        // Create Favicon
        $favicon = StoreAsset::create([
            'store_id' => $store->id,
            'type' => AssetTypeEnum::FAVICON,
            'name' => 'Store Favicon',
            'file_path' => 'assets/favicons/favicon-' . $store->id . '.ico',
            'file_url' => $faviconUrl,
            'alt_text' => $store->name . ' Favicon',
            'mime_type' => 'image/x-icon',
            'file_size' => 5120, // 5KB fake size
            'width' => 32,
            'height' => 32,
        ]);

        // Update store with favicon URL
        $store->update([
            'favicon_url' => $favicon->file_url,
        ]);

        // Create Banner Images
        foreach ($this->sampleAssets['banners'] as $index => $bannerUrl) {
            StoreAsset::create([
                'store_id' => $store->id,
                'type' => AssetTypeEnum::BANNER,
                'name' => 'Hero Banner ' . ($index + 1),
                'file_path' => 'assets/banners/banner-' . $store->id . '-' . ($index + 1) . '.jpg',
                'file_url' => $bannerUrl,
                'alt_text' => 'Hero banner image showcasing products',
                'mime_type' => 'image/jpeg',
                'file_size' => 512000 + ($index * 10000), // Varying sizes
                'width' => 1920,
                'height' => 1080,
            ]);
        }

        // Create Additional Product Showcase Images
        for ($i = 1; $i <= 3; $i++) {
            StoreAsset::create([
                'store_id' => $store->id,
                'type' => AssetTypeEnum::OTHER,
                'name' => 'Product Showcase ' . $i,
                'file_path' => 'assets/products/showcase-' . $store->id . '-' . $i . '.jpg',
                'file_url' => 'https://images.unsplash.com/photo-' . (1500000000000 + $i * 100000),
                'alt_text' => 'Product showcase image ' . $i,
                'mime_type' => 'image/jpeg',
                'file_size' => 256000,
                'width' => 800,
                'height' => 600,
            ]);
        }

        $this->command->info("  ✓ Created " . (2 + count($this->sampleAssets['banners']) + 3) . " assets");
    }
}

