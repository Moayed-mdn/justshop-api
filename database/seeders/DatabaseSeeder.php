<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

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
            FakeSalesSeeder::class,
            ReviewSeeder::class,
            HeroBannerSeeder::class,
            CmsBlogSeeder::class,
            CmsDocumentationSeeder::class,
            CmsMarketingSeeder::class,
        ]);
    }
}