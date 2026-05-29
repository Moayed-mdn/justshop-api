<?php
// database/seeders/ProductSeeder.php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoStore;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Image;
use App\Models\ProductVariant;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\Store;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    use SeedsDemoStore;

    /**
     * Product name → Brand name mapping.
     */
    private array $productBrands = [
        // Phones
        'iPhone 14'              => 'Apple',
        'Samsung Galaxy S23'     => 'Samsung',
        'Xiaomi Redmi Note 12'   => 'Xiaomi',
        // Laptops
        'MacBook Pro'            => 'Apple',
        'Dell XPS 13'            => 'Dell',
        'HP Spectre x360'        => 'HP',
        'Lenovo ThinkPad X1'     => 'Lenovo',
        'ASUS ROG Zephyrus'      => 'ASUS',
        // Shoes
        'Running Sneakers'       => 'Nike',
        // Furniture
        'Modern Sofa 3-Seater'   => 'IKEA',
        'Bookshelf 5-Tier'       => 'IKEA',
    ];

    /**
     * Product name → Tags mapping.
     */
    private array $productTags = [
        'iPhone 14'              => ['Electronics', 'Mobile', 'New Arrival'],
        'Samsung Galaxy S23'     => ['Electronics', 'Mobile', 'Bestseller'],
        'MacBook Pro'            => ['Electronics', 'Laptop', 'Pro'],
        'Classic Cotton T-Shirt' => ['Fashion', 'T-Shirt', 'Sale'],
        'Slim Fit Jeans'         => ['Fashion', 'Jeans', 'Bestseller'],
        'Flory Summer Dress'     => ['Fashion', 'Dress', 'New Arrival'],
        'Running Sneakers'       => ['Shoes', 'Sport', 'Bestseller'],
        'Modern Sofa 3-Seater'   => ['Furniture', 'Home', 'Sale'],
    ];

    /**
     * Product name → Tags mapping (Arabic).
     */
    private array $productTagsArabic = [
        'Electronics' => 'إلكترونيات',
        'Mobile' => 'موبايل',
        'New Arrival' => 'وصل حديثا',
        'Bestseller' => 'الأكثر مبيعا',
        'Laptop' => 'لابتوب',
        'Pro' => 'برو',
        'Fashion' => 'موضة',
        'T-Shirt' => 'تي شيرت',
        'Sale' => 'تخفيضات',
        'Jeans' => 'جينز',
        'Dress' => 'فستان',
        'Shoes' => 'أحذية',
        'Sport' => 'رياضة',
        'Furniture' => 'أثاث',
        'Home' => 'منزل',
    ];

    /**
     * Product configuration with options and their values
     * This demonstrates the "Option + Values → Generate Combinations" pattern
     */
    private array $productsConfig = [
        'Men Clothing' => [
            'Classic Cotton T-Shirt' => [
                'options' => [
                    'Color' => ['Red', 'Blue', 'Black', 'White'],
                    'Size' => ['S', 'M', 'L', 'XL'],
                ],
                'base_price' => 29.99,
                'description' => 'Premium cotton t-shirt for everyday comfort',
            ],
            'Slim Fit Jeans' => [
                'options' => [
                    'Color' => ['Blue', 'Black', 'Light Blue'],
                    'Size' => ['30', '32', '34', '36'],
                    'Length' => ['Regular', 'Long'],
                ],
                'base_price' => 59.99,
                'description' => 'Modern slim fit jeans with stretch comfort',
            ],
        ],
        'Women Clothing' => [
            'Flory Summer Dress' => [
                'options' => [
                    'Color' => ['Pink', 'Blue', 'White', 'Yellow'],
                    'Size' => ['S', 'M', 'L', 'XL'],
                ],
                'base_price' => 49.99,
                'description' => 'Floral summer dress perfect for warm days',
            ],
            'Yoga Pants' => [
                'options' => [
                    'Color' => ['Black', 'Dark Grey', 'Burgundy', 'Navy'],
                    'Size' => ['S', 'M', 'L', 'XL'],
                    'Length' => ['Capri', 'Regular', 'Long'],
                ],
                'base_price' => 39.99,
                'description' => 'High-waist yoga pants with moisture-wicking fabric',
            ],
        ],
        'Shoes' => [
            'Running Sneakers' => [
                'options' => [
                    'Color' => ['White', 'Black', 'Blue', 'Red'],
                    'Size' => ['38', '39', '40', '41', '42'],
                ],
                'base_price' => 89.99,
                'description' => 'Lightweight running sneakers with cushioned sole',
            ],
            'Casual Loafers' => [
                'options' => [
                    'Color' => ['Brown', 'Black', 'Navy', 'Tan'],
                    'Size' => ['38', '39', '40', '41', '42', '43'],
                ],
                'base_price' => 69.99,
                'description' => 'Comfortable casual loafers for everyday wear',
            ],
        ],
        'Phones' => [
            'iPhone 14' => [
                'options' => [
                    'Storage' => ['128GB', '256GB', '512GB'],
                    'Color' => ['Black', 'Blue', 'Purple', 'Red'],
                ],
                'base_price' => 799.99,
                'description' => 'Apple iPhone 14 with advanced camera system',
            ],
            'Samsung Galaxy S23' => [
                'options' => [
                    'Storage' => ['128GB', '256GB', '512GB'],
                    'Color' => ['Black', 'Green', 'Lavender', 'White'],
                ],
                'base_price' => 699.99,
                'description' => 'Samsung Galaxy S23 with dynamic AMOLED display',
            ],
        ],
        'Laptops' => [
            'MacBook Pro' => [
                'options' => [
                    'Storage' => ['256GB', '512GB', '1TB', '2TB'],
                    'RAM' => ['8GB', '16GB', '32GB', '64GB'],
                    'Color' => ['Space Gray', 'Silver'],
                ],
                'base_price' => 1299.99,
                'description' => 'MacBook Pro with M2 chip and Retina display',
            ],
        ],
        'Furniture' => [
            'Modern Sofa 3-Seater' => [
                'options' => [
                    'Color' => ['Grey', 'Beige', 'Navy Blue', 'Dark Green'],
                    'Material' => ['Fabric', 'Leather', 'Velvet'],
                ],
                'base_price' => 499.99,
                'description' => 'Modern 3-seater sofa with comfortable cushions',
            ],
        ],
        'Accessories' => [
            'Wireless Earbuds' => [
                'options' => [
                    'Color' => ['White', 'Black', 'Blue'],
                    'Battery' => ['6h', '8h', '12h'],
                ],
                'base_price' => 79.99,
                'description' => 'High-quality wireless earbuds with noise cancellation',
            ],
        ],
    ];

    public function run()
    {
        $storeId = $this->demoStoreId();
        DB::beginTransaction();

        foreach ($this->productsConfig as $categoryName => $products) {
            $categoryName = trim($categoryName);
            $category = Category::query()
                ->where('store_id', $storeId)
                ->whereHas('translations', function ($query) use ($categoryName) {
                    $query->where('name', $categoryName)->where('locale', 'en');
                })
                ->first();

            if (!$category) {
                $this->command->error("❌ Category '{$categoryName}' not found!");
                continue;
            }

            foreach ($products as $productName => $config) {
                $this->command->info("📦 Processing: $productName");

                // ── Resolve brand ──────────────────────────────
                $brandId = null;
                if (isset($this->productBrands[$productName])) {
                    $brand =$brandName = $this->productBrands[$productName];
                    $brand = Brand::updateOrCreate(
                        [
                            'slug' => Str::slug($brandName),
                            'store_id' => $storeId,
                        ],
                        [
                            'name' => $brandName,
                            'is_active' => true,
                        ],
                    );
                    $brandId = $brand->id;
                }

                // ── Create product ─────────────────────────────
                $productSlug = Str::slug($productName);
                $product = Product::query()
                    ->where('store_id', $storeId)
                    ->whereHas('translations', function ($query) use ($productSlug) {
                        $query->where('slug', $productSlug)->where('locale', 'en');
                    })
                    ->first();

                if (!$product) {
                    $product = Product::create([
                        'category_id' => $category->id,
                        'brand_id'    => $brandId,
                        'store_id'    => $storeId,
                        'is_active'   => true,
                    ]);

                    // ── Product translations ───────────────────────
                    $product->translations()->create([
                        'locale'      => 'en',
                        'name'        => $productName,
                        'description' => $config['description'] ?? "$productName — premium quality, fast shipping.",
                        'slug'        => $productSlug,
                    ]);

                    $product->translations()->create([
                        'locale'      => 'ar',
                        'name'        => $this->getArabicProductName($productName),
                        'description' => $this->getArabicDescription($productName),
                        'slug'        => Str::slug($this->getArabicProductName($productName)),
                    ]);
                }

                // ── STEP 1: Create Options and their Values ────
                $createdOptions = [];
                $allCombinations = [];

                foreach ($config['options'] as $optionName => $optionValues) {
                    // Create ProductOption
                    $option = ProductOption::create([
                        'product_id' => $product->id,
                        'name'       => $optionName,
                        'position'   => count($createdOptions),
                    ]);

                    // Create ProductOptionValues
                    $optionValueModels = [];
                    foreach ($optionValues as $position => $value) {
                        $optionValue = ProductOptionValue::create([
                            'option_id' => $option->id,
                            'value'     => $value,
                        ]);
                        $optionValueModels[] = $optionValue;
                    }

                    $createdOptions[$optionName] = [
                        'option' => $option,
                        'values' => $optionValueModels,
                    ];
                }

                // ── STEP 2: Generate All Combinations (Cartesian Product) ──
                $combinations = $this->generateCombinations($createdOptions);
                $this->command->info("   ✓ Generated " . count($combinations) . " variant combinations");

                // ── STEP 3: Create Product Variants from Combinations ──
                $firstVariant = null;
                $variantPrice = $config['base_price'] ?? rand(50, 999);

                foreach ($combinations as $index => $combination) {
                    // Generate SKU from combination values
                    $skuParts = [$productName];
                    foreach ($combination['values'] as $value) {
                        $skuParts[] = $value->value;
                    }
                    $sku = strtoupper(Str::slug(implode('-', $skuParts))) . '-' . rand(1000, 9999);

                    // Adjust price slightly for different combinations (optional)
                    $priceAdjustment = $this->calculatePriceAdjustment($combination['values']);
                    $finalPrice = max(0.99, $variantPrice + $priceAdjustment);

                    // Create variant
                    $variant = ProductVariant::create([
                        'product_id'       => $product->id,
                        'sku'              => $sku,
                        'price'            => round($finalPrice, 2),
                        'quantity'         => rand(5, 100),
                        'batch_number'     => 'BATCH-' . date('Ymd') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                        'manufacture_date' => now()->subMonths(rand(1, 6)),
                        'expiry_date'      => now()->addMonths(rand(12, 36)),
                        'is_active'        => true,
                    ]);

                    if (!$firstVariant) {
                        $firstVariant = $variant;
                    }

                    // Add default image
                    Image::create([
                        'imageable_id'   => $variant->id,
                        'imageable_type' => ProductVariant::class,
                        'image_url'      => '/storage/variants/default.png',
                        'is_primary'     => true,
                    ]);

                    // Link variant to its option values
                    foreach ($combination['mapping'] as $optionName => $value) {
                        DB::table('variant_option_values')->insert([
                            'variant_id'       => $variant->id,
                            'option_value_id'  => $value->id,
                            'option_id'        => $createdOptions[$optionName]['option']->id,
                        ]);
                    }
                }

                // ── Set default variant ────────────────────────
                if ($firstVariant) {
                    $product->update(['product_variant_id' => $firstVariant->id]);
                }

                // ── Attach tags ────────────────────────────────
                if (isset($this->productTags[$productName])) {
                    $tagIds = [];
                    foreach ($this->productTags[$productName] as $tagName) {
                        $tag = Tag::where('store_id', $product->store_id)
                            ->whereHas('translations', function ($query) use ($tagName) {
                                $query->where('name', $tagName)->where('locale', 'en');
                            })->first();

                        if (!$tag) {
                            $tag = Tag::create([
                                'store_id' => $product->store_id,
                                'is_active' => true,
                            ]);

                            $tag->translations()->create([
                                'locale' => 'en',
                                'name' => $tagName,
                                'slug' => Str::slug($tagName),
                            ]);

                            if (isset($this->productTagsArabic[$tagName])) {
                                $arabicTagName = $this->productTagsArabic[$tagName];
                                $tag->translations()->create([
                                    'locale' => 'ar',
                                    'name' => $arabicTagName,
                                    'slug' => Str::slug($arabicTagName),
                                ]);
                            }
                        }

                        $tagIds[] = $tag->id;
                    }
                    $product->tags()->sync($tagIds);
                    $this->command->info("   ✓ Attached " . count($tagIds) . " tags");
                }

                $this->command->info("   ✅ Created " . count($combinations) . " variants for $productName");
            }
        }

        DB::commit();
        $this->command->info("\n✅ Products seeded successfully with Option + Value → Generate Combinations pattern!");
        $this->command->info("   Each product has options and all possible combinations were generated automatically.");
    }

    /**
     * Generate all possible combinations of option values (Cartesian product)
     * 
     * @param array $createdOptions Format: ['OptionName' => ['option' => $option, 'values' => [$value1, $value2]]]
     * @return array List of combinations, each containing 'mapping' and 'values'
     * 
     * Example Output:
     * [
     *     [
     *         'mapping' => ['Color' => $redValue, 'Size' => $smallValue],
     *         'values' => [$redValue, $smallValue]
     *     ],
     *     ...
     * ]
     */
    private function generateCombinations(array $createdOptions): array
    {
        $combinations = [];
        $optionNames = array_keys($createdOptions);
        $optionValuesList = array_map(function ($option) use ($createdOptions) {
            return $createdOptions[$option]['values'];
        }, $optionNames);

        // Generate Cartesian product
        $combinationsData = $this->cartesianProduct($optionValuesList);

        foreach ($combinationsData as $combinationValues) {
            $mapping = [];
            foreach ($optionNames as $index => $optionName) {
                $mapping[$optionName] = $combinationValues[$index];
            }
            $combinations[] = [
                'mapping' => $mapping,
                'values' => $combinationValues,
            ];
        }

        return $combinations;
    }

    /**
     * Calculate Cartesian product of multiple arrays
     * 
     * @param array $arrays List of arrays to combine
     * @return array All possible combinations
     */
    private function cartesianProduct(array $arrays): array
    {
        $result = [[]];
        foreach ($arrays as $array) {
            $temp = [];
            foreach ($result as $combination) {
                foreach ($array as $item) {
                    $temp[] = array_merge($combination, [$item]);
                }
            }
            $result = $temp;
        }
        return $result;
    }

    /**
     * Calculate price adjustment based on option values
     * Example: Higher storage or RAM increases price
     */
    private function calculatePriceAdjustment(array $optionValues): float
    {
        $adjustment = 0;

        foreach ($optionValues as $value) {
            $valueString = $value->value;
            
            // Storage adjustments
            if (str_contains($valueString, 'GB') || str_contains($valueString, 'TB')) {
                if ($valueString === '256GB') $adjustment += 50;
                elseif ($valueString === '512GB') $adjustment += 100;
                elseif ($valueString === '1TB') $adjustment += 200;
                elseif ($valueString === '2TB') $adjustment += 400;
            }
            
            // RAM adjustments
            if (str_contains($valueString, 'GB') && !str_contains($valueString, 'Storage')) {
                if ($valueString === '16GB') $adjustment += 80;
                elseif ($valueString === '32GB') $adjustment += 160;
                elseif ($valueString === '64GB') $adjustment += 320;
            }
            
            // Material adjustments
            if ($valueString === 'Leather') $adjustment += 100;
            if ($valueString === 'Velvet') $adjustment += 80;
            
            // Size doesn't affect price, length might
            if ($valueString === 'Long') $adjustment += 10;
        }

        return $adjustment;
    }

    /**
     * Display combinations in a readable format (for debugging)
     */
    private function displayCombinations(array $combinations): void
    {
        foreach ($combinations as $index => $combination) {
            $values = [];
            foreach ($combination['mapping'] as $optionName => $value) {
                $values[] = "$optionName: {$value->value}";
            }
            $this->command->info("      Combo " . ($index + 1) . ": " . implode(', ', $values));
        }
    }

    private function getArabicProductName($englishName)
    {
        $translations = [
            'iPhone 14' => 'آيفون 14',
            'Samsung Galaxy S23' => 'سامسونج جالاكسي S23',
            'Xiaomi Redmi Note 12' => 'شاومي ريدمي نوت 12',
            'Wireless Earbuds' => 'سماعات لاسلكية',
            'Smartwatches' => 'ساعات ذكية',
            'Laptop Bags' => 'حقائب لابتوب',
            'External Monitors' => 'شاشات خارجية',
            'Mechanical Keyboards' => 'لوحات مفاتيح ميكانيكية',
            'Wireless Mice' => 'فئران لاسلكية',
            'USB-C Hubs' => 'موزعات USB-C',
            'Portable SSDs' => 'أقراص SSD محمولة',
            'MacBook Pro' => 'ماك بوك برو',
            'Dell XPS 13' => 'ديل XPS 13',
            'HP Spectre x360' => 'إتش بي سبيكتر x360',
            'Lenovo ThinkPad X1' => 'لينوفو ثينك باد X1',
            'ASUS ROG Zephyrus' => 'آسوس ROG زيفيروس',
            'Classic Cotton T-Shirt' => 'تي شيرت قطني كلاسيكي',
            'Slim Fit Jeans' => 'جينز بقصة ضيقة',
            'Winter Hoodie' => 'هودي شتوي',
            'Flory Summer Dress' => 'فستان صيفي زهري',
            'High-Waist Jeans' => 'جينز عالي الخصر',
            'Oversized Sweater' => 'كنزة صوف كبيرة',
            'Classic Blazer' => 'بليزر كلاسيكي',
            'Yoga Pants' => 'بنطلون يوجا',
            'Running Sneakers' => 'حذاء جري رياضي',
            'Leather Boots' => 'جزمة جلدية',
            'High Heels' => 'كعب عالي',
            'Casual Loafers' => 'لوففرز كاجوال',
            'Sports Sandals' => 'صنادل رياضية',
            'Air Fryer 4L' => 'مقلاة هوائية 4 لتر',
            'Electric Kettle 1.7L' => 'غلاية كهربائية 1.7 لتر',
            'Blender 1200W' => 'خلاط 1200 واط',
            'Modern Sofa 3-Seater' => 'كنبة حديثة 3 مقاعد',
            'Wooden Dining Table' => 'طاولة طعام خشبية',
            'Office Ergonomic Chair' => 'كرسي مكتب مريح',
            'Queen Size Bed Frame' => 'هيكل سرير مقاس كوين',
            'Bookshelf 5-Tier' => 'رف كتب 5 طبقات',
            'Ceramic Table Vase' => 'مزهرية سيراميك للطاولة',
            'Wall Clock Modern' => 'ساعة حائط عصرية',
            'LED Floor Lamp' => 'مصباح أرضي LED',
            'Abstract Wall Art' => 'لوحة حائط تجريدية',
            'Decorative Throw Pillows' => 'وسائد زخرفية',
            'Indoor Plant Set' => 'مجموعة نباتات داخلية',
            'Adjustable Dumbbells' => 'دمبلز قابل للتعديل',
            'Yoga Mat Pro' => 'سجادة يوجا احترافية',
            'Pull-Up Bar' => 'بار عقلة',
        ];

        return $translations[$englishName] ?? $englishName;
    }

    private function getArabicDescription($productName)
    {
        $descriptions = [
            'Classic Cotton T-Shirt' => 'تي شيرت قطني فاخر مريح للاستخدام اليومي',
            'Slim Fit Jeans' => 'جينز عصري بقصة ضيقة مع قماش مطاطي مريح',
            'Flory Summer Dress' => 'فستان صيفي زهري مثالي للأيام الدافئة',
            'Yoga Pants' => 'بنطلون يوجا عالي الخصر بقماش يمتص الرطوبة',
            'Running Sneakers' => 'حذاء جري رياضي خفيف الوزن بنعل مبطن',
            'Casual Loafers' => 'لوففرز كاجوال مريحة للاستخدام اليومي',
            'iPhone 14' => 'آيفون 14 من أبل مع نظام كاميرا متقدم',
            'Samsung Galaxy S23' => 'سامسونج جالاكسي S23 بشاشة AMOLED ديناميكية',
            'MacBook Pro' => 'ماك بوك برو مع شريحة M2 وشاشة ريتينا',
            'Modern Sofa 3-Seater' => 'كنبة عصرية بثلاثة مقاعد مع وسائد مريحة',
            'Wireless Earbuds' => 'سماعات لاسلكية عالية الجودة مع إلغاء الضوضاء',
        ];

        return $descriptions[$productName] ?? 'وصف ' . $this->getArabicProductName($productName) . ' — جودة ممتازة، شحن سريع.';
    }
}