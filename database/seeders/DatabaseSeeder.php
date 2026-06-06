<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use App\Models\Store;
use App\Services\Storefront\Runtime\RuntimeCacheService;
use Database\Seeders\Theme\DefaultThemeSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            PermissionSeeder::class,
            StoreSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            ProductSeeder::class,
            DemoStoreVolumeSeeder::class,
            DemoStorePresentationSeeder::class,
            FakeSalesSeeder::class,
            ReviewSeeder::class,
            HeroBannerSeeder::class,
            StorefrontRuntimeDevSeeder::class,
            CmsBlogSeeder::class,
            CmsDocumentationSeeder::class,
            CmsMarketingSeeder::class,
            DefaultThemeSeeder::class,
        ]);

        $store = Store::query()->where('slug', 'merchant-store')->first();

        if ($store instanceof Store) {
            $invalidated = app(RuntimeCacheService::class)->invalidateTenantArtifacts($store);

            $this->command?->info(sprintf(
                'Storefront runtime cache invalidated for %s (%d entries).',
                $store->slug,
                $invalidated,
            ));
        }
    }
}
