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
use Illuminate\Support\Facades\Hash;

class FakeSalesSeeder extends Seeder
{
    use SeedsDemoStore;

    public function run(): void
    {
        $storeId = $this->demoStoreId();
        
        // Control dataset size via environment variable
        $userCount = env('SEED_LARGE_DATASET', false) ? 20 : 5;
        $orderCount = env('SEED_LARGE_DATASET', false) ? 50 : 15;
        
        // ===== CREATE USERS (with pre-hashed password) =====
        $hashedPassword = Hash::make('password');
        User::factory()->count($userCount)->create([
            'password' => $hashedPassword,
        ]);

        // ===== GET ALL VARIANTS =====
        $variants = ProductVariant::query()
            ->whereHas('product', fn ($query) => $query->where('store_id', $storeId))
            ->with('product')
            ->get();

        if ($variants->count() === 0) {
            $this->command->info('❌ No product variants found! Seed products first.');
            return;
        }

        // Prepare batch inserts
        $orders = [];
        $orderItems = [];
        $now = now();

        // ===== PREPARE ORDERS DATA =====
        for ($i = 0; $i < $orderCount; $i++) {
            $user = User::inRandomOrder()->first();
            $orderNumber = strtoupper(Str::random(10));
            
            $subtotal = 0;
            $itemsData = [];
            
            // ===== ADD 1–4 ITEMS PER ORDER =====
            $itemsCount = rand(1, 4);
            
            for ($j = 0; $j < $itemsCount; $j++) {
                $variant = $variants->random();
                $product = $variant->product;
                $qty = rand(1, 5);
                $price = $variant->price;
                $itemSubtotal = $price * $qty;
                $subtotal += $itemSubtotal;
                
                $itemsData[] = [
                    'product_id'        => $product->id,
                    'product_variant_id'=> $variant->id,
                    'subtotal'          => $itemSubtotal,
                    'total'             => $itemSubtotal,
                    'product_name'      => $product->translation(app()->getLocale())->name,
                    'sku'               => $variant->sku,
                    'unit_price'        => $price,
                    'unit_discount_percentage' => 0,
                    'quantity'          => $qty,
                    'attributes'        => $variant->attributes ?? null,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];
            }

            $taxAmount = $subtotal * 0.15;
            $shippingAmount = rand(0, 20);
            $total = $subtotal + $taxAmount + $shippingAmount;

            // Create order immediately to get ID for order items
            $order = Order::create([
                'order_number'        => $orderNumber,
                'user_id'             => $user->id,
                'shipping_address_id' => null,
                'billing_address_id'  => null,
                'payment_method_id'   => null,
                'store_id'            => $storeId,
                'subtotal'            => $subtotal,
                'tax_amount'          => $taxAmount,
                'shipping_amount'     => $shippingAmount,
                'discount_amount'     => 0,
                'total'               => $total,
                'status'              => OrderStatusEnum::DELIVERED,
                'payment_status'      => PaymentStatusEnum::PAID,
            ]);

            // Add order_id to each item
            foreach ($itemsData as $itemData) {
                $itemData['order_id'] = $order->id;
                $orderItems[] = $itemData;
            }
        }

        // Batch insert order items
        if (!empty($orderItems)) {
            // Split into chunks to avoid max packet size issues
            foreach (array_chunk($orderItems, 100) as $chunk) {
                OrderItem::insert($chunk);
            }
        }

        $this->command->info("✅ Fake sales created successfully! ({$orderCount} orders with {$userCount} users)");
    }
}
