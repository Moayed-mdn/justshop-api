<?php

namespace App\Services\Payment\Webhooks;

use App\Services\CheckoutService;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;

class HandleEcommerceCheckoutSessionCompleted
{
    public function __construct(
        private CheckoutService $checkoutService,
    ) {}

    /**
     * Handle e-commerce checkout.session.completed webhook.
     */
    public function handle(array $event): void
    {
        $sessionData = $event['data']['object'];

        // Only handle payment mode checkouts
        if ($sessionData['mode'] !== 'payment') {
            Log::info('Webhook: checkout.session.completed is not payment mode, skipping', [
                'session_id' => $sessionData['id'],
                'mode' => $sessionData['mode'],
            ]);
            return;
        }

        // Convert array to Stripe Session object
        $session = Session::constructFrom($sessionData);

        // Handle the completed checkout
        $this->checkoutService->handleCheckoutCompleted($session);

        Log::info('E-commerce checkout completed successfully', [
            'session_id' => $sessionData['id'],
            'order_id' => $sessionData['metadata']['order_id'] ?? null,
        ]);
    }
}
