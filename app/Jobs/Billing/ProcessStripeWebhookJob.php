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

            // Get the appropriate handler
            $handler = $this->getHandler($webhookEvent->event_type);

            if (!$handler) {
                Log::channel('billing')->info('webhook.no_handler', [
                    'webhook_event_id' => $this->webhookEventId,
                    'event_type' => $webhookEvent->event_type,
                ]);

                $webhookEvent->update([
                    'status' => WebhookStatusEnum::SKIPPED->value,
                    'error_message' => 'No handler for event type',
                ]);

                return;
            }

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
                'attempts' => $webhookEvent->attempts,
            ]);

        } catch (\Exception $e) {
            Log::channel('billing')->error('webhook.processing_failed', [
                'webhook_event_id' => $this->webhookEventId,
                'event_type' => $webhookEvent->event_type,
                'attempts' => $webhookEvent->attempts,
                'error' => $e->getMessage(),
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
            'customer.subscription.created' => app(\App\Services\Billing\Webhooks\HandleSubscriptionCreated::class),
            'customer.subscription.updated' => app(\App\Services\Billing\Webhooks\HandleSubscriptionUpdated::class),
            'customer.subscription.deleted' => app(\App\Services\Billing\Webhooks\HandleSubscriptionDeleted::class),
            'invoice.payment_succeeded' => app(\App\Services\Billing\Webhooks\HandleInvoicePaymentSucceeded::class),
            'invoice.payment_failed' => app(\App\Services\Billing\Webhooks\HandleInvoicePaymentFailed::class),
            'invoice.finalized' => app(\App\Services\Billing\Webhooks\HandleInvoiceFinalized::class),
            default => null,
        };
    }
}
