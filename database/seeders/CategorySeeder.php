<?php

namespace Database\Seeders;

use App\Models\Category;
use Database\Seeders\Concerns\SeedsDemoStore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    use SeedsDemoStore;

    public function run()
    {
        $storeId = $this->demoStoreId();
        $sortOrder = 0;
        $tree = [
            'Electronics' => [
                'en' => 'Electronics',
                'ar' => 'الإلكترونيات',
                'children' => [
                    'Phones' => ['en' => 'Phones', 'ar' => 'الهواتف'],
                    'Laptops' => ['en' => 'Laptops', 'ar' => 'أجهزة الكمبيوتر المحمولة'],
                    'Accessories' => ['en' => 'Accessories', 'ar' => 'الإكسسوارات'],
                ]
            ],
            'Fashion' => [
                'en' => 'Fashion',
                'ar' => 'الأزياء',
                'children' => [
                    'Men Clothing' => ['en' => 'Men Clothing', 'ar' => 'ملابس رجالية'],
                    'Women Clothing' => ['en' => 'Women Clothing', 'ar' => 'ملابس نسائية'],
                    'Shoes' => ['en' => 'Shoes', 'ar' => 'الأحذية'],
                ]
            ],
            'Home & Kitchen' => [
                'en' => 'Home & Kitchen',
                'ar' => 'المنزل والمطبخ',
                'children' => [
                    'Appliances' => ['en' => 'Appliances', 'ar' => 'الأجهزة المنزلية'],
                    'Furniture' => ['en' => 'Furniture', 'ar' => 'الأثاث'],
                    'Decor' => ['en' => 'Decor', 'ar' => 'الديكور'],
                ]
            ],
            'Health & Beauty' => [
                'en' => 'Health & Beauty',
                'ar' => 'الصحة والجمال',
                'children' => [
                    'Skincare' => ['en' => 'Skincare', 'ar' => 'العناية بالبشرة'],
                    'Hair Care' => ['en' => 'Hair Care', 'ar' => 'العناية بالشعر'],
                    'Perfumes' => ['en' => 'Perfumes', 'ar' => 'العطور'],
                ]
            ],
            'Sports & Outdoors' => [
                'en' => 'Sports & Outdoors',
                'ar' => 'الرياضة والهواء الطلق',
                'children' => [
                    'Fitness Equipment' => ['en' => 'Fitness Equipment', 'ar' => 'معدات اللياقة البدنية'],
                    'Outdoor Gear' => ['en' => 'Outdoor Gear', 'ar' => 'معدات الهواء الطلق'],
                    'Sportswear' => ['en' => 'Sportswear', 'ar' => 'الملابس الرياضية'],
                ]
            ],
        ];

        foreach ($tree as $parentKey => $parentData) {
            // Create parent category
            $parentCategory = Category::create([
                'parent_id' => null,
                'slug' => Str::slug($parentData['en']),
                'store_id' => $storeId,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]);
            $sortOrder++;

            // Add translations for parent category
            $parentCategory->translations()->createMany([
                [
                    'locale' => 'en',
                    'name' => $parentData['en'],
                    'slug' => Str::slug($parentData['en'])
                ],
                [
                    'locale' => 'ar',
                    'name' => $parentData['ar'],
                    'slug' => Str::slug($parentData['ar'])
                ]
            ]);

            // Create child categories
            foreach ($parentData['children'] as $childKey => $childData) {
                $childCategory = Category::create([
                    'parent_id' => $parentCategory->id,
                    'slug' => Str::slug($childData['en']),
                    'store_id' => $storeId,
                    'is_active' => true,
                ]);

                // Add translations for child category
                $childCategory->translations()->createMany([
                    [
                        'locale' => 'en',
                        'name' => $childData['en'],
                        'slug' => Str::slug($childData['en'])
                    ],
                    [
                        'locale' => 'ar',
                        'name' => $childData['ar'],
                        'slug' => Str::slug($childData['ar'])
                    ]
                ]);
            }
        }
    }
}