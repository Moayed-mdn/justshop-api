<?php
// app/Services/CheckoutService.php

namespace App\Services;

use App\Exceptions\Order\OutOfStockException;
use App\Exceptions\Payment\StripeServiceException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class CheckoutService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Checkout Session for a logged-in user.
     * Reads cart from the database.
     * @throws Exception
     */
    public function createSessionForUser(User $user): array
    {
        $cart = $user->cart;

        if (!$cart || $cart->items->isEmpty()) {
            throw new \App\Exceptions\BaseApiException(
                message: __('cart.empty'),
                statusCode: 422,
                errorCode: \App\Enums\ErrorCode::SYS_001->value
            );
        }

        $cart->load([
            'items.productVariant.product.translations',
            'items.productVariant.attributeValues.translations',
            'items.productVariant.attributeValues.attribute.translations',
            'items.productVariant.images',
        ]);

        // Validate stock and build order
        $validatedItems = $this->validateAndPrepareItems(
            $cart->items->map(fn($item) => [
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
            ])->toArray()
        );

        return $this->createCheckoutSession($validatedItems, $user);
    }

    /**
     * Create a Stripe Checkout Session for a guest user.
     * Cart items are passed from the frontend (localStorage).
     * @throws Exception
     */
    public function createSessionForGuest(array $items, ?string $email = null): array
    {
        if (empty($items)) {
            throw new \App\Exceptions\BaseApiException(
                message: __('cart.empty'),
                statusCode: 422,
                errorCode: \App\Enums\ErrorCode::SYS_001->value
            );
        }

        $validatedItems = $this->validateAndPrepareItems($items);

        return $this->createCheckoutSession($validatedItems, null, $email);
    }

    /**
     * Validate stock, prices, and prepare item data for the order.
     */
    private function validateAndPrepareItems(array $rawItems): array
    {
        $locale = app()->getLocale();
        $prepared = [];

        foreach ($rawItems as $item) {
            $variant = ProductVariant::with([
                'product.translations',
                'attributeValues.translations',
                'attributeValues.attribute.translations',
                'images',
            ])->findOrFail($item['product_variant_id']);

            // Check active
            if (!$variant->is_active) {
                throw new OutOfStockException(__('cart.variant_no_longer_available', ['id' => $variant->id]));
            }

            // Check stock
            if ($variant->quantity < $item['quantity']) {
                $productName = $variant->product->translations
                    ->where('locale', $locale)->first()?->name
                    ?? $variant->product->translations->first()?->name
                    ?? 'Product';

                throw new OutOfStockException(__('cart.not_enough_stock_for_product', [
                    'product' => $productName,
                    'available' => $variant->quantity
                ]));
            }

            // Build translated product name
            $translation = $variant->product->translations
                ->where('locale', $locale)->first()
                ?? $variant->product->translations->first();

            $productName = $translation?->name ?? 'Product';

            // Build attribute string: "Color: Red, Size: 500g"
            $attrParts = $variant->attributeValues->map(function ($attrValue) use ($locale) {
                $name = $attrValue->attribute->translations
                    ->where('locale', $locale)->first()?->name
                    ?? $attrValue->attribute->code;

                $value = $attrValue->translations
                    ->where('locale', $locale)->first()?->label
                    ?? $attrValue->code;

                return "{$name}: {$value}";
            })->toArray();

            $attrString = implode(', ', $attrParts);

            // Get image URL
            $primaryImage = $variant->images->where('is_primary', true)->first()
                ?? $variant->images->first();
            $imageUrl = $primaryImage ? $primaryImage->full_url : null;

            $prepared[] = [
                'variant'        => $variant,
                'product_id'     => $variant->product_id,
                'product_name'   => $productName,
                'description'    => $attrString ?: null,
                'sku'            => $variant->sku,
                'unit_price'     => $variant->price,
                'quantity'       => $item['quantity'],
                'image_url'      => $imageUrl,
                'attributes'     => $variant->attributeValues->map(function ($attrValue) use ($locale) {
                    $name = $attrValue->attribute->translations
                        ->where('locale', $locale)->first()?->name
                        ?? $attrValue->attribute->code;

                    $value = $attrValue->translations
                        ->where('locale', $locale)->first()?->label
                        ?? $attrValue->code;

                    return ['name' => $name, 'value' => $value];
                })->toArray(),
            ];
        }

        return $prepared;
    }

    /**
     * Create the pending Order, then the Stripe Checkout Session.
     */
    private function createCheckoutSession(array $validatedItems, ?User $user, ?string $guestEmail = null): array
    {
        return DB::transaction(function () use ($validatedItems, $user, $guestEmail) {

            // ── 1. Calculate totals ────────────────────────────
            $subtotal = collect($validatedItems)->sum(fn($i) => $i['unit_price'] * $i['quantity']);
            $shippingAmount = 0; // Free shipping for now
            $total = $subtotal + $shippingAmount;

            // ── 2. Create pending Order ────────────────────────
            $order = Order::create([
                'user_id'          => $user?->id,
                'guest_email'      => $guestEmail,
                'subtotal'         => $subtotal,
                'tax_amount'       => 0,
                'shipping_amount'  => $shippingAmount,
                'discount_amount'  => 0,
                'total'            => $total,
                'currency'         => 'usd',
                'status'           => 'pending',
                'payment_status'   => 'pending',
                'shipping_method'  => 'free',
            ]);

            // ── 3. Create OrderItems ───────────────────────────
            foreach ($validatedItems as $item) {
                OrderItem::create([
                    'order_id'                => $order->id,
                    'product_id'              => $item['product_id'],
                    'product_variant_id'      => $item['variant']->id,
                    'product_name'            => $item['product_name'],
                    'sku'                     => $item['sku'],
                    'unit_price'              => $item['unit_price'],
                    'unit_discount_percentage' => 0,
                    'quantity'                => $item['quantity'],
                    'subtotal'                => $item['unit_price'] * $item['quantity'],
                    'total'                   => $item['unit_price'] * $item['quantity'],
                    'attributes'              => $item['attributes'],
                ]);
            }

            // ── 4. Build Stripe line_items ─────────────────────
            $lineItems = collect($validatedItems)->map(function ($item) {
                $productData = [
                    'name' => $item['product_name'],
                ];

                if ($item['description']) {
                    $productData['description'] = $item['description'];
                }

                if ($item['image_url']) {
                    $productData['images'] = [$item['image_url']];
                }

                return [
                    'price_data' => [
                        'currency'     => 'usd',
                        'product_data' => $productData,
                        'unit_amount'  => (int) round($item['unit_price'] * 100), // Stripe uses cents
                    ],
                    'quantity' => $item['quantity'],
                ];
            })->toArray();

            // ── 5. Build Stripe session params ─────────────────
            $sessionParams = [
                'mode'        => 'payment',
                'line_items'  => $lineItems,
                'success_url' => config('app.frontend_url') . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => config('app.frontend_url') . '/checkout/cancel',

                'shipping_address_collection' => [
                    'allowed_countries' => ['US', 'CA', 'GB', 'DE', 'FR', 'SA', 'AE', 'EG', 'JO'],
                ],

                'shipping_options' => [
                    [
                        'shipping_rate_data' => [
                            'type'         => 'fixed_amount',
                            'fixed_amount' => ['amount' => 0, 'currency' => 'usd'],
                            'display_name' => 'Free Shipping',
                            'delivery_estimate' => [
                                'minimum' => ['unit' => 'business_day', 'value' => 5],
                                'maximum' => ['unit' => 'business_day', 'value' => 7],
                            ],
                        ],
                    ],
                ],

                'metadata' => [
                    'order_id' => $order->id,
                    'source'   => 'ecommerce_app',
                ],
            ];

            // ── 6. Logged-in user: attach Stripe customer ──────
            if ($user) {
                $sessionParams['customer_email'] = $user->email;
            }

            // ── 7. Create Stripe Checkout Session ──────────────
            try {
                $session = Session::create($sessionParams);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                throw new StripeServiceException(__('payment.stripe_checkout_failed'));
            }

            // ── 8. Store session ID on order ───────────────────
            $order->update([
                'stripe_checkout_session_id' => $session->id,
            ]);

            return [
                'session_id'  => $session->id,
                'session_url' => $session->url,
            ];
        });
    }

    /**
     * Handle successful checkout from Stripe webhook.
     * - Mark order as paid
     * - Deduct stock
     * - Save shipping address
     * - Clear cart (if logged-in user)
     */
    public function handleCheckoutCompleted(Session $session): void
    {
        $orderId = $session->metadata->order_id ?? null;

        if (!$orderId) {
            Log::warning('Stripe webhook: checkout.session.completed without order_id in metadata', [
                'session_id' => $session->id,
            ]);
            return;
        }

        $order = Order::with('items')->find($orderId);

        if (!$order) {
            Log::warning('Stripe webhook: order not found', ['order_id' => $orderId]);
            return;
        }

        // Prevent double processing
        if ($order->payment_status === 'paid') {
            Log::info('Stripe webhook: order already paid', ['order_id' => $orderId]);
            return;
        }

        DB::transaction(function () use ($order, $session) {

            // ── 1. Mark order as paid ──────────────────────────
            $order->markAsPaid($session->payment_intent);

            // ── 2. Save guest email if not already set ─────────
            if (!$order->guest_email && !$order->user_id) {
                $order->update([
                    'guest_email' => $session->customer_details->email ?? null,
                ]);
            }

            // ── 3. Save shipping address from Stripe ───────────
            $shippingDetails = $session->shipping_details ?? $session->customer_details;

            if ($shippingDetails) {
                $order->update([
                    'shipping_address_data' => [
                        'name'        => $shippingDetails->name ?? null,
                        'phone'       => $shippingDetails->phone ?? $session->customer_details->phone ?? null,
                        'address'     => [
                            'line1'       => $shippingDetails->address->line1 ?? null,
                            'line2'       => $shippingDetails->address->line2 ?? null,
                            'city'        => $shippingDetails->address->city ?? null,
                            'state'       => $shippingDetails->address->state ?? null,
                            'postal_code' => $shippingDetails->address->postal_code ?? null,
                            'country'     => $shippingDetails->address->country ?? null,
                        ],
                    ],
                ]);
            }

            // ── 4. Deduct stock ────────────────────────────────
            foreach ($order->items as $item) {
                $variant = ProductVariant::lockForUpdate()->find($item->product_variant_id);

                if ($variant) {
                    $newQuantity = max(0, $variant->quantity - $item->quantity);
                    $variant->update(['quantity' => $newQuantity]);

                    if ($newQuantity === 0) {
                        Log::info("Product variant #{$variant->id} is now out of stock.");
                    }
                }
            }

            // ── 5. Clear cart (for logged-in users) ────────────
            if ($order->user_id) {
                $cart = Cart::where('user_id', $order->user_id)->first();
                if ($cart) {
                    $cart->items()->delete();
                }
            }
        });

        Log::info('Order completed successfully', [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
        ]);
    }

    /**
     * Handle expired/cancelled checkout session.
     * - Mark order as failed so it can be cleaned up.
     */
    public function handleSessionExpired(Session $session): void
    {
        $orderId = $session->metadata->order_id ?? null;

        if (!$orderId) return;

        $order = Order::find($orderId);

        if ($order && $order->payment_status === 'pending') {
            $order->markAsFailed();

            Log::info('Checkout session expired, order cancelled', [
                'order_id' => $order->id,
            ]);
        }
    }

    /**
     * Retrieve order status after checkout (for the success page).
     */
    public function getCheckoutStatus(string $sessionId): array
    {
        try {
            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            $order = \App\Models\Order::where('stripe_checkout_session_id', $sessionId)
                ->first();

            return [
                'payment_status' => $session->payment_status,  // 'paid', 'unpaid', 'no_payment_required'
                'order_number' => $order?->order_number,
                'order_status' => $order?->status,
                'customer_email' => $session->customer_details->email ?? $order?->guest_email,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new StripeServiceException(__('payment.stripe_session_retrieve_failed'));
        }
    }
}