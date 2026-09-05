<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 20, 500);
        $shipping = $this->faker->randomFloat(2, 0, 20);
        $tax = 0;

        return [
            'store_id' => Store::factory(),
            'user_id' => User::factory(),
            'order_number' => 'ORD-' . strtoupper($this->faker->unique()->bothify('??####??')),
            'shipping_address_id' => null,
            'billing_address_id' => null,
            'payment_method_id' => null,
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'shipping_amount' => $shipping,
            'discount_amount' => 0,
            'total' => $subtotal + $shipping + $tax,
            'currency' => 'USD',
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::PENDING,
            'payment_intent_id' => null,
            'shipping_method' => 'standard',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatusEnum::PROCESSING,
            'payment_status' => PaymentStatusEnum::PAID,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatusEnum::CANCELLED,
        ]);
    }

    public function shipped(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatusEnum::SHIPPED,
            'payment_status' => PaymentStatusEnum::PAID,
        ]);
    }
}
