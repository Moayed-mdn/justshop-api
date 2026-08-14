<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront;

use App\Actions\Store\ApplyStripeAccountStatusAction;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\EnhancedCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Dedicated webhook controller for Stripe ecommerce events (orders, payments).
 * Separate from platform billing webhooks.
 */
class StripeEcommerceWebhookController extends Controller
{
    public function __construct(
        private EnhancedCheckoutService $checkoutService,
        private ApplyStripeAccountStatusAction $applyStripeAccountStatus,
    ) {}

    /**
     * Handle Stripe ecommerce webhook events.
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.ecommerce_webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe ecommerce webhook: Invalid payload', [
                'error' => $e->getMessage(),
            ]);
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe ecommerce webhook: Invalid signature', [
                'error' => $e->getMessage(),
            ]);
            return response('Invalid signature', 400);
        }

        Log::info('Stripe ecommerce webhook received', [
            'type' => $event->type,
            'event_id' => $event->id,
        ]);

        try {
            switch ($event->type) {
                case 'account.updated':
                    $this->handleAccountUpdated($event);
                    break;

                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event);
                    break;

                default:
                    Log::debug('Stripe ecommerce webhook: Unhandled event type', [
                        'type' => $event->type,
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Stripe ecommerce webhook: Handler failed', [
                'type' => $event->type,
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response('Handler error', 500);
        }

        return response('Webhook handled', 200);
    }

    /**
     * Handle account.updated event to sync Connect account status.
     */
    private function handleAccountUpdated(Event $event): void
    {
        $account = $event->data->object;
        $stripeAccountId = $account->id;

        $store = Store::where('stripe_account_id', $stripeAccountId)->first();

        if (!$store) {
            Log::warning('Stripe ecommerce webhook: account.updated for unknown account', [
                'stripe_account_id' => $stripeAccountId,
            ]);
            return;
        }

        $store = $this->applyStripeAccountStatus->execute($store, $account);

        Log::info('Stripe ecommerce webhook: account.updated processed', [
            'store_id' => $store->id,
            'stripe_account_id' => $stripeAccountId,
            'can_receive_payments' => $store->canReceivePayments(),
        ]);
    }

    /**
     * Handle payment_intent.succeeded event to complete checkout server-side.
     */
    private function handlePaymentIntentSucceeded(Event $event): void
    {
        $paymentIntent = $event->data->object;
        $paymentIntentId = $paymentIntent->id;
        $orderId = $paymentIntent->metadata->order_id ?? null;

        if (!$orderId) {
            Log::warning('Stripe ecommerce webhook: payment_intent.succeeded missing order_id in metadata', [
                'payment_intent_id' => $paymentIntentId,
            ]);
            return;
        }

        try {
            $order = $this->checkoutService->completeCheckout($paymentIntentId);

            Log::info('Stripe ecommerce webhook: payment_intent.succeeded processed', [
                'payment_intent_id' => $paymentIntentId,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe ecommerce webhook: Failed to complete checkout', [
                'payment_intent_id' => $paymentIntentId,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
