<?php

namespace App\Services\Billing\Webhooks;

use App\Actions\Billing\RecordInvoiceAction;
use App\DTOs\Billing\RecordInvoiceDTO;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class HandleInvoiceFinalized
{
    public function __construct(
        private RecordInvoiceAction $recordInvoice,
    ) {}

    /**
     * Handle invoice.finalized webhook.
     * 
     * Creates or updates invoice record when invoice is finalized.
     */
    public function handle(array $event): void
    {
        $invoiceData = $event['data']['object'];

        Log::channel('billing')->info('webhook.invoice.finalized', [
            'invoice_id' => $invoiceData['id'],
            'subscription_id' => $invoiceData['subscription'] ?? null,
            'total' => $invoiceData['total'],
            'currency' => $invoiceData['currency'],
        ]);

        // Find subscription to get billing account
        $subscriptionId = $invoiceData['subscription'] ?? null;
        $subscription = null;

        if ($subscriptionId) {
            $subscription = Subscription::where('provider_subscription_id', $subscriptionId)->first();
        }

        // Fallback: try to find subscription via line item metadata
        if (!$subscription) {
            $billingAccountId = $this->extractBillingAccountIdFromLines($invoiceData['lines']['data'] ?? []);
            if ($billingAccountId) {
                $subscription = Subscription::where('billing_account_id', $billingAccountId)
                    ->whereIn('status', ['active', 'trialing', 'incomplete'])
                    ->latest()
                    ->first();
            }
        }

        if (!$subscription) {
            Log::channel('billing')->warning('webhook.invoice.finalized.subscription_not_found', [
                'invoice_id' => $invoiceData['id'],
                'provider_subscription_id' => $subscriptionId,
            ]);
            return;
        }

        // Record invoice
        $this->recordInvoice->execute(
            RecordInvoiceDTO::fromStripeInvoice(
                $invoiceData,
                $subscription->billing_account_id,
                $subscription->id
            )
        );
    }

    private function extractBillingAccountIdFromLines(array $lines): ?int
    {
        foreach ($lines as $line) {
            if (isset($line['metadata']['billing_account_id'])) {
                return (int) $line['metadata']['billing_account_id'];
            }
        }
        return null;
    }
}
