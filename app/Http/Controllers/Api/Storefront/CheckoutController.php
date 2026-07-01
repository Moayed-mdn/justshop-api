<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\EnhancedCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private EnhancedCheckoutService $enhancedCheckoutService,
    ) {}

    public function confirm(Request $request, Store $store): JsonResponse
    {
        // Confirm payment logic here
        return $this->success(null, 'Payment confirmed');
    }

    public function initiateEnhanced(Request $request, Store $store): JsonResponse
    {
        $user = $request->user();
        
        $data = $this->enhancedCheckoutService->initiateCheckout($user, $store);
        
        return $this->success($data, 'Checkout initiated');
    }

    /**
     * Get available shipping methods for given address.
     */
    public function getShippingMethods(Request $request, Store $store): JsonResponse
    {
        $request->validate([
            'shipping_address' => 'required|array',
            'order_amount' => 'required|numeric|min:0',
        ]);
        
        $methods = $this->enhancedCheckoutService->getAvailableShippingMethods(
            $store,
            $request->input('shipping_address'),
            (float) $request->input('order_amount')
        );
        
        return $this->success($methods, 'Shipping methods retrieved');
    }

    /**
     * Create Stripe PaymentIntent for checkout.
     */
    public function createPaymentIntent(Request $request, Store $store): JsonResponse
    {
        $request->validate([
            'shipping_address' => 'required|array',
            'billing_address' => 'required|array',
            'shipping_method_id' => 'required|integer|exists:shipping_methods,id',
        ]);
        
        $user = $request->user();
        
        $data = $this->enhancedCheckoutService->createPaymentIntent(
            $user,
            $store,
            $request->input('shipping_address'),
            $request->input('billing_address'),
            (int) $request->input('shipping_method_id')
        );
        
        return $this->success($data, 'Payment intent created');
    }

    /**
     * Complete checkout after successful payment.
     */
    public function completeEnhanced(Request $request, Store $store): JsonResponse
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);
        
        $order = $this->enhancedCheckoutService->completeCheckout(
            $request->input('payment_intent_id')
        );
        
        return $this->success($order, 'Checkout completed');
    }
}
