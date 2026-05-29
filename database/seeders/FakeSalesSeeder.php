<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsDemoStore;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

class FakeSalesSeeder extends Seeder
{
    use SeedsDemoStore;

    public function run(): void
    {
        $storeId = $this->demoStoreId();
        // ===== CREATE USERS =====
        $userCount = 20;
        User::factory()->count($userCount)->create();

        // ===== GET ALL VARIANTS =====
        $variants = ProductVariant::query()
            ->whereHas('product', fn ($query) => $query->where('store_id', $storeId))
            ->with('product')
            ->get();

        if ($variants->count() === 0) {
            $this->command->info('❌ No product variants found! Seed products first.');
            return;
        }

        // ===== CREATE ORDERS =====
        $orderCount = 50;

        for ($i = 0; $i < $orderCount; $i++) {

            $user = User::inRandomOrder()->first();

            $order = Order::create([
                'order_number'        => strtoupper(Str::random(10)),
                'user_id'             => $user->id,
                'shipping_address_id' => null,
                'billing_address_id'  => null,
                'payment_method_id'   => null,
                'store_id'            => $storeId,

                // totals are calculated later
                'subtotal'            => 0,
                'tax_amount'          => 0,
                'shipping_amount'     => 0,
                'discount_amount'     => 0,
                'total'               => 0,

                'status'              => OrderStatusEnum::DELIVERED,
                'payment_status'      => PaymentStatusEnum::PAID,
            ]);

            $subtotal = 0;

            // ===== ADD 1–4 ITEMS PER ORDER =====
            $itemsCount = rand(1, 4);

            for ($j = 0; $j < $itemsCount; $j++) {

                $variant = $variants->random();
                $product = $variant->product;

                $qty = rand(1, 5);
                $price = $variant->price;
                $subtotal += $price * $qty;
                $total = $price * $qty;
                
                OrderItem::create([
                    'order_id'          => $order->id,
                    'product_id'        => $product->id,
                    'product_variant_id'=> $variant->id,
                    'subtotal'          => $price * $qty,
                    'total'             => $total,
                    'product_name'      => $product->translation(app()->getLocale())->name,
                    'sku'               => $variant->sku,
                    'unit_price'        => $price,
                    'unit_discount_percentage' => 0,
                    'quantity'          => $qty,
                    'attributes'        => $variant->attributes ?? null,
                ]);
            }

            // ===== UPDATE TOTALS =====
            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $subtotal * 0.15,
                'shipping_amount' => rand(0, 20),
                'total' => $subtotal * 1.15,
            ]);
        }

        $this->command->info("✅ Fake sales created successfully!");
    }
}
