<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = $this->faker->randomFloat(2, 10, 200);
        $quantity = $this->faker->numberBetween(1, 3);
        $subtotal = $unitPrice * $quantity;

        // Product and variant are created together (not via independent factory
        // calls) so product_id and product_variant_id always reference the same
        // product, matching the real FK relationship enforced by the migration.
        $product = Product::factory()
            ->for(Category::factory())
            ->for(Brand::factory())
            ->create();
        $variant = ProductVariant::factory()->for($product)->create();

        return [
            'order_id' => Order::factory(),
            'product_variant_id' => $variant->id,
            'product_id' => $product->id,
            'product_name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-???-###')),
            'unit_price' => $unitPrice,
            'unit_discount_percentage' => 0,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'attributes' => [],
        ];
    }
}
