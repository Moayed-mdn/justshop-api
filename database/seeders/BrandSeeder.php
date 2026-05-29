<?php
// database/seeders/BrandSeeder.php

namespace Database\Seeders;

use App\Models\Brand;
use Database\Seeders\Concerns\SeedsDemoStore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    use SeedsDemoStore;

    public function run(): void
    {
        $storeId = $this->demoStoreId();
        $brands = [
            ['name' => 'Apple',   'description' => 'Think different.'],
            ['name' => 'Samsung', 'description' => 'Inspire the world, create the future.'],
            ['name' => 'Xiaomi',  'description' => 'Innovation for everyone.'],
            ['name' => 'Dell',    'description' => 'Technology that drives human progress.'],
            ['name' => 'HP',      'description' => 'Keep reinventing.'],
            ['name' => 'Lenovo',  'description' => 'Smarter technology for all.'],
            ['name' => 'ASUS',    'description' => 'In search of incredible.'],
            ['name' => 'Sony',    'description' => 'Be moved.'],
            ['name' => 'Nike',    'description' => 'Just do it.'],
            ['name' => 'IKEA',    'description' => 'Creating a better everyday life.'],
            ['name' => 'Fitbit',  'description' => 'Find your fit.'],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(
                [
                    'slug' => Str::slug($brand['name']),
                    'store_id' => $storeId,
                ],
                [
                    'name'        => $brand['name'],
                    'description' => $brand['description'],
                    'is_active'   => true,
                ]
            );
        }

        $this->command->info('✅ Brands seeded!');
    }
}