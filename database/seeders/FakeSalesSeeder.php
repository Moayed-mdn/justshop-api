<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

class FakeSalesSeeder extends Seeder
{
    public function run(): void
    {
        // ===== CREATE USERS =====
        $userCount = 20;
        User::factory()->count($userCount)->create();

        // ===== GET ALL VARIANTS =====
        $variants = ProductVariant::with('product')->get();

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

                // totals are calculated later
                'subtotal'            => 0,
                'tax_amount'          => 0,
                'shipping_amount'     => 0,
                'discount_amount'     => 0,
                'total'               => 0,

                'status'              => 'delivered',
                'payment_status'      => 'paid',
            ]);

            $subtotal = 0;

            // ===== ADD 1–4 ITEMS PER ORDER =====
            $itemsCount = rand(1, 4);

            for ($j = 0; $j < $itemsCount; $j++) {

                $variant = $variants->random();
                $product = $variant->product;

                $qty = rand(1, 5);
                $price = $variant->price;
                $itemSubtotal = $price * $qty;
                $subtotal += $itemSubtotal;

                OrderItem::create([
                    'order_id'          => $order->id,
                    'product_id'        => $product->id,
                    'product_variant_id'=> $variant->id,

                    'product_name'      => $product->translation(app()->getLocale())->name,
                    'sku'               => $variant->sku,
                    'unit_price'        => $price,
                    'unit_discount_percentage' => 0,
                    'quantity'          => $qty,
                    'subtotal'          => $itemSubtotal,
                    'total'             => $itemSubtotal, // total is same as subtotal because no taxes or discounts are applied
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