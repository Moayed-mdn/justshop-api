<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Address\AddressTypeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Exceptions\BaseApiException;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShippingMethod;
use App\Models\Store;
use App\Models\User;
use App\Services\Storefront\Runtime\RuntimeStoreResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

/**
 * Enhanced checkout service for custom Shopify-inspired checkout flow.
 * 
 * Handles checkout session creation, address validation, shipping method
 * selection, and payment processing with Stripe PaymentIntents.
 */
class EnhancedCheckoutService
{
    public function __construct(
        private StripeClient $stripe,
        private RuntimeStoreResolver $storeResolver,
        private StoreAddressSettingsService $addressSettingsService,
        private ShippingMethodService $shippingMethodService,
    ) {}

    /**
     * Initialize checkout session with cart validation.
     * 
     * @param User $user
     * @param Store $store
     * @return array Checkout session data
     */
    public function initiateCheckout(User $user, Store $store): array
    {
        $cart = $user->cartForStore($store->id);

        if (!$cart || $cart->items->isEmpty()) {
            throw new BaseApiException(
                message: __('cart.empty'),
                statusCode: 422,
                errorCode: \App\Enums\ErrorCode::SYS_001->value
            );
        }

        $cart->load([
            'items.productVariant.product.translations',
            'items.productVariant.optionValues.option',
            'items.productVariant.images',
        ]);

        // Calculate cart totals
        $subtotal = $cart->calculateSubtotal();

        // Get user's saved addresses
        $addresses = $user->addresses()
            ->where(function ($q) use ($store) {
                $q->whereNull('store_id')
                    ->orWhere('store_id', $store->id);
            })
            ->get()
            ->map(fn($addr) => $addr->toApiArray());

        $addressSettings = $this->addressSettingsService->getSettingsPayload($store);

        return [
            'cart' => [
                'items' => $cart->items->map(function ($item) {
                    $locale = app()->getLocale();
                    $translation = $item->productVariant->product->translations
                        ->where('locale', $locale)->first()
                        ?? $item->productVariant->product->translations->first();

                    return [
                        'id' => $item->id,
                        'product_variant_id' => $item->product_variant_id,
                        'product_name' => $translation?->name ?? 'Product',
                        'sku' => $item->productVariant->sku,
                        'price' => $item->productVariant->price,
                        'quantity' => $item->quantity,
                        'subtotal' => $item->productVariant->price * $item->quantity,
                        'image_url' => $item->productVariant->images->where('is_primary', true)->first()?->full_url
                            ?? $item->productVariant->images->first()?->full_url,
                    ];
                }),
                'subtotal' => $subtotal,
                'items_count' => $cart->items->sum('quantity'),
            ],
            'addresses' => $addresses,
            'allowed_countries' => $addressSettings['allowed_countries'],
            'address_settings' => $addressSettings,
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'currency' => $store->currency ?? 'USD',
            ],
        ];
    }

    /**
     * Get available shipping methods for an address.
     * 
     * @param Store $store
     * @param array $shippingAddress
     * @param float $orderAmount
     * @return array
     */
    public function getAvailableShippingMethods(
        Store $store,
        array $shippingAddress,
        float $orderAmount
    ): array {
        $normalizedShippingAddress = $this->addressSettingsService->normalizeAddressData($shippingAddress);
        $errors = $this->addressSettingsService->validateAddressForStore($store, $normalizedShippingAddress);
        
        if (!empty($errors)) {
            throw new BaseApiException(
                message: 'Invalid shipping address: ' . implode(' ', $errors),
                statusCode: 422,
                errorCode: \App\Enums\ErrorCode::VAL_001->value
            );
        }

        // Get available methods
        $methods = $this->shippingMethodService->getAvailableMethodsForAddress(
            $store,
            $normalizedShippingAddress,
            $orderAmount
        );

        return $methods->map(function ($method) {
            return [
                'id' => $method->id,
                'name' => $method->name,
                'code' => $method->code,
                'description' => $method->description,
                'price' => $method->effective_price ?? $method->price,
                'formatted_price' => $method->getFormattedPrice(),
                'delivery_estimate' => $method->getDeliveryEstimate(),
                'estimated_delivery_days' => $method->estimated_delivery_days,
                'zone_name' => $method->zone_name ?? null,
            ];
        })->values()->toArray();
    }

    /**
     * Create Stripe PaymentIntent for custom checkout.
     * 
     * @param User $user
     * @param Store $store
     * @param array $shippingAddress
     * @param array $billingAddress
     * @param int $shippingMethodId
     * @return array PaymentIntent data
     */
    public function createPaymentIntent(
        User $user,
        Store $store,
        array $shippingAddress,
        array $billingAddress,
        int $shippingMethodId
    ): array {
        return DB::transaction(function () use ($user, $store, $shippingAddress, $billingAddress, $shippingMethodId) {
            $cart = $user->cartForStore($store->id);

            if (!$cart || $cart->items->isEmpty()) {
                throw new BaseApiException(
                    message: __('cart.empty'),
                    statusCode: 422,
                    errorCode: \App\Enums\ErrorCode::SYS_001->value
                );
            }

            $normalizedShippingAddress = $this->validateCheckoutAddress(
                $store,
                $shippingAddress,
                'shipping'
            );
            $normalizedBillingAddress = $this->validateCheckoutAddress(
                $store,
                $billingAddress,
                'billing'
            );

            // Get shipping method
            $shippingMethod = $this->shippingMethodService->getMethod($store, $shippingMethodId);
            if (!$shippingMethod || !$shippingMethod->is_active) {
                throw new BaseApiException(
                    message: 'Invalid shipping method',
                    statusCode: 422,
                    errorCode: \App\Enums\ErrorCode::VAL_001->value
                );
            }

            // Calculate totals
            $subtotal = $cart->calculateSubtotal();
            $shippingCost = $this->shippingMethodService->calculateShippingCost(
                $shippingMethod,
                $normalizedShippingAddress,
                $subtotal
            );

            if ($shippingCost === null) {
                throw new BaseApiException(
                    message: 'Shipping method not available for this address',
                    statusCode: 422,
                    errorCode: \App\Enums\ErrorCode::VAL_001->value
                );
            }

            $tax = 0; // TODO: Implement tax calculation
            $total = $subtotal + $shippingCost + $tax;

            // Create or update order
            $order = Order::create([
                'store_id' => $store->id,
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'shipping_amount' => $shippingCost,
                'discount_amount' => 0,
                'total' => $total,
                'currency' => $store->currency ?? 'usd',
                'status' => OrderStatusEnum::PENDING,
                'payment_status' => PaymentStatusEnum::PENDING,
                'shipping_method_id' => $shippingMethod->id,
                'shipping_method' => $shippingMethod->name,
            ]);

            // Create order items
            foreach ($cart->items as $item) {
                $locale = app()->getLocale();
                $translation = $item->productVariant->product->translations
                    ->where('locale', $locale)->first()
                    ?? $item->productVariant->product->translations->first();

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->productVariant->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $translation?->name ?? 'Product',
                    'sku' => $item->productVariant->sku,
                    'unit_price' => $item->productVariant->price,
                    'unit_discount_percentage' => 0,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->productVariant->price * $item->quantity,
                    'total' => $item->productVariant->price * $item->quantity,
                    'attributes' => $item->productVariant->optionValues->map(function ($ov) {
                        return ['name' => $ov->option->name, 'value' => $ov->value];
                    })->toArray(),
                ]);
            }

            // Create Stripe PaymentIntent
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int)($total * 100), // Convert to cents
                'currency' => strtolower($store->currency ?? 'usd'),
                'metadata' => [
                    'order_id' => $order->id,
                    'store_id' => $store->id,
                    'user_id' => $user->id,
                ],
                'description' => "Order #{$order->order_number} from {$store->name}",
            ]);

            // Store PaymentIntent ID
            $order->update([
                'payment_intent_id' => $paymentIntent->id,
            ]);

            // Store session data in cache for later retrieval
            cache()->put(
                "checkout_session:{$order->id}",
                [
                    'shipping_address' => $normalizedShippingAddress,
                    'billing_address' => $normalizedBillingAddress,
                    'shipping_method_id' => $shippingMethodId,
                ],
                now()->addHours(2)
            );

            Log::info('PaymentIntent created for enhanced checkout', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $total,
            ]);

            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => $total,
                'currency' => $store->currency ?? 'USD',
            ];
        });
    }

    /**
     * Complete checkout after successful payment.
     * 
     * @param string $paymentIntentId
     * @return Order
     */
    public function completeCheckout(string $paymentIntentId): Order
    {
        return DB::transaction(function () use ($paymentIntentId) {
            // Retrieve PaymentIntent from Stripe
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);

            if ($paymentIntent->status !== 'succeeded') {
                throw new BaseApiException(
                    message: 'Payment has not been completed',
                    statusCode: 400,
                    errorCode: \App\Enums\ErrorCode::SYS_001->value
                );
            }

            // Find order
            $order = Order::where('payment_intent_id', $paymentIntentId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->payment_status === PaymentStatusEnum::PAID) {
                return $order; // Already processed
            }

            // Get cached session data
            $sessionData = cache()->get("checkout_session:{$order->id}");

            if ($sessionData) {
                [$shippingAddressId, $billingAddressId] = $this->persistCheckoutAddresses($order, $sessionData);

                $order->update([
                    'shipping_address_id' => $shippingAddressId,
                    'billing_address_id' => $billingAddressId,
                ]);

                cache()->forget("checkout_session:{$order->id}");
            }

            // Mark order as paid
            $order->markAsPaid($paymentIntentId);

            // Deduct stock
            foreach ($order->items as $item) {
                $variant = $item->productVariant;
                if ($variant) {
                    $newQuantity = max(0, $variant->quantity - $item->quantity);
                    $variant->update(['quantity' => $newQuantity]);
                }
            }

            // Clear cart
            if ($order->user_id) {
                $cart = Cart::where('user_id', $order->user_id)
                    ->where('store_id', $order->store_id)
                    ->first();
                    
                if ($cart) {
                    $cart->items()->forceDelete();
                }
            }

            Log::info('Enhanced checkout completed successfully', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntentId,
            ]);

            return $order->fresh(['items', 'shippingAddress', 'billingAddress']);
        });
    }

    /**
     * Persist checkout addresses using the same default-aware semantics as the
     * storefront address book. Identical shipping/billing addresses collapse
     * into a single `both` address so future checkouts reuse the right default.
     */
    private function persistCheckoutAddresses(Order $order, array $sessionData): array
    {
        $shippingAddress = $this->addressSettingsService->normalizeAddressData(
            $sessionData['shipping_address'] ?? []
        );
        $billingAddress = $this->addressSettingsService->normalizeAddressData(
            $sessionData['billing_address'] ?? []
        );

        if ($this->addressesMatch($shippingAddress, $billingAddress)) {
            $addressId = $this->saveAddressIfNeeded(
                (int) $order->user_id,
                (int) $order->store_id,
                $shippingAddress,
                AddressTypeEnum::BOTH
            );

            return [$addressId, $addressId];
        }

        return [
            $this->saveAddressIfNeeded(
                (int) $order->user_id,
                (int) $order->store_id,
                $shippingAddress,
                AddressTypeEnum::SHIPPING
            ),
            $this->saveAddressIfNeeded(
                (int) $order->user_id,
                (int) $order->store_id,
                $billingAddress,
                AddressTypeEnum::BILLING
            ),
        ];
    }

    /**
     * Save address if it doesn't already exist for the user and promote it to
     * the relevant checkout defaults for the current store.
     */
    private function saveAddressIfNeeded(
        int $userId,
        int $storeId,
        array $addressData,
        AddressTypeEnum $addressType
    ): ?int
    {
        $normalizedAddress = $this->addressSettingsService->normalizeAddressData($addressData);

        // Check if address already exists
        $existing = Address::where('user_id', $userId)
            ->where('store_id', $storeId)
            ->where('first_name', $normalizedAddress['first_name'])
            ->where('last_name', $normalizedAddress['last_name'])
            ->where('address_line_1', $normalizedAddress['address_line_1'])
            ->where('city', $normalizedAddress['city'])
            ->where('state', $normalizedAddress['state'])
            ->where('postal_code', $normalizedAddress['postal_code'])
            ->where('country', $normalizedAddress['country'])
            ->first();

        if ($existing) {
            $existing->update([
                'name' => $normalizedAddress['name'] ?? $existing->name ?? $this->buildAddressName($normalizedAddress),
                'company' => $normalizedAddress['company'],
                'phone' => $normalizedAddress['phone'],
                'email' => $normalizedAddress['email'],
                'type' => $this->mergeAddressType($existing->type, $addressType),
            ]);

            $this->applyCheckoutDefaults(
                $existing->fresh(),
                $this->mergeAddressType($existing->type, $addressType),
                $storeId
            );

            return $existing->id;
        }

        // Create new address
        $address = Address::create([
            'user_id' => $userId,
            'store_id' => $storeId,
            'name' => $normalizedAddress['name'] ?? $this->buildAddressName($normalizedAddress),
            'first_name' => $normalizedAddress['first_name'],
            'last_name' => $normalizedAddress['last_name'],
            'company' => $normalizedAddress['company'],
            'address_line_1' => $normalizedAddress['address_line_1'],
            'address_line_2' => $normalizedAddress['address_line_2'],
            'city' => $normalizedAddress['city'],
            'state' => $normalizedAddress['state'],
            'postal_code' => $normalizedAddress['postal_code'],
            'country' => $normalizedAddress['country'],
            'phone' => $normalizedAddress['phone'],
            'email' => $normalizedAddress['email'],
            'type' => $addressType,
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ]);

        $this->applyCheckoutDefaults($address, $addressType, $storeId);

        return $address->id;
    }

    private function validateCheckoutAddress(Store $store, array $addressData, string $addressType): array
    {
        $normalizedAddress = $this->addressSettingsService->normalizeAddressData($addressData);
        $errors = $this->addressSettingsService->validateAddressForStore($store, $normalizedAddress);

        if (!empty($errors)) {
            throw new BaseApiException(
                message: 'Invalid ' . $addressType . ' address: ' . implode(' ', $errors),
                statusCode: 422,
                errorCode: \App\Enums\ErrorCode::VAL_001->value,
                errors: $this->addressSettingsService->formatValidationErrors($errors)
            );
        }

        return $normalizedAddress;
    }

    private function addressesMatch(array $shippingAddress, array $billingAddress): bool
    {
        return $shippingAddress === $billingAddress;
    }

    private function mergeAddressType(
        AddressTypeEnum|string|null $existingType,
        AddressTypeEnum $incomingType
    ): AddressTypeEnum {
        $resolvedExistingType = match (true) {
            $existingType instanceof AddressTypeEnum => $existingType,
            is_string($existingType) && $existingType !== '' => AddressTypeEnum::from($existingType),
            default => null,
        };

        if ($resolvedExistingType === null || $resolvedExistingType === $incomingType) {
            return $incomingType;
        }

        if (
            $resolvedExistingType === AddressTypeEnum::BOTH
            || $incomingType === AddressTypeEnum::BOTH
        ) {
            return AddressTypeEnum::BOTH;
        }

        return AddressTypeEnum::BOTH;
    }

    private function applyCheckoutDefaults(Address $address, AddressTypeEnum $addressType, int $storeId): void
    {
        $updates = [];

        if (in_array($addressType, [AddressTypeEnum::SHIPPING, AddressTypeEnum::BOTH], true)) {
            Address::where('user_id', $address->user_id)
                ->where('store_id', $storeId)
                ->where('id', '!=', $address->id)
                ->whereIn('type', [AddressTypeEnum::SHIPPING->value, AddressTypeEnum::BOTH->value])
                ->update(['is_default_shipping' => false]);

            $updates['is_default_shipping'] = true;
        }

        if (in_array($addressType, [AddressTypeEnum::BILLING, AddressTypeEnum::BOTH], true)) {
            Address::where('user_id', $address->user_id)
                ->where('store_id', $storeId)
                ->where('id', '!=', $address->id)
                ->whereIn('type', [AddressTypeEnum::BILLING->value, AddressTypeEnum::BOTH->value])
                ->update(['is_default_billing' => false]);

            $updates['is_default_billing'] = true;
        }

        if (!empty($updates)) {
            $address->update($updates);
        }
    }

    private function buildAddressName(array $normalizedAddress): string
    {
        $fullName = trim(($normalizedAddress['first_name'] ?? '') . ' ' . ($normalizedAddress['last_name'] ?? ''));

        return $fullName !== '' ? $fullName : 'Checkout Address';
    }
}
