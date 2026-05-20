<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\DTOs\Order\CreateOrderDTO;
use App\Exceptions\System\UnprocessableContentException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Order\OrderRepository;
use Illuminate\Support\Facades\DB;

class CreateOrderAction
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {}

    public function execute(CreateOrderDTO $dto): Order
    {
        return DB::transaction(function () use ($dto) {
            $user = User::findOrFail($dto->userId);
            $cart = $user->cart;

            if (!$cart || $cart->items->isEmpty()) {
                throw new UnprocessableContentException(__('error.cart_empty'));
            }

            $shippingAddress = $user->addresses()->findOrFail($dto->shippingAddressId);
            $billingAddress = $user->addresses()->findOrFail($dto->billingAddressId);
            $paymentMethod = $user->paymentMethods()->findOrFail($dto->paymentMethodId);

            $subtotal = $cart->total;
            $shippingAmount = $this->calculateShipping($dto->shippingMethod, $cart);
            $taxAmount = $this->calculateTax($subtotal, $shippingAddress);
            $total = $subtotal + $shippingAmount + $taxAmount;

            $order = $this->orderRepository->create([
                'user_id' => $user->id,
                'shipping_address_id' => $shippingAddress->id,
                'billing_address_id' => $billingAddress->id,
                'payment_method_id' => $paymentMethod->id,
                'subtotal' => $subtotal,
                'shipping_amount' => $shippingAmount,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'shipping_method' => $dto->shippingMethod,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            foreach ($cart->items as $cartItem) {
                $variant = $cartItem->productVariant;
                $itemSubtotal = $variant->price * $cartItem->quantity;

                $order->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'sku' => $variant->sku,
                    'unit_price' => $variant->price,
                    'quantity' => $cartItem->quantity,
                    'subtotal' => $itemSubtotal,
                    'attributes' => $variant->attributes->pluck('attribute_value', 'attribute_name')
                ]);

                $variant->decrement('quantity', $cartItem->quantity);
            }

            $cart->items()->delete();
            $order->markAsPaid();

            return $order;
        });
    }

    private function calculateShipping(string $shippingMethod, Cart $cart): float
    {
        $rates = [
            'standard' => 5.00,
            'express' => 15.00,
            'overnight' => 25.00,
        ];

        return $rates[$shippingMethod] ?? $rates['standard'];
    }

    private function calculateTax(float $subtotal, $address): float
    {
        $taxRates = [
            'CA' => 0.0825,
            'NY' => 0.08875,
            'TX' => 0.0825,
        ];

        $rate = $taxRates[$address->state] ?? 0.08;
        return $subtotal * $rate;
    }
}