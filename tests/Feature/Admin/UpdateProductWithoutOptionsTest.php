<?php

namespace Tests\Feature\Admin;

use App\Actions\Admin\Product\UpdateProductAction;
use App\DTOs\Admin\Product\UpdateProductDTO;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Store;
use App\Repositories\Admin\Product\AdminProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug #13 test: The elseif branch in UpdateProductAction that handles
 * sync_variants: true without an options key is unreachable from the
 * current frontend, but represents legitimate defensive handling for
 * an API consumer that syncs variants without resending option definitions.
 * 
 * This test ensures that capability remains working for mobile apps
 * or third-party integrations.
 */
class UpdateProductWithoutOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_variants_without_options_key_uses_existing_options(): void
    {
        // Create minimal setup using factories
        $user = \App\Models\User::factory()->create();
        $store = \App\Models\Store::factory()->create(['owner_id' => $user->id]);
        
        // Create category and product using raw DB inserts to avoid factory complexity
        $categoryId = \DB::table('categories')->insertGetId([
            'store_id' => $store->id,
            'slug' => 'test-category',
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = \DB::table('products')->insertGetId([
            'store_id' => $store->id,
            'category_id' => $categoryId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create options
        $colorOptionId = \DB::table('product_options')->insertGetId([
            'product_id' => $productId,
            'name' => 'Color',
            'position' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('product_option_values')->insert([
            ['option_id' => $colorOptionId, 'value' => 'Red', 'created_at' => now(), 'updated_at' => now()],
            ['option_id' => $colorOptionId, 'value' => 'Blue', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $sizeOptionId = \DB::table('product_options')->insertGetId([
            'product_id' => $productId,
            'name' => 'Size',
            'position' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('product_option_values')->insert([
            ['option_id' => $sizeOptionId, 'value' => 'Small', 'created_at' => now(), 'updated_at' => now()],
            ['option_id' => $sizeOptionId, 'value' => 'Large', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Now test: Call UpdateProductAction with sync_variants but NO options key
        $product = Product::find($productId);
        
        $dto = new UpdateProductDTO(
            storeId: $store->id,
            productId: $productId,
            options: null,  // KEY: options is null
            syncVariants: true,  // But sync_variants is true
            variants: [
                [
                    'price'    => 10.00,
                    'quantity' => 5,
                    'options'  => [
                        'Color' => 'Red',
                        'Size'  => 'Small',
                    ],
                ],
                [
                    'price'    => 12.00,
                    'quantity' => 3,
                    'options'  => [
                        'Color' => 'Blue',
                        'Size'  => 'Large',
                    ],
                ],
            ],
        );

        $repository = app(AdminProductRepository::class);
        $tagRepository = app(\App\Repositories\Admin\Tag\AdminTagRepository::class);
        $action = new UpdateProductAction($repository, $tagRepository);

        // This should use the existing options to sync the variants
        $updatedProduct = $action->execute($dto);

        // Verify variants were synced correctly using existing options
        $this->assertCount(2, $updatedProduct->variants);

        $redSmall = $updatedProduct->variants()
            ->whereHas('optionValues', function ($q) {
                $q->where('value', 'Red');
            })
            ->whereHas('optionValues', function ($q) {
                $q->where('value', 'Small');
            })
            ->first();

        $this->assertNotNull($redSmall, 'Red/Small variant should exist');
        $this->assertEquals(10.00, $redSmall->price);
        $this->assertEquals(5, $redSmall->quantity);

        $blueLarge = $updatedProduct->variants()
            ->whereHas('optionValues', function ($q) {
                $q->where('value', 'Blue');
            })
            ->whereHas('optionValues', function ($q) {
                $q->where('value', 'Large');
            })
            ->first();

        $this->assertNotNull($blueLarge, 'Blue/Large variant should exist');
        $this->assertEquals(12.00, $blueLarge->price);
        $this->assertEquals(3, $blueLarge->quantity);
    }
}

