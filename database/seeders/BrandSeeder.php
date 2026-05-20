<?php
// database/seeders/BrandSeeder.php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
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
                ['slug' => Str::slug($brand['name'])],
                [
                    'name'        => $brand['name'],
                    'description' => $brand['description'],
                    'is_active'   => true,
                    'store_id'    => 1,
                ]
            );
        }

        $this->command->info('✅ Brands seeded!');
    }
}