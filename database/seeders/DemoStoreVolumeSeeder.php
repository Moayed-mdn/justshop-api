<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\Concerns\SeedsDemoStore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoStoreVolumeSeeder extends Seeder
{
    use SeedsDemoStore;

    /**
     * Adds additional catalog volume for the demo tenant storefront.
     *
     * @var array<string, array<int, array{en: string, ar: string, price: float}>>
     */
    private array $catalogExtensions = [
        'Phones' => [
            ['en' => 'Pixel 8 Pro', 'ar' => 'بيكسل 8 برو', 'price' => 899.00],
            ['en' => 'OnePlus 12', 'ar' => 'ون بلس 12', 'price' => 749.00],
            ['en' => 'Budget Android A55', 'ar' => 'أندرويد اقتصادي A55', 'price' => 249.00],
        ],
        'Laptops' => [
            ['en' => 'Surface Laptop 6', 'ar' => 'سيرفس لابتوب 6', 'price' => 1199.00],
            ['en' => 'Acer Swift Go', 'ar' => 'ايسر سويفت جو', 'price' => 899.00],
        ],
        'Accessories' => [
            ['en' => 'USB-C Hub Pro', 'ar' => 'موزع USB-C برو', 'price' => 59.99],
            ['en' => 'Mechanical Keyboard', 'ar' => 'لوحة مفاتيح ميكانيكية', 'price' => 129.99],
            ['en' => 'Noise Cancelling Headphones', 'ar' => 'سماعات بعزل الضوضاء', 'price' => 199.99],
        ],
        'Men Clothing' => [
            ['en' => 'Oxford Button-Down Shirt', 'ar' => 'قميص اوكسفورد', 'price' => 44.99],
            ['en' => 'Merino Wool Sweater', 'ar' => 'سترة صوف ميرينو', 'price' => 79.99],
        ],
        'Women Clothing' => [
            ['en' => 'Silk Evening Blouse', 'ar' => 'بلوزة حرير مسائية', 'price' => 69.99],
            ['en' => 'High-Rise Wide Leg Pants', 'ar' => 'بنطال واسع عالي الخصر', 'price' => 54.99],
        ],
        'Shoes' => [
            ['en' => 'Leather Chelsea Boots', 'ar' => 'بوت جلد تشيلسي', 'price' => 119.99],
            ['en' => 'Trail Running Shoes', 'ar' => 'حذاء جري للمسارات', 'price' => 99.99],
        ],
        'Appliances' => [
            ['en' => 'Air Fryer XL', 'ar' => 'قلاية هوائية كبيرة', 'price' => 149.99],
            ['en' => 'Espresso Machine', 'ar' => 'ماكينة اسبريسو', 'price' => 299.99],
        ],
        'Furniture' => [
            ['en' => 'Standing Desk', 'ar' => 'مكتب قابل للوقوف', 'price' => 399.99],
            ['en' => 'Ergonomic Office Chair', 'ar' => 'كرسي مكتب مريح', 'price' => 249.99],
        ],
        'Skincare' => [
            ['en' => 'Vitamin C Serum', 'ar' => 'سيروم فيتامين سي', 'price' => 34.99],
            ['en' => 'Hydrating Night Cream', 'ar' => 'كريم ليلي مرطب', 'price' => 29.99],
        ],
        'Sportswear' => [
            ['en' => 'Compression Training Top', 'ar' => 'قميص تدريب ضاغط', 'price' => 39.99],
            ['en' => 'Performance Running Shorts', 'ar' => 'شورت جري رياضي', 'price' => 34.99],
        ],
    ];

    public function run(): void
    {
        $storeId = $this->demoStoreId();
        $created = 0;

        foreach ($this->catalogExtensions as $categoryName => $products) {
            $category = Category::query()
                ->where('store_id', $storeId)
                ->whereHas('translations', fn ($query) => $query->where('locale', 'en')->where('name', $categoryName))
                ->first();

            if (!$category) {
                $this->command?->warn("DemoStoreVolumeSeeder skipped missing category: {$categoryName}");
                continue;
            }

            foreach ($products as $productData) {
                $slug = Str::slug($productData['en']);

                $exists = Product::query()
                    ->where('store_id', $storeId)
                    ->whereHas('translations', fn ($query) => $query->where('locale', 'en')->where('slug', $slug))
                    ->exists();

                if ($exists) {
                    continue;
                }

                $product = Product::query()->create([
                    'store_id' => $storeId,
                    'category_id' => $category->id,
                    'brand_id' => null,
                    'is_active' => true,
                    'is_featured' => (bool) random_int(0, 1),
                    'sort_order' => $created,
                ]);

                $product->translations()->createMany([
                    [
                        'locale' => 'en',
                        'name' => $productData['en'],
                        'description' => $productData['en'] . ' — premium demo catalog item for local storefront testing.',
                        'slug' => $slug,
                    ],
                    [
                        'locale' => 'ar',
                        'name' => $productData['ar'],
                        'description' => $productData['ar'] . ' — منتج تجريبي لاختبار واجهة المتجر محليًا.',
                        'slug' => Str::slug($productData['ar']),
                    ],
                ]);

                $variant = ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'sku' => strtoupper('DEMO-' . $slug . '-' . $product->id),
                    'price' => $productData['price'],
                    'quantity' => random_int(8, 120),
                    'track_inventory' => true,
                    'is_active' => true,
                ]);

                $product->update(['product_variant_id' => $variant->id]);
                $created++;
            }
        }

        $this->command?->info("DemoStoreVolumeSeeder created {$created} additional products for merchant-store.");
    }
}
