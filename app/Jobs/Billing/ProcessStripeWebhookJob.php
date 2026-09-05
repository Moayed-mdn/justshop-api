<?php

namespace App\Jobs\Billing;

use App\Enums\Billing\WebhookStatusEnum;
use App\Models\StripeWebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessStripeWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 60, 120]; // 30s, 1min, 2min

    /**
     * The webhook event instance for use in getHandler
     */
    private ?StripeWebhookEvent $webhookEvent = null;

    public function __construct(
        private int $webhookEventId,
    ) {}

    public function handle(): void
    {
        $startTime = microtime(true);

        // Lock the webhook event row to prevent duplicate processing
        $webhookEvent = DB::transaction(function () {
            return StripeWebhookEvent::where('id', $this->webhookEventId)
                ->lockForUpdate()
                ->first();
        });

        if (!$webhookEvent) {
            Log::channel('billing')->warning('webhook.event_not_found', [
                'webhook_event_id' => $this->webhookEventId,
            ]);
            return;
        }

        $this->webhookEvent = $webhookEvent;

        // Skip if already processed
        if ($webhookEvent->status === WebhookStatusEnum::PROCESSED->value) {
            Log::channel('billing')->info('webhook.already_processed', [
                'webhook_event_id' => $this->webhookEventId,
                'event_type' => $webhookEvent->event_type,
            ]);
            return;
        }

        try {
            // Increment attempts counter
            $webhookEvent->increment('attempts');

            // LOG: Full webhook event data before processing
            Log::channel('billing')->info('webhook.event.received', [
                'webhook_event_id' => $this->webhookEventId,
                'event_id' => $webhookEvent->event_id,
                'event_type' => $webhookEvent->event_type,
                'attempts' => $webhookEvent->attempts,
                'livemode' => $webhookEvent->payload['livemode'] ?? null,
                'created' => $webhookEvent->payload['created'] ?? null,
                'api_version' => $webhookEvent->payload['api_version'] ?? null,
                'object_id' => $webhookEvent->payload['data']['object']['id'] ?? null,
                'object_type' => $webhookEvent->payload['data']['object']['object'] ?? null,
            ]);

            // LOG: Full payload for debugging (can be removed in production)
            if (config('app.debug')) {
                Log::channel('billing')->debug('webhook.event.full_payload', [
                    'webhook_event_id' => $this->webhookEventId,
                    'event_type' => $webhookEvent->event_type,
                    'payload' => $webhookEvent->payload,
                ]);
            }

            // Get the appropriate handler
            $handler = $this->getHandler($webhookEvent->event_type);

            if (!$handler) {
                Log::channel('billing')->warning('webhook.no_handler', [
                    'webhook_event_id' => $this->webhookEventId,
                    'event_type' => $webhookEvent->event_type,
                    'event_id' => $webhookEvent->event_id,
                    'available_handlers' => [
                        'checkout.session.completed',
                        'checkout.session.expired',
                        'customer.subscription.created',
                        'customer.subscription.updated',
                        'customer.subscription.deleted',
                        'invoice.payment_succeeded',
                        'invoice.payment_failed',
                        'invoice.finalized',
                        'account.updated',
                    ],
                ]);

                $webhookEvent->update([
                    'status' => WebhookStatusEnum::SKIPPED->value,
                    'error_message' => 'No handler for event type',
                ]);

                return;
            }

            // LOG: Handler found, about to process
            Log::channel('billing')->info('webhook.handler.found', [
                'webhook_event_id' => $this->webhookEventId,
                'event_type' => $webhookEvent->event_type,
                'handler_class' => get_class($handler),
            ]);

            // Process the webhook
            $handler->handle($webhookEvent->payload);

            // Mark as processed
            $webhookEvent->update([
                'status' => WebhookStatusEnum::PROCESSED->value,
                'processed_at' => now(),
                'error_message' => null,
            ]);

            Log::channel('billing')->info('webhook.processed', [
                'webhook_event_id' => $this->webhookEventId,
                'event_type' => $webhookEvent->event_type,
                'event_id' => $webhookEvent->event_id,
                'handler_class' => get_class($handler),
                'attempts' => $webhookEvent->attempts,
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

        } catch (\Exception $e) {
            Log::channel('billing')->error('webhook.processing_failed', [
                'webhook_event_id' => $this->webhookEventId,
                'event_type' => $webhookEvent->event_type,
                'event_id' => $webhookEvent->event_id,
                'attempts' => $webhookEvent->attempts,
                'error_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $webhookEvent->update([
                'status' => WebhookStatusEnum::FAILED->value,
                'error_message' => $e->getMessage(),
            ]);

            // Re-throw to trigger job retry
            throw $e;
        }
    }

    /**
     * Get the appropriate handler for the webhook event type.
     */
    private function getHandler(string $eventType): ?object
    {
        if ($eventType === 'checkout.session.completed') {
            // Check session mode to pick the right handler
            $sessionMode = $this->webhookEvent?->payload['data']['object']['mode'] ?? null;
            if ($sessionMode === 'payment') {
                return app(\App\Services\Payment\Webhooks\HandleEcommerceCheckoutSessionCompleted::class);
            }
            
            return app(\App\Services\Billing\Webhooks\HandleCheckoutSessionCompleted::class);
        }

        return match ($eventType) {
            'checkout.session.expired' => app(\App\Services\Billing\Webhooks\HandleCheckoutSessionExpired::class),
            'customer.subscription.created' => app(\App\Services\Billing\Webhooks\HandleSubscriptionCreated::class),
            'customer.subscription.updated' => app(\App\Services\Billing\Webhooks\HandleSubscriptionUpdated::class),
            'customer.subscription.deleted' => app(\App\Services\Billing\Webhooks\HandleSubscriptionDeleted::class),
            'invoice.payment_succeeded' => app(\App\Services\Billing\Webhooks\HandleInvoicePaymentSucceeded::class),
            'invoice.payment_failed' => app(\App\Services\Billing\Webhooks\HandleInvoicePaymentFailed::class),
            'invoice.finalized' => app(\App\Services\Billing\Webhooks\HandleInvoiceFinalized::class),
            'account.updated' => app(\App\Services\Stripe\HandleStripeConnectAccountUpdated::class),
            default => null,
        };
    }
}
