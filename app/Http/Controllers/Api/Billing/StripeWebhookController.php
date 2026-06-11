<?php

namespace App\Http\Controllers\Api\Billing;

use App\Contracts\Billing\BillingProviderInterface;
use App\Enums\Billing\WebhookStatusEnum;
use App\Http\Controllers\Controller;
use App\Jobs\Billing\ProcessStripeWebhookJob;
use App\Models\StripeWebhookEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __construct(
        private BillingProviderInterface $billingProvider,
    ) {}

    /**
     * Handle incoming Stripe webhooks.
     * 
     * POST /api/v1/webhooks/stripe
     */
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        // Verify webhook signature
        if (!$signature) {
            Log::channel('billing')->warning('webhook.missing_signature', [
                'ip' => $request->ip(),
            ]);

            return response('Missing Stripe signature header', 400);
        }

        try {
            $this->billingProvider->verifyWebhookSignature($payload, $signature, $secret);
        } catch (\Exception $e) {
            Log::channel('billing')->error('webhook.signature_verification_failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response('Webhook signature verification failed', 400);
        }

        // Parse event
        $event = $this->billingProvider->parseWebhookEvent($payload);

        // Check for duplicate (idempotency)
        $existing = StripeWebhookEvent::where('provider', 'stripe')
            ->where('provider_event_id', $event['id'])
            ->first();

        if ($existing) {
            Log::channel('billing')->info('webhook.duplicate', [
                'event_id' => $event['id'],
                'event_type' => $event['type'],
            ]);

            return response('Webhook already processed', 200);
        }

        // Store webhook event
        $webhookEvent = StripeWebhookEvent::create([
            'provider' => 'stripe',
            'provider_event_id' => $event['id'],
            'event_type' => $event['type'],
            'status' => WebhookStatusEnum::RECEIVED->value,
            'attempts' => 0,
            'received_at' => Carbon::now(),
            'payload' => json_decode($payload, true),
        ]);

        // Dispatch processing job
        ProcessStripeWebhookJob::dispatch($webhookEvent->id);

        Log::channel('billing')->info('webhook.received', [
            'webhook_event_id' => $webhookEvent->id,
            'event_id' => $event['id'],
            'event_type' => $event['type'],
        ]);

        return response('Webhook received', 200);
    }
}
