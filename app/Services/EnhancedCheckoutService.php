<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Address\AddressTypeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Events\Order\OrderPlaced;
use App\Events\Product\ProductVariantLowStock;
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
use Stripe\Exception\ApiErrorException;
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
     * Create (or reuse) the Stripe PaymentIntent for custom checkout.
     *
     * IMPORTANT: This is called every time the customer reaches the payment
     * step, including when they go Back and come Forward again. It must be
     * idempotent per user/store checkout attempt, otherwise every retry
     * spawns a brand new "pending" Order that lingers in the customer's
     * Order history forever. Instead of always creating a new Order, we
     * look for an existing, still-unpaid draft order for this user/store
     * and update it in place (refreshed totals, refreshed line items,
     * refreshed/updated PaymentIntent). A new Order is only ever created
     * the first time a given user reaches this step for a given store.
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

            // Fail fast before touching the database if the store can't
            // take payments at all — no point creating/reusing a draft order.
            if (!$store->canReceivePayments()) {
                throw new BaseApiException(
                    message: 'This store has not completed payment setup. Please contact the merchant.',
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

            // Reuse the customer's still-unpaid draft order for this store
            // (if one exists) instead of creating a duplicate every time
            // they (re)reach the payment step.
            $order = $this->findReusableDraftOrder($user, $store)
                ?? $this->createDraftOrder($store, $user);

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'shipping_amount' => $shippingCost,
                'discount_amount' => 0,
                'total' => $total,
                'currency' => $store->currency ?? 'usd',
                'shipping_method_id' => $shippingMethod->id,
                'shipping_method' => $shippingMethod->name,
            ]);

            // Refresh the line-item snapshot so it always matches what the
            // customer is actually about to pay for (cart may have changed
            // between attempts).
            $this->syncDraftOrderItems($order, $cart);

            $platformFeePercent = config('services.stripe.platform_fee_percent', 3.0);
            $applicationFeeAmount = (int) round($total * $platformFeePercent / 100 * 100);

            Log::info('Preparing PaymentIntent for enhanced checkout', [
                'order_id' => $order->id,
                'reused_existing_order' => !$order->wasRecentlyCreated,
                'total' => $total,
                'platform_fee_percent' => $platformFeePercent,
                'application_fee_amount' => $applicationFeeAmount,
                'destination' => $store->stripe_account_id,
            ]);

            // Create a new PaymentIntent, or update the existing one in
            // place if this order already has one that hasn't been
            // confirmed/consumed yet — avoids leaving orphaned PaymentIntents
            // behind in Stripe every time the customer goes back and forth.
            $paymentIntent = $this->createOrUpdatePaymentIntent(
                $order,
                $store,
                $total,
                $applicationFeeAmount
            );

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

            Log::info('PaymentIntent ready for enhanced checkout', [
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
     * Find an existing draft order for this user/store that hasn't been
     * paid yet, so a repeated "continue to payment" click (e.g. after the
     * customer pressed Back) updates it instead of creating a duplicate.
     *
     * Locked FOR UPDATE within the surrounding transaction to avoid a race
     * if the same customer double-submits from two tabs.
     */
    private function findReusableDraftOrder(User $user, Store $store): ?Order
    {
        return Order::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->where('status', OrderStatusEnum::PENDING)
            ->where('payment_status', PaymentStatusEnum::PENDING)
            ->lockForUpdate()
            ->latest('id')
            ->first();
    }

    /**
     * Create a brand-new draft order. Totals/items are filled in by the
     * caller right after — this just reserves the row (and order number).
     */
    private function createDraftOrder(Store $store, User $user): Order
    {
        return Order::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'subtotal' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total' => 0,
            'currency' => $store->currency ?? 'usd',
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::PENDING,
        ]);
    }

    /**
     * Replace a draft order's line items with a fresh snapshot of the
     * current cart. Uses forceDelete (not soft-delete) because these rows
     * were never part of a completed order — no need to keep a trail of
     * every intermediate snapshot each time the customer revisits payment.
     */
    private function syncDraftOrderItems(Order $order, Cart $cart): void
    {
        $order->items()->forceDelete();

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
    }

    /**
     * Create a fresh PaymentIntent for this order, or update the existing
     * one in place when it's still in a pre-confirmation state. This keeps
     * one PaymentIntent per checkout attempt instead of piling up a new
     * Stripe object every time totals change on retry.
     */
    private function createOrUpdatePaymentIntent(
        Order $order,
        Store $store,
        float $total,
        int $applicationFeeAmount
    ): PaymentIntent {
        $amountInCents = (int) round($total * 100);
        $metadata = [
            'order_id' => (string) $order->id,
            'store_id' => (string) $store->id,
            'user_id' => (string) $order->user_id,
        ];
        $description = "Order #{$order->order_number} from {$store->name}";

        if ($order->payment_intent_id) {
            try {
                $existing = $this->stripe->paymentIntents->retrieve($order->payment_intent_id);

                // Payment is already submitted/authorized/settled on
                // Stripe's side — leave it alone and hand back what's
                // already there rather than risk a second charge attempt.
                if (in_array($existing->status, ['processing', 'succeeded', 'requires_capture'], true)) {
                    return $existing;
                }

                // Still awaiting the customer's card details/confirmation —
                // safe to update amount/fees in place, same client_secret.
                if (in_array($existing->status, ['requires_payment_method', 'requires_confirmation', 'requires_action'], true)) {
                    return $this->stripe->paymentIntents->update($existing->id, [
                        'amount' => $amountInCents,
                        'application_fee_amount' => $applicationFeeAmount,
                        'metadata' => $metadata,
                        'description' => $description,
                    ]);
                }

                // Any other terminal state (e.g. canceled) — fall through
                // and create a fresh PaymentIntent below.
            } catch (ApiErrorException $e) {
                Log::warning('Could not retrieve existing PaymentIntent, creating a new one', [
                    'order_id' => $order->id,
                    'payment_intent_id' => $order->payment_intent_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->stripe->paymentIntents->create([
            'amount' => $amountInCents,
            'currency' => strtolower($store->currency ?? 'usd'),
            'metadata' => $metadata,
            'description' => $description,
            'application_fee_amount' => $applicationFeeAmount,
            'transfer_data' => [
                'destination' => $store->stripe_account_id,
            ],
        ]);
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
                    $previousQuantity = $variant->quantity;
                    $newQuantity = max(0, $variant->quantity - $item->quantity);
                    $variant->update(['quantity' => $newQuantity]);

                    // Only fire on the transition into low-stock, not on
                    // every subsequent order once already below threshold.
                    if (
                        $variant->track_inventory
                        && $variant->low_stock_threshold !== null
                        && $previousQuantity > $variant->low_stock_threshold
                        && $newQuantity <= $variant->low_stock_threshold
                    ) {
                        ProductVariantLowStock::dispatch($variant->id, $newQuantity, $variant->low_stock_threshold);
                    }
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

            OrderPlaced::dispatch($order->id);

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
